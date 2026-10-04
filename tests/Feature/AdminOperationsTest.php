<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\DanaTunaiApplication;
use App\Models\District;
use App\Models\MotorBrochure;
use App\Models\MotorBrochureItem;
use App\Models\MotorFinancingOption;
use App\Models\MotorProduct;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppNotification;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_kpis_use_database_values_and_custom_period_filters(): void
    {
        $customer = $this->customer('Period Customer');
        $oldCustomer = $this->customer('Old Customer');
        $oldCustomer->forceFill(['created_at' => now()->subDays(20), 'updated_at' => now()->subDays(20)])->save();
        $motor = $this->motor();
        $application = $this->creditApplication($customer, $motor, 'under_review');
        $this->danaApplication($customer, 'submitted');
        $conversation = $this->conversation($customer, 'motor', $motor);
        $conversation->messages()->create([
            'sender_type' => 'customer', 'sender_id' => $customer->id, 'body' => 'Pertanyaan motor',
        ]);
        Storage::fake('chat_private');
        Storage::disk('chat_private')->put('documents/' . $customer->id . '/11111111-1111-4111-8111-111111111111.pdf', '%PDF-1.4');
        CustomerDocument::create([
            'customer_id' => $customer->id, 'document_type' => 'identity',
            'original_name' => 'identity.pdf', 'disk' => 'chat_private',
            'path' => 'documents/' . $customer->id . '/11111111-1111-4111-8111-111111111111.pdf',
            'mime_type' => 'application/pdf', 'size' => 8, 'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);
        foreach (['081315379598', '081770643439'] as $recipient) {
            WhatsAppNotification::create([
                'event_type' => 'new_credit_application',
                'recipient' => $recipient,
                'message_type' => 'template',
                'reference_type' => 'credit_application',
                'reference_id' => (string) $application->id,
                'idempotency_key' => hash('sha256', $application->id . '|' . $recipient),
                'payload' => [
                    'customer_id' => $customer->id,
                    'application_id' => $application->id,
                    'motor_product_id' => $motor->id,
                    'variables' => [
                        'customer_name' => $customer->full_name,
                        'customer_phone' => $customer->phone,
                        'service_type' => 'Kredit Motor Honda',
                        'activity_summary' => 'Pengajuan menunggu tindak lanjut.',
                    ],
                ],
                'customer_id' => $customer->id,
                'application_id' => $application->id,
                'motor_product_id' => $motor->id,
                'status' => 'queued',
            ]);
        }

        $this->actingAs($this->admin())->get('/admin?period=custom&from=' . now()->toDateString() . '&to=' . now()->toDateString())
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/IndexPage')
                ->where('kpis.customers.total', 2)
                ->where('kpis.customers.inPeriod', 1)
                ->where('kpis.motors.active', 1)
                ->where('kpis.motors.interestInPeriod', 1)
                ->where('kpis.creditApplications.totalInPeriod', 1)
                ->where('kpis.creditApplications.byStatus.under_review', 1)
                ->where('kpis.creditApplications.pendingReview', 1)
                ->where('kpis.danaTunai.totalInPeriod', 1)
                ->where('kpis.chat.open', 1)
                ->where('kpis.chat.unread', 1)
                ->where('kpis.whatsapp.queued', 2)
                ->where('kpis.whatsapp.sent', 0)
                ->where('kpis.whatsapp.failed', 0)
                ->where('kpis.documents.waitingReview', 1));

        $this->actingAs($this->admin())->get('/admin?period=custom&from=2030-01-02&to=2030-01-01')
            ->assertSessionHasErrors('to');
        $this->assertNotNull($customer);
    }

    public function test_customer_list_search_region_and_detail_expose_related_data_without_internal_paths(): void
    {
        $customer = $this->customer('Siti Admin List');
        $motor = $this->motor();
        $application = $this->creditApplication($customer, $motor, 'submitted');
        $dana = $this->danaApplication($customer, 'draft');
        $conversation = $this->conversation($customer, 'motor', $motor);
        $conversation->messages()->create(['sender_type' => 'customer', 'sender_id' => $customer->id, 'body' => 'Halo']);
        Storage::fake('chat_private');
        $path = 'documents/' . $customer->id . '/22222222-2222-4222-8222-222222222222.pdf';
        Storage::disk('chat_private')->put($path, '%PDF-1.4');
        CustomerDocument::create([
            'customer_id' => $customer->id, 'credit_application_id' => $application->id,
            'document_type' => 'identity', 'original_name' => 'ktp.pdf', 'disk' => 'chat_private',
            'path' => $path, 'mime_type' => 'application/pdf', 'size' => 8, 'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($this->staff())
            ->get('/admin/customers?search=Siti&region_id=' . $customer->region_id)
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Siti Admin List')
                ->where('customers.data.0.creditApplicationCount', 1)
                ->where('customers.data.0.danaTunaiApplicationCount', 1)
                ->where('customers.data.0.conversationCount', 1));
        $this->actingAs($this->staff())->get('/admin/customers/' . $customer->id)
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/customers/ShowPage')
                ->where('customer.phone', $customer->phone)
                ->where('creditApplications.data.0.id', $application->id)
                ->where('danaTunaiApplications.data.0.id', $dana->id)
                ->where('conversations.data.0.id', $conversation->id)
                ->where('documents.data.0.downloadUrl', route('admin.documents.show', 1))
                ->missing('documents.data.0.path'));
    }

    public function test_customer_list_and_detail_paginate_and_validate_date_ranges(): void
    {
        $customer = $this->customer('Paginated Customer');
        for ($index = 0; $index < 25; $index++) {
            $this->customer('Customer Page ' . $index);
        }

        $this->actingAs($this->staff())->get('/admin/customers?page=2&from=' . now()->toDateString() . '&to=' . now()->toDateString())
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('customers.current_page', 2)
                ->where('customers.per_page', 25)
                ->where('customers.total', 26)
                ->has('customers.data', 1));
        $this->actingAs($this->staff())->get('/admin/customers?from=2030-01-02&to=2030-01-01')
            ->assertSessionHasErrors('to');

        $motor = $this->motor();
        for ($index = 0; $index < 11; $index++) {
            $this->creditApplication($customer, $motor, 'submitted');
            $this->danaApplication($customer, 'submitted');
            CustomerDocument::create([
                'customer_id' => $customer->id, 'document_type' => 'identity',
                'original_name' => 'document-' . $index . '.pdf', 'disk' => 'chat_private',
                'path' => 'documents/' . $customer->id . '/document-' . $index . '.pdf',
                'mime_type' => 'application/pdf', 'size' => 8, 'status' => 'uploaded',
                'uploaded_at' => now()->addSeconds($index),
            ]);
        }

        $this->actingAs($this->staff())->get('/admin/customers/' . $customer->id . '?credit_page=2&dana_page=2&documents_page=2')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('creditApplications.current_page', 2)
                ->where('creditApplications.total', 11)
                ->has('creditApplications.data', 1)
                ->where('danaTunaiApplications.current_page', 2)
                ->where('danaTunaiApplications.total', 11)
                ->has('danaTunaiApplications.data', 1)
                ->where('documents.current_page', 2)
                ->where('documents.total', 11)
                ->has('documents.data', 1));
    }

    public function test_motor_admin_list_filters_and_shows_real_brochure_financing_counts(): void
    {
        $motor = $this->motor('Vario 125 CBS', 'Vario');
        $brochure = MotorBrochure::create([
            'period_label' => 'Juni 2026', 'source_name' => 'Brosur Juni',
            'status' => 'active',
        ]);
        $item = MotorBrochureItem::create([
            'brochure_id' => $brochure->id, 'motor_product_id' => $motor->id, 'cash_price' => 26000000,
        ]);
        MotorFinancingOption::create([
            'brochure_item_id' => $item->id, 'down_payment_source_value' => 'DP official',
            'tenor_code' => '36', 'tenor_label' => '36 bulan',
            'installment_source_value' => null, 'is_available' => true,
        ]);
        $this->actingAs($this->admin())->get('/admin/motors?family=Vario&search=Vario')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/motors/IndexPage')
                ->has('motors.data', 1)
                ->where('motors.data.0.cashPrice', 26000000)
                ->where('motors.data.0.brochurePeriod', 'Juni 2026')
                ->where('motors.data.0.financingOptionCount', 1)
                ->where('brochures.0.items_count', 1)
                ->where('brochures.0.financing_options_count', 1));
        $this->actingAs($this->staff())->get('/admin/motors')->assertForbidden();
    }

    public function test_credit_application_filter_detail_and_status_history_are_database_backed(): void
    {
        $customer = $this->customer('Customer Credit');
        $motor = $this->motor('PCX 160', 'PCX');
        $application = $this->creditApplication($customer, $motor, 'submitted');
        $admin = $this->admin();
        $notificationRecipient = $this->staff();
        $this->actingAs($admin)->get('/admin/pengajuan?status=submitted&search=' . $application->application_number)
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/applications/IndexPage')
                ->has('applications.data', 1)
                ->where('applications.data.0.number', $application->application_number)
                ->where('applications.data.0.cashPrice', '27000000.00'));

        $this->actingAs($admin)->post('/admin/pengajuan/' . $application->id . '/status', [
            'status' => 'under_review', 'note' => 'Dokumen mulai diperiksa.',
        ])->assertRedirect();
        $this->assertDatabaseHas('application_status_histories', [
            'credit_application_id' => $application->id,
            'from_status' => 'submitted',
            'to_status' => 'under_review',
            'changed_by' => $admin->id,
            'note' => 'Dokumen mulai diperiksa.',
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'application.status_changed',
            'entity_type' => 'credit_application',
            'entity_id' => (string) $application->id,
        ]);
        $this->assertDatabaseHas('admin_notifications', [
            'recipient_admin_id' => $notificationRecipient->id,
            'type' => 'application_status_change',
        ]);
        $this->actingAs($admin)->get('/admin/pengajuan/' . $application->id)
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/applications/ShowPage')
                ->where('application.status', 'under_review')
                ->where('application.history.0.changedBy', $admin->name)
                ->where('application.history.0.note', 'Dokumen mulai diperiksa.'));
        $this->actingAs($admin)->post('/admin/pengajuan/' . $application->id . '/status', [
            'status' => 'not-a-status',
        ])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post('/admin/pengajuan/' . $application->id . '/status', [
            'status' => 'under_review',
        ])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('application_status_histories', 1);
    }

    public function test_dana_tunai_admin_status_has_history_and_never_invents_financial_values(): void
    {
        $customer = $this->customer('Customer Dana');
        $application = $this->danaApplication($customer, 'submitted');
        $conversation = $this->conversation($customer, 'dana_tunai');
        $application->update(['chat_conversation_id' => $conversation->id]);
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/dana-tunai?status=submitted')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/dana-tunai/IndexPage')
                ->has('applications.data', 1)
                ->where('applications.data.0.conversationId', $conversation->id));
        $this->actingAs($admin)->post('/admin/dana-tunai/' . $application->id . '/status', [
            'status' => 'need_documents', 'note' => 'Menunggu dokumen pendukung.',
        ])->assertRedirect();
        $this->assertDatabaseHas('dana_tunai_status_histories', [
            'dana_tunai_application_id' => $application->id,
            'from_status' => 'submitted',
            'to_status' => 'need_documents',
            'changed_by' => $admin->id,
        ]);
        $this->actingAs($admin)->get('/admin/dana-tunai/' . $application->id)
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/dana-tunai/ShowPage')
                ->where('application.status', 'need_documents')
                ->missing('application.amount')
                ->where('application.conversationId', $conversation->id));
    }

    public function test_chat_admin_search_context_status_unread_filter_and_pagination_use_queries(): void
    {
        $motorCustomer = $this->customer('Motor Search');
        $danaCustomer = $this->customer('Dana Search');
        $motor = $this->motor();
        $motorConversation = $this->conversation($motorCustomer, 'motor', $motor);
        $danaConversation = $this->conversation($danaCustomer, 'dana_tunai');
        $danaConversation->messages()->create([
            'sender_type' => 'customer', 'sender_id' => $danaCustomer->id, 'body' => 'Perlu dibalas',
        ]);
        $this->actingAs($this->staff())
            ->get('/admin/chat?context=dana_tunai&status=open&unread=1&search=Dana')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/ChatIndexPage')
                ->has('conversations.data', 1)
                ->where('conversations.data.0.id', $danaConversation->id)
                ->where('conversations.data.0.unreadCount', 1)
                ->where('filters.context', 'dana_tunai'));
        $this->actingAs($this->staff())->get('/admin/chat?context=motor&unread=1')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->has('conversations.data', 0));
        $this->assertNotSame($motorConversation->id, $danaConversation->id);
    }

    public function test_application_and_customer_routes_deny_customer_session_and_unassigned_users(): void
    {
        $this->withSession(['customer_id' => 1])->get('/admin/customers')->assertRedirect('/admin/login');
        $this->withSession(['customer_id' => 1])->get('/admin/pengajuan')->assertRedirect('/admin/login');
        $unassigned = User::forceCreate([
            'name' => 'No Role', 'email' => 'no-role@example.test',
            'password' => Hash::make('password'), 'is_admin' => false,
            'role' => null, 'is_active' => true,
        ]);
        $this->actingAs($unassigned)->get('/admin/customers')->assertForbidden();
        $this->actingAs($this->staff())->get('/admin/customers')->assertOk();
    }

    public function test_private_application_document_download_requires_staff_and_never_serializes_storage_path(): void
    {
        $customer = $this->customer('Customer Document');
        Storage::fake('chat_private');
        $path = 'documents/' . $customer->id . '/33333333-3333-4333-8333-333333333333.pdf';
        Storage::disk('chat_private')->put($path, "%PDF-1.4\n");
        $document = CustomerDocument::create([
            'customer_id' => $customer->id, 'document_type' => 'bpkb',
            'original_name' => 'bpkb.pdf', 'disk' => 'chat_private', 'path' => $path,
            'mime_type' => 'application/pdf', 'size' => 9, 'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);
        $this->actingAs($this->staff())->get('/admin/documents?status=uploaded')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/documents/IndexPage')
                ->where('documents.data.0.filename', 'bpkb.pdf')
                ->missing('documents.data.0.path')
                ->missing('documents.data.0.disk'));
        $this->actingAs($this->staff())->get('/admin/documents/' . $document->id)
            ->assertOk()->assertDownload('bpkb.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        Auth::logout();
        $this->withSession(['customer_id' => $customer->id])
            ->get('/admin/documents/' . $document->id)->assertRedirect('/admin/login');
    }

    private function customer(string $name): Customer
    {
        static $sequence = 0;
        $sequence++;
        $region = Region::create(['code' => 'admin-region-' . $sequence, 'name' => 'Jakarta Test ' . $sequence, 'type' => 'city']);
        $district = District::create(['region_id' => $region->id, 'code' => 'admin-district-' . $sequence, 'name' => 'District ' . $sequence]);
        $village = Village::create(['district_id' => $district->id, 'code' => 'admin-village-' . $sequence, 'name' => 'Village ' . $sequence, 'type' => 'urban_village', 'postal_code' => '11160']);
        return Customer::create([
            'full_name' => $name, 'phone' => '0813000' . str_pad((string) $sequence, 7, '0', STR_PAD_LEFT),
            'region_id' => $region->id, 'district_id' => $district->id, 'village_id' => $village->id,
            'postal_code' => '11160', 'street_address' => 'Jalan Admin Test',
        ]);
    }

    private function motor(string $name = 'Vario 125 CBS', string $family = 'Vario'): MotorProduct
    {
        static $sequence = 0;
        $sequence++;
        return MotorProduct::create([
            'code' => 'ADMIN-MOTOR-' . $sequence, 'name' => $name,
            'family' => $family, 'status' => 'active',
        ]);
    }

    private function creditApplication(Customer $customer, MotorProduct $motor, string $status): CreditApplication
    {
        static $sequence = 0;
        $sequence++;
        return CreditApplication::create([
            'application_number' => 'AFH-CR-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id, 'motor_product_id' => $motor->id,
            'cash_price' => '27000000.00', 'down_payment' => null,
            'tenor_months' => null, 'installment_amount' => null,
            'status' => $status, 'source' => 'website', 'submitted_at' => now(),
        ]);
    }

    private function danaApplication(Customer $customer, string $status): DanaTunaiApplication
    {
        static $sequence = 0;
        $sequence++;
        return DanaTunaiApplication::create([
            'application_number' => 'AFH-DT-' . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id, 'status' => $status,
            'source' => 'website', 'submitted_at' => now(),
        ]);
    }

    private function conversation(Customer $customer, string $context, ?MotorProduct $motor = null): ChatConversation
    {
        return ChatConversation::create([
            'customer_id' => $customer->id, 'context_type' => $context,
            'motor_product_id' => $motor?->id, 'status' => 'open', 'last_message_at' => now(),
        ]);
    }

    private function admin(): User
    {
        return User::forceCreate([
            'name' => 'Operations Admin',
            'email' => 'operations-admin-' . User::count() . '@example.test',
            'password' => Hash::make('operations-test-password'),
            'is_admin' => false, 'role' => 'admin', 'is_active' => true,
        ]);
    }

    private function staff(): User
    {
        return User::forceCreate([
            'name' => 'Operations Staff',
            'email' => 'operations-staff-' . User::count() . '@example.test',
            'password' => Hash::make('operations-test-password'),
            'is_admin' => false, 'role' => 'staff', 'is_active' => true,
        ]);
    }
}
