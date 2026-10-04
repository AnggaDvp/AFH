<?php

namespace Tests\Feature;

use App\Models\CashLoanRequest;
use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\District;
use App\Models\Region;
use App\Models\User;
use App\Models\Village;
use App\Services\CashLoanRequestSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CashLoanRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_request_and_is_redirected_to_private_chat_with_auto_reply(): void
    {
        $customer = $this->customer('081234567890');
        $key = (string) Str::uuid();

        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/dana-tunai', $this->validPayload($key))
            ->assertRedirect('/customer/chat/1');

        $request = CashLoanRequest::query()->firstOrFail();
        $conversation = ChatConversation::query()->firstOrFail();
        $this->assertDatabaseHas('cash_loan_requests', [
            'customer_id' => $customer->id,
            'conversation_id' => $conversation->id,
            'nopol' => 'B 1234 ABC',
            'motor_brand' => 'honda',
            'stnk_name_type' => 'self_name',
            'house_ownership' => 'owned',
            'status' => 'submitted',
        ]);
        $this->assertSame('dana_tunai', $conversation->context_type);
        $this->assertNull($conversation->motor_product_id);
        $this->assertSame($customer->id, $conversation->customer_id);
        $this->assertSame($conversation->id, $request->conversation_id);
        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'system',
            'sender_id' => null,
            'body' => CashLoanRequestSubmission::AUTO_REPLY,
        ]);
        $this->assertDatabaseCount('whatsapp_notifications', 0);
        $this->assertDatabaseCount('jobs', 0);

        $this->withSession(['customer_id' => $customer->id])
            ->get('/customer/chat/' . $conversation->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer/ConversationPage')
                ->where('conversation.serviceLabel', 'Dana Tunai / Gadai BPKB')
                ->where('conversation.cashLoanRequest.nopol', 'B 1234 ABC')
                ->where('conversation.cashLoanRequest.motorBrand', 'Honda')
                ->where('conversation.cashLoanRequest.stnkName', 'Atas Nama Sendiri')
                ->where('conversation.cashLoanRequest.houseOwnership', 'Milik Sendiri')
                ->where('conversation.messages.0.senderType', 'system')
                ->where('conversation.messages.0.senderName', 'Affiyah')
                ->where('conversation.messages.0.body', CashLoanRequestSubmission::AUTO_REPLY));
    }

    public function test_customer_session_is_required_and_identity_cannot_be_forged_from_request_data(): void
    {
        $owner = $this->customer('081234567890');
        $other = $this->customer('081234567891');
        $payload = $this->validPayload((string) Str::uuid()) + ['customer_id' => $other->id];

        $this->post('/customer/dana-tunai', $payload)->assertRedirect('/');
        $this->assertDatabaseCount('cash_loan_requests', 0);

        $this->withSession(['customer_id' => $owner->id])
            ->post('/customer/dana-tunai', $payload)
            ->assertRedirect('/customer/chat/1');
        $this->assertDatabaseHas('cash_loan_requests', [
            'customer_id' => $owner->id,
            'nopol' => 'B 1234 ABC',
        ]);
        $this->assertDatabaseMissing('cash_loan_requests', ['customer_id' => $other->id]);
    }

    public function test_all_form_fields_are_validated_by_the_backend(): void
    {
        $customer = $this->customer('081234567890');
        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/dana-tunai', [
                'submission_key' => (string) Str::uuid(),
                'nopol' => '',
                'motor_brand' => 'suzuki',
                'stnk_name_type' => 'someone_else',
                'house_ownership' => 'mortgaged',
            ])
            ->assertSessionHasErrors([
                'nopol',
                'motor_brand',
                'stnk_name_type',
                'house_ownership',
            ]);

        $this->assertDatabaseCount('cash_loan_requests', 0);
        $this->assertDatabaseCount('chat_conversations', 0);
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_non_string_nopol_is_rejected(): void
    {
        $customer = $this->customer('081234567890');
        $payload = $this->validPayload((string) Str::uuid());
        $payload['nopol'] = 1234;

        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/dana-tunai', $payload)
            ->assertSessionHasErrors('nopol');

        $this->assertDatabaseCount('cash_loan_requests', 0);
    }

    public function test_unexpected_submit_failure_returns_a_human_error_without_exposing_details(): void
    {
        $customer = $this->customer('081234567890');
        Log::spy();
        $this->mock(CashLoanRequestSubmission::class, function ($mock): void {
            $mock->shouldReceive('submit')->once()
                ->andThrow(new \RuntimeException('SQLSTATE internal database detail'));
        });

        $this->withSession(['customer_id' => $customer->id])
            ->from('/customer/dana-tunai')
            ->post('/customer/dana-tunai', $this->validPayload((string) Str::uuid()))
            ->assertRedirect('/customer/dana-tunai')
            ->assertSessionHas('cash_loan_error', 'Data belum lengkap. Silakan periksa kembali.');

        $this->withSession(['customer_id' => $customer->id])
            ->get('/customer/dana-tunai')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('submissionError', 'Data belum lengkap. Silakan periksa kembali.'));
        $this->assertDatabaseCount('cash_loan_requests', 0);
    }

    public function test_submission_key_prevents_double_submit_and_a_new_key_allows_a_future_request_on_open_chat(): void
    {
        $customer = $this->customer('081234567890');
        $session = ['customer_id' => $customer->id];
        $firstPayload = $this->validPayload((string) Str::uuid());

        $this->withSession($session)->post('/customer/dana-tunai', $firstPayload)->assertRedirect();
        $conversation = ChatConversation::firstOrFail();
        $this->withSession($session)->post('/customer/dana-tunai', $firstPayload)
            ->assertRedirect('/customer/chat/' . $conversation->id);

        $this->assertDatabaseCount('cash_loan_requests', 1);
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseCount('chat_messages', 1);

        $secondPayload = $this->validPayload((string) Str::uuid());
        $secondPayload['motor_brand'] = 'yamaha';
        $secondPayload['stnk_name_type'] = 'different_name';
        $secondPayload['house_ownership'] = 'rented';
        $this->withSession($session)->post('/customer/dana-tunai', $secondPayload)
            ->assertRedirect('/customer/chat/' . $conversation->id);

        $this->assertDatabaseCount('cash_loan_requests', 2);
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseCount('chat_messages', 2);
        $this->assertDatabaseHas('cash_loan_requests', [
            'conversation_id' => $conversation->id,
            'motor_brand' => 'yamaha',
            'stnk_name_type' => 'different_name',
            'house_ownership' => 'rented',
        ]);
    }

    public function test_admin_chat_shows_request_context_and_other_customers_cannot_read_it(): void
    {
        $owner = $this->customer('081234567890');
        $other = $this->customer('081234567891');
        $this->withSession(['customer_id' => $owner->id])
            ->post('/customer/dana-tunai', $this->validPayload((string) Str::uuid()));
        $conversation = ChatConversation::firstOrFail();

        $this->withSession(['customer_id' => $other->id])
            ->get('/customer/chat/' . $conversation->id)
            ->assertNotFound();

        $admin = User::forceCreate([
            'name' => 'Dana Chat Admin',
            'email' => 'dana-chat-admin@example.test',
            'password' => Hash::make('test-password'),
            'is_admin' => false,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/chat/' . $conversation->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/ChatConversationPage')
                ->where('conversation.customerName', $owner->full_name)
                ->where('conversation.customerPhone', $owner->phone)
                ->where('conversation.cashLoanRequest.nopol', 'B 1234 ABC')
                ->where('conversation.cashLoanRequest.motorBrand', 'Honda')
                ->where('conversation.cashLoanRequest.stnkName', 'Atas Nama Sendiri')
                ->where('conversation.cashLoanRequest.houseOwnership', 'Milik Sendiri'));

        $this->actingAs($admin)
            ->post('/admin/chat/' . $conversation->id . '/messages', ['body' => 'Silakan sampaikan pertanyaan Anda.'])
            ->assertRedirect('/admin/chat/' . $conversation->id);
        $this->withSession(['customer_id' => $owner->id])
            ->get('/customer/chat/' . $conversation->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('conversation.messages', 2)
                ->where('conversation.messages.1.senderType', 'admin')
                ->where('conversation.messages.1.body', 'Silakan sampaikan pertanyaan Anda.'));
    }

    public function test_dana_tunai_page_provides_a_server_generated_submission_key(): void
    {
        $customer = $this->customer('081234567890');

        $this->withSession(['customer_id' => $customer->id])
            ->get('/customer/dana-tunai')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer/DanaTunaiPage')
                ->where('submissionKey', fn ($key) => is_string($key) && (bool) preg_match(
                    '/^[0-9a-f-]{36}$/i',
                    $key
                ))
                ->where('submissionError', null));
    }

    private function validPayload(string $submissionKey): array
    {
        return [
            'submission_key' => $submissionKey,
            'nopol' => 'b 1234 abc',
            'motor_brand' => 'honda',
            'stnk_name_type' => 'self_name',
            'house_ownership' => 'owned',
        ];
    }

    private function customer(string $phone): Customer
    {
        $sequence = Customer::count() + 1;
        $region = Region::create([
            'code' => 'cash-region-' . $sequence,
            'name' => 'Wilayah Cash ' . $sequence,
            'type' => 'city',
        ]);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'cash-district-' . $sequence,
            'name' => 'Kecamatan Cash ' . $sequence,
        ]);
        $village = Village::create([
            'district_id' => $district->id,
            'code' => 'cash-village-' . $sequence,
            'name' => 'Kelurahan Cash ' . $sequence,
            'type' => 'urban_village',
            'postal_code' => '11160',
        ]);

        return Customer::create([
            'full_name' => 'Customer Cash ' . $sequence,
            'phone' => $phone,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => $village->postal_code,
            'street_address' => 'Jalan Cash ' . $sequence,
        ]);
    }
}
