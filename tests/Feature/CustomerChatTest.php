<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\District;
use App\Models\MotorProduct;
use App\Models\Region;
use App\Models\User;
use App\Models\Village;
use App\Services\ChatConversationStarter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class CustomerChatTest extends TestCase
{
    use RefreshDatabase;

    private int $customerSequence = 0;

    public function test_dana_tunai_interest_creates_generic_conversation_and_internal_reminder(): void
    {
        $customer = $this->createCustomer();
        $response = $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai']);

        $conversation = ChatConversation::with('messages')->firstOrFail();
        $response->assertRedirect('/customer/chat/' . $conversation->id);
        $this->assertSame('dana_tunai', $conversation->context_type);
        $this->assertNull($conversation->motor_product_id);
        $this->assertSame($customer->id, $conversation->customer_id);
        $this->assertSame('open', $conversation->status);
        $this->assertNotNull($conversation->last_message_at);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'sender_id' => $customer->id,
            'body' => 'Halo, saya tertarik dengan layanan Dana Tunai / Gadai BPKB Motor. Saya ingin mendapatkan informasi lebih lanjut.',
        ]);

        $this->assertDatabaseCount('whatsapp_notifications', 1);
        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_dana_tunai_interest',
            'recipient' => '081315379598',
            'reference_type' => 'chat_conversation',
            'reference_id' => (string) $conversation->id,
            'status' => 'queued',
        ]);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_repeated_dana_tunai_interest_reuses_open_conversation_without_duplicate_reminder(): void
    {
        $customer = $this->createCustomer();
        $session = ['customer_id' => $customer->id];

        $this->withSession($session)
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai'])
            ->assertRedirect();
        $conversation = ChatConversation::firstOrFail();
        $this->withSession($session)
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai'])
            ->assertRedirect('/customer/chat/' . $conversation->id);

        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertDatabaseCount('whatsapp_notifications', 1);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_customer_chat_lists_context_and_uses_shared_read_receipts(): void
    {
        $customer = $this->createCustomer();
        $admin = $this->createAdmin();
        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai'])
            ->assertRedirect();
        $conversation = ChatConversation::firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/chat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/ChatIndexPage')
                ->where('conversations.data.0.serviceLabel', 'Dana Tunai / Gadai BPKB')
                ->where('conversations.data.0.unreadCount', 1));

        $this->actingAs($admin)
            ->get('/admin/chat/' . $conversation->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/ChatConversationPage')
                ->where('conversation.serviceLabel', 'Dana Tunai / Gadai BPKB')
                ->where('conversation.motorName', null));
        $this->assertNotNull($conversation->messages()->firstOrFail()->fresh()->read_at);

        $this->actingAs($admin)
            ->post('/admin/chat/' . $conversation->id . '/messages', ['body' => 'Silakan sampaikan pertanyaan Anda.'])
            ->assertRedirect('/admin/chat/' . $conversation->id);

        Auth::logout();
        $session = ['customer_id' => $customer->id];
        $this->withSession($session)
            ->get('/customer/chat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer/ChatPage')
                ->where('conversations.0.serviceLabel', 'Dana Tunai / Gadai BPKB')
                ->where('conversations.0.motorName', null)
                ->where('conversations.0.unreadCount', 1));

        $this->withSession($session)
            ->get('/customer/chat/' . $conversation->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer/ConversationPage')
                ->where('conversation.serviceLabel', 'Dana Tunai / Gadai BPKB')
                ->where('conversation.motorName', null));
        $adminReply = $conversation->messages()->where('sender_type', 'admin')->firstOrFail();
        $this->assertNotNull($adminReply->fresh()->read_at);

        $this->withSession($session)
            ->post('/customer/chat/' . $conversation->id . '/messages', ['body' => 'Saya ingin bertanya lebih lanjut.'])
            ->assertRedirect('/customer/chat/' . $conversation->id);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'sender_id' => $customer->id,
            'body' => 'Saya ingin bertanya lebih lanjut.',
        ]);

        $this->actingAs($admin)
            ->get('/admin/chat/' . $conversation->id)
            ->assertOk();
        $customerReply = $conversation->messages()->where('body', 'Saya ingin bertanya lebih lanjut.')->firstOrFail();
        $this->assertNotNull($customerReply->fresh()->read_at);
    }

    public function test_customer_can_only_open_owned_conversations(): void
    {
        $owner = $this->createCustomer();
        $otherCustomer = $this->createCustomer();
        $this->withSession(['customer_id' => $owner->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai']);
        $conversation = ChatConversation::firstOrFail();

        $this->withSession(['customer_id' => $otherCustomer->id])
            ->get('/customer/chat/' . $conversation->id)
            ->assertNotFound();
        $this->withSession(['customer_id' => $otherCustomer->id])
            ->post('/customer/chat/' . $conversation->id . '/messages', ['body' => 'Tidak berhak'])
            ->assertNotFound();
    }

    public function test_generic_starter_keeps_motor_context_and_product_relation(): void
    {
        $customer = $this->createCustomer();
        $motor = MotorProduct::create([
            'code' => 'TEST-MOTOR',
            'name' => 'Vario 125 CBS',
            'family' => 'Vario',
            'status' => 'active',
        ]);

        $response = $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', [
                'context_type' => 'motor',
                'motor_product_id' => $motor->id,
            ]);
        $conversation = ChatConversation::firstOrFail();
        $response->assertRedirect('/customer/chat/' . $conversation->id);

        $this->assertDatabaseHas('chat_conversations', [
            'customer_id' => $customer->id,
            'context_type' => 'motor',
            'motor_product_id' => $motor->id,
            'status' => 'open',
        ]);
        $this->withSession(['customer_id' => $customer->id])
            ->get('/customer/chat/' . $conversation->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer/ConversationPage')
                ->where('conversation.serviceLabel', 'Motor Honda')
                ->where('conversation.motorName', 'Vario 125 CBS'));

        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'motor', 'motor_product_id' => $motor->id])
            ->assertRedirect('/customer/chat/' . $conversation->id);
        $this->assertDatabaseCount('chat_messages', 1);
        $this->actingAs($this->createAdmin())
            ->get('/admin/chat/' . $conversation->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('conversation.motorName', 'Vario 125 CBS'));
        $this->post('/admin/chat/' . $conversation->id . '/messages', ['body' => 'Informasi motor tersedia.'])
            ->assertRedirect('/admin/chat/' . $conversation->id);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'admin',
            'body' => 'Informasi motor tersedia.',
        ]);
    }

    public function test_customer_session_cannot_access_admin_chat_and_unassigned_user_is_denied(): void
    {
        $customer = $this->createCustomer();
        $this->withSession(['customer_id' => $customer->id])
            ->get('/admin/chat')
            ->assertRedirect('/admin/login');

        $regularUser = User::forceCreate([
            'name' => 'Unassigned User',
            'email' => 'unassigned@example.test',
            'password' => Hash::make('regular-user-password'),
            'is_admin' => false,
            'role' => null,
            'is_active' => true,
        ]);
        $this->actingAs($regularUser)
            ->get('/admin/chat')
            ->assertForbidden();
    }

    public function test_admin_user_can_login_and_reply_from_admin_chat(): void
    {
        $admin = $this->createAdmin();
        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'admin-test-password',
        ]);

        $response->assertRedirect(route('admin.home'));
        $this->get('/admin/chat')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/ChatIndexPage'));
    }

    public function test_admin_login_returns_to_an_intended_conversation_deep_link(): void
    {
        $customer = $this->createCustomer();
        $conversation = app(ChatConversationStarter::class)
            ->start($customer, 'dana_tunai')[0];
        $admin = $this->createAdmin();

        $this->get('/admin/chat/' . $conversation->id)
            ->assertRedirect('/admin/login');
        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'admin-test-password',
        ])->assertRedirect('/admin/chat/' . $conversation->id);
    }

    public function test_starter_validates_context_and_product_without_creating_messages(): void
    {
        $customer = $this->createCustomer();
        $this->withSession(['customer_id' => $customer->id]);
        $this->post('/customer/chat/start', ['context_type' => 'other'])
            ->assertSessionHasErrors('context_type');
        $this->post('/customer/chat/start', ['context_type' => 'motor'])
            ->assertSessionHasErrors('motor_product_id');
        $this->post('/customer/chat/start', ['context_type' => 'dana_tunai', 'motor_product_id' => 999])
            ->assertSessionHasErrors('motor_product_id');
        $this->assertDatabaseCount('chat_conversations', 0);
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_closed_conversation_is_preserved_and_new_interest_starts_a_new_one(): void
    {
        $customer = $this->createCustomer();
        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai']);
        $closed = ChatConversation::firstOrFail();
        $closed->update(['status' => 'closed']);
        $this->post('/customer/chat/' . $closed->id . '/messages', ['body' => 'Tidak boleh'])
            ->assertNotFound();
        $this->post('/customer/chat/start', ['context_type' => 'dana_tunai'])
            ->assertRedirect('/customer/chat/' . ChatConversation::latest('id')->firstOrFail()->id);
        $this->assertDatabaseCount('chat_conversations', 2);
        $this->assertDatabaseCount('chat_messages', 2);
        $this->assertSame('closed', $closed->fresh()->status);
    }

    public function test_customer_list_contains_both_contexts_in_latest_message_order_only_for_owner(): void
    {
        $customer = $this->createCustomer();
        $other = $this->createCustomer();
        $motor = MotorProduct::create([
            'code' => 'ORDER-MOTOR', 'name' => 'Vario 125 CBS', 'family' => 'Vario', 'status' => 'active',
        ]);
        $this->withSession(['customer_id' => $other->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai']);
        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'motor', 'motor_product_id' => $motor->id]);
        $this->travel(1)->minutes();
        $this->post('/customer/chat/start', ['context_type' => 'dana_tunai']);
        $this->get('/customer/chat')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('conversations', 2)
            ->where('conversations.0.contextType', 'dana_tunai')
            ->where('conversations.1.contextType', 'motor'));
        $this->travelBack();
    }

    public function test_guests_cannot_start_or_access_chat_and_customer_cannot_reply_as_admin(): void
    {
        $this->post('/customer/chat/start', ['context_type' => 'dana_tunai'])->assertRedirect('/');
        $this->get('/customer/chat')->assertRedirect('/');
        $owner = $this->createCustomer();
        $this->withSession(['customer_id' => $owner->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai']);
        $conversation = ChatConversation::firstOrFail();
        $this->get('/admin/chat/' . $conversation->id)->assertRedirect('/admin/login');
        $this->post('/admin/chat/' . $conversation->id . '/messages', ['body' => 'Tidak berhak'])
            ->assertRedirect('/admin/login');
        $this->withSession(['customer_id' => null])
            ->get('/customer/chat/' . $conversation->id)->assertRedirect('/');
        $this->post('/customer/chat/' . $conversation->id . '/messages', ['body' => 'Tidak berhak'])
            ->assertRedirect('/');
        $this->assertDatabaseCount('chat_messages', 1);
    }

    public function test_invalid_messages_and_forged_sender_do_not_bypass_customer_identity(): void
    {
        $customer = $this->createCustomer();
        Log::spy();
        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai']);
        $conversation = ChatConversation::firstOrFail();
        $url = '/customer/chat/' . $conversation->id . '/messages';
        $this->post($url, ['body' => '   '])->assertSessionHasErrors('body');
        $this->post($url, ['body' => str_repeat('x', 5001)])->assertSessionHasErrors('body');
        $this->assertDatabaseCount('chat_messages', 1);
        $this->post($url, [
            'body' => 'Pesan privat tidak boleh masuk reminder.',
            'sender_type' => 'admin', 'sender_id' => 999,
        ])->assertRedirect('/customer/chat/' . $conversation->id);
        $this->assertDatabaseHas('chat_messages', [
            'sender_type' => 'customer', 'sender_id' => $customer->id,
            'body' => 'Pesan privat tidak boleh masuk reminder.',
        ]);
        Log::shouldHaveReceived('info')
            ->with('WhatsApp reminder skipped because WHATSAPP_ENABLED=false.', Mockery::on(
                fn (array $context): bool => !array_key_exists('message', $context)
                    && isset($context['event_type'], $context['recipient'])
            ))
            ->times(2);
    }

    public function test_chat_server_errors_never_expose_internal_details_even_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        $customer = $this->createCustomer();
        $this->mock(ChatConversationStarter::class, function ($mock) {
            $mock->shouldReceive('start')->once()
                ->andThrow(new \RuntimeException('Internal implementation details must stay private'));
        });

        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai'])
            ->assertStatus(500)
            ->assertDontSee('Internal implementation details must stay private')
            ->assertDontSee('stacktrace')
            ->assertInertia(fn (Assert $page) => $page
                ->component('ChatErrorPage')
                ->where('message', 'Chat belum dapat dimuat. Silakan coba lagi nanti.'));
    }

    private function createCustomer(): Customer
    {
        $sequence = ++$this->customerSequence;
        $region = Region::create([
            'code' => 'test-region-' . $sequence,
            'name' => 'Wilayah Test ' . $sequence,
            'type' => 'city',
        ]);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'test-district-' . $sequence,
            'name' => 'Kecamatan Test ' . $sequence,
        ]);
        $village = Village::create([
            'district_id' => $district->id,
            'code' => 'test-village-' . $sequence,
            'name' => 'Kelurahan Test ' . $sequence,
            'type' => 'urban_village',
            'postal_code' => '11160',
        ]);

        return Customer::create([
            'full_name' => 'Customer Test ' . $sequence,
            'phone' => sprintf('08123456%04d', $sequence),
            'region_id' => $region->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => $village->postal_code,
            'street_address' => 'Jalan Test ' . $sequence,
        ]);
    }

    private function createAdmin(bool $isAdmin = true): User
    {
        return User::forceCreate([
            'name' => 'Admin Test',
            'email' => ($isAdmin ? 'admin' : 'user') . $this->customerSequence . '@example.test',
            'password' => Hash::make('admin-test-password'),
            'is_admin' => $isAdmin,
            'role' => $isAdmin ? 'admin' : 'staff',
            'is_active' => true,
        ]);
    }
}
