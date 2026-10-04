<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppProviderInterface;
use App\Jobs\SendWhatsAppInteractiveResponse;
use App\Jobs\SendWhatsAppReminder;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\DanaTunaiApplication;
use App\Models\District;
use App\Models\MotorProduct;
use App\Models\Region;
use App\Models\Village;
use App\Models\WhatsAppNotification;
use App\Services\BirdWhatsAppProvider;
use App\Services\WhatsAppReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class WhatsAppRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'whatsapp.enabled' => false,
            'whatsapp.provider' => 'bird',
            'whatsapp.bird.api_key' => 'test-api-key',
            'whatsapp.bird.region' => 'eu1',
            'whatsapp.bird.whatsapp_number_id' => 'test-number-id',
            'whatsapp.bird.webhook_secret' => 'whsec_' . base64_encode('test-webhook-secret'),
            'whatsapp.bird.webhook_tolerance' => 300,
            'whatsapp.sender_phone' => '081770643439',
            'whatsapp.reminder_template_name' => 'test_approved_template',
            'whatsapp.reminder_template_language' => 'id_ID',
            'whatsapp.recipients' => ['081315379598'],
            'whatsapp.queue_connection' => 'database',
            'whatsapp.queue_name' => 'whatsapp',
            'whatsapp.deep_link_enabled' => false,
        ]);
    }

    public function test_expected_sender_recipient_and_provider_are_configured(): void
    {
        $this->assertSame('bird', config('whatsapp.provider'));
        $this->assertSame('081770643439', config('whatsapp.sender_phone'));
        $this->assertSame(['081315379598'], config('whatsapp.recipients'));
        $this->assertInstanceOf(BirdWhatsAppProvider::class, app(WhatsAppProviderInterface::class));
    }

    public function test_disabled_mode_records_idempotent_intent_without_network_or_queue_job(): void
    {
        [$customer, $conversation] = $this->customerAndConversation();
        config(['whatsapp.enabled' => false]);
        Http::fake();
        Log::spy();

        $service = app(WhatsAppReminderService::class);
        $this->assertSame(1, $service->queueDanaTunaiInterest($conversation));
        $this->assertSame(0, $service->queueDanaTunaiInterest($conversation));

        $this->assertDatabaseCount('whatsapp_notifications', 1);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_dana_tunai_interest',
            'reference_id' => (string) $conversation->id,
            'status' => 'queued',
        ]);
        Log::shouldHaveReceived('info')
            ->once()
            ->with('WhatsApp reminder skipped because WHATSAPP_ENABLED=false.', Mockery::on(
                fn (array $context): bool => $context['event_type'] === 'new_dana_tunai_interest'
            ));
        Http::assertNothingSent();
        $this->assertSame($customer->id, $conversation->customer_id);
    }

    public function test_enabled_mode_dispatches_database_queue_jobs_with_unique_event_keys(): void
    {
        [, $conversation] = $this->customerAndConversation();
        config(['whatsapp.enabled' => true]);
        Bus::fake();

        $service = app(WhatsAppReminderService::class);
        $service->queueMotorInterest($conversation);
        $service->queueMotorInterest($conversation);

        $this->assertDatabaseCount('whatsapp_notifications', 1);
        Bus::assertDispatched(SendWhatsAppReminder::class, 1);
        Bus::assertDispatched(SendWhatsAppReminder::class, fn (SendWhatsAppReminder $job): bool =>
            $job->connection === 'database' && $job->queue === 'whatsapp'
        );
        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_motor_interest',
            'recipient' => '081315379598',
        ]);
    }

    public function test_sync_queue_configuration_is_logged_as_enqueue_failure(): void
    {
        [, $conversation] = $this->customerAndConversation();
        config([
            'whatsapp.enabled' => true,
            'whatsapp.queue_connection' => 'sync',
        ]);

        app(WhatsAppReminderService::class)->queueMotorInterest($conversation);

        $this->assertDatabaseCount('whatsapp_notifications', 1);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_motor_interest',
            'status' => 'failed',
            'error_message' => 'Reminder tidak dapat dimasukkan ke antrean.',
        ]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'whatsapp.reminder_enqueue_failed',
        ]);
    }

    public function test_missing_template_configuration_never_dispatches_a_job(): void
    {
        [, $conversation] = $this->customerAndConversation();
        config([
            'whatsapp.enabled' => true,
            'whatsapp.reminder_template_name' => '',
        ]);
        Bus::fake();

        app(WhatsAppReminderService::class)->queueMotorInterest($conversation);

        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_motor_interest',
            'status' => 'failed',
            'error_message' => 'Konfigurasi WhatsApp atau template belum lengkap.',
        ]);
        Bus::assertNotDispatched(SendWhatsAppReminder::class);
    }

    public function test_credit_application_triggers_reminder_but_dana_application_creation_does_not(): void
    {
        [$customer, , $motor] = $this->customerAndConversation();

        CreditApplication::create([
            'application_number' => 'AFH-CR-WA-1',
            'customer_id' => $customer->id,
            'motor_product_id' => $motor->id,
            'status' => 'submitted',
            'source' => 'website',
        ]);
        $this->assertDatabaseCount('whatsapp_notifications', 1);
        $this->assertDatabaseHas('whatsapp_notifications', [
            'event_type' => 'new_credit_application',
            'reference_type' => 'credit_application',
            'reference_id' => (string) CreditApplication::firstOrFail()->id,
        ]);

        DanaTunaiApplication::create([
            'application_number' => 'AFH-DT-WA-1',
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'source' => 'website',
        ]);
        $this->assertDatabaseCount('whatsapp_notifications', 1);
    }

    public function test_need_documents_transition_creates_a_safe_reminder(): void
    {
        [$customer, , $motor] = $this->customerAndConversation();
        $application = CreditApplication::create([
            'application_number' => 'AFH-CR-WA-DOC',
            'customer_id' => $customer->id,
            'motor_product_id' => $motor->id,
            'status' => 'under_review',
            'source' => 'website',
        ]);

        $application->update(['status' => 'need_documents']);

        $this->assertDatabaseCount('whatsapp_notifications', 1);
        $notification = WhatsAppNotification::query()
            ->where('event_type', 'need_documents')
            ->firstOrFail();
        $this->assertSame($application->id, $notification->application_id);
        $this->assertStringContainsString('Customer perlu melengkapi dokumen', $notification->payload['variables']['activity_summary']);
        $this->assertStringNotContainsString('KTP', json_encode($notification->payload['variables']) ?: '');
    }

    public function test_job_marks_success_and_sends_only_customer_summary(): void
    {
        [$customer, $conversation, $motor] = $this->customerAndConversation();
        $conversation->update([
            'context_type' => 'motor',
            'motor_product_id' => $motor->id,
        ]);
        $message = $conversation->messages()->create([
            'sender_type' => 'customer',
            'sender_id' => $customer->id,
            'body' => 'Pesan rahasia yang tidak boleh dikirim.',
        ]);
        $service = app(WhatsAppReminderService::class);
        $service->queueNewChatMessage($message);
        $notification = WhatsAppNotification::query()
            ->where('event_type', 'new_chat_message')
            ->firstOrFail();
        config(['whatsapp.enabled' => true]);

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('sendTemplateMessage')
            ->once()
            ->with(
                '081315379598',
                'test_approved_template',
                'id_ID',
                [
                    'customer_name' => $customer->full_name,
                    'service_type' => 'Motor',
                    'customer_phone' => $customer->phone,
                    'activity_summary' => 'Customer mengirim pesan baru di chat. Internal chat tersedia.',
                ]
            )
            ->andReturn('wamid.test-success');

        (new SendWhatsAppReminder($notification->id))->handle($provider, $service);

        $notification->refresh();
        $this->assertSame('sent', $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertSame('wamid.test-success', $notification->provider_message_id);
        $this->assertNotNull($notification->sent_at);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'whatsapp.reminder_sent',
            'entity_id' => (string) $notification->id,
        ]);
    }

    public function test_failed_job_records_safe_error_after_retries_are_exhausted(): void
    {
        [, $conversation] = $this->customerAndConversation();
        $service = app(WhatsAppReminderService::class);
        $service->queueDanaTunaiInterest($conversation);
        $notification = WhatsAppNotification::query()->firstOrFail();
        config(['whatsapp.enabled' => true]);
        $exception = new RuntimeException('access_token=must-not-be-stored');
        Log::spy();
        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('sendTemplateMessage')->once()->andThrow($exception);
        $job = new SendWhatsAppReminder($notification->id);

        try {
            $job->handle($provider, $service);
            $this->fail('Provider exception should trigger the queue retry.');
        } catch (RuntimeException $caught) {
            $job->failed($caught);
        }

        $notification->refresh();
        $this->assertSame('failed', $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertSame('Pengiriman WhatsApp gagal setelah beberapa percobaan.', $notification->error_message);
        $this->assertStringNotContainsString('access_token', $notification->error_message);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'whatsapp.reminder_failed',
            'entity_id' => (string) $notification->id,
        ]);
        Log::shouldHaveReceived('warning')->with(
            'WhatsApp reminder attempt failed.',
            Mockery::on(fn (array $context): bool =>
                !str_contains(json_encode($context) ?: '', 'must-not-be-stored')
            )
        );
    }

    public function test_retry_attempt_can_recover_a_stale_sending_notification(): void
    {
        [, $conversation] = $this->customerAndConversation();
        app(WhatsAppReminderService::class)->queueDanaTunaiInterest($conversation);
        $notification = WhatsAppNotification::query()->firstOrFail();
        config(['whatsapp.enabled' => true]);
        \Illuminate\Support\Facades\DB::table('whatsapp_notifications')
            ->where('id', $notification->id)
            ->update(['status' => 'sending', 'updated_at' => now()->subSeconds(120)]);

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('sendTemplateMessage')->once()->andReturn('wamid.recovered');
        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob->shouldReceive('attempts')->andReturn(2);
        $job = (new SendWhatsAppReminder($notification->id))->setJob($queueJob);

        $job->handle($provider, app(WhatsAppReminderService::class));

        $notification->refresh();
        $this->assertSame('sent', $notification->status);
        $this->assertSame(1, $notification->attempts);
    }

    public function test_retry_attempt_does_not_claim_an_active_sending_notification(): void
    {
        [, $conversation] = $this->customerAndConversation();
        app(WhatsAppReminderService::class)->queueDanaTunaiInterest($conversation);
        $notification = WhatsAppNotification::query()->firstOrFail();
        config(['whatsapp.enabled' => true]);
        \Illuminate\Support\Facades\DB::table('whatsapp_notifications')
            ->where('id', $notification->id)
            ->update(['status' => 'sending', 'updated_at' => now()]);

        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldNotReceive('sendTemplateMessage');
        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob->shouldReceive('attempts')->andReturn(2);
        $job = (new SendWhatsAppReminder($notification->id))->setJob($queueJob);

        $job->handle($provider, app(WhatsAppReminderService::class));

        $notification->refresh();
        $this->assertSame('sending', $notification->status);
        $this->assertSame(0, $notification->attempts);
    }

    public function test_bird_provider_sends_configured_template_sender_and_normalizes_numbers(): void
    {
        config([
            'whatsapp.bird.api_key' => 'configured-token',
            'whatsapp.bird.region' => 'eu1',
            'whatsapp.sender_phone' => '081770643439',
        ]);
        Http::fake([
            'eu1.platform.bird.com/*' => Http::response(['id' => 'wamid.bird'], 202),
        ]);

        $provider = app(BirdWhatsAppProvider::class);
        $messageId = $provider->sendTemplateMessage(
            '081315379598',
            'approved_reminder_name',
            'id_ID',
            [
                'customer_name' => 'Nama Customer',
                'service_type' => 'Motor Honda',
                'customer_phone' => '081234567890',
                'activity_summary' => 'Aktivitas baru',
            ]
        );

        $this->assertSame('+6281315379598', $provider->normalizePhoneNumber('081315379598'));
        $this->assertSame('wamid.bird', $messageId);
        Http::assertSent(fn ($request): bool =>
            $request->url() === 'https://eu1.platform.bird.com/v1/whatsapp/messages'
            && $request['to'] === '+6281315379598'
            && $request['from'] === '+6281770643439'
            && $request['template']['slug'] === 'approved_reminder_name'
            && $request['template']['language'] === 'id_ID'
            && $request['template']['components'][0]['parameters'][0]['name'] === 'customer_name'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'Nama Customer'
            && count($request['template']['components']) === 1
            && $request->hasHeader('Authorization', 'Bearer configured-token')
        );
    }

    public function test_deep_link_uses_named_route_and_only_becomes_public_on_https_url(): void
    {
        [, $conversation] = $this->customerAndConversation();
        app(WhatsAppReminderService::class)->queueDanaTunaiInterest($conversation);
        $notification = WhatsAppNotification::query()->firstOrFail();
        $service = app(WhatsAppReminderService::class);

        config(['app.url' => 'http://localhost:8000', 'whatsapp.deep_link_enabled' => true]);
        $this->assertSame(
            'http://localhost:8000/admin/chat/' . $conversation->id,
            $service->adminDeepLinkFor($notification)
        );
        $this->assertNull($service->publicAdminDeepLinkFor($notification));

        config(['app.url' => 'https://agencyfinance.example']);
        $this->assertSame(
            'https://agencyfinance.example/admin/chat/' . $conversation->id,
            $service->publicAdminDeepLinkFor($notification)
        );
    }

    public function test_bird_webhook_rejects_an_invalid_signature(): void
    {
        $body = json_encode($this->interactiveReplyEvent('open_admin'), JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/webhooks/bird/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_WEBHOOK_ID' => 'webhook-invalid',
            'HTTP_WEBHOOK_TIMESTAMP' => (string) now()->timestamp,
            'HTTP_WEBHOOK_SIGNATURE' => 'v1,invalid',
        ], $body)->assertUnauthorized();

        $this->assertDatabaseCount('admin_activity_logs', 0);
    }

    public function test_open_admin_reply_is_correlated_deduplicated_and_queued(): void
    {
        [, $conversation] = $this->customerAndConversation();
        app(WhatsAppReminderService::class)->queueDanaTunaiInterest($conversation);
        $notification = WhatsAppNotification::query()->firstOrFail();
        $notification->forceFill([
            'status' => 'sent',
            'provider_message_id' => 'wamid.original-reminder',
            'sent_at' => now(),
        ])->save();
        config([
            'whatsapp.enabled' => true,
            'whatsapp.deep_link_enabled' => true,
            'app.url' => 'https://agencyfinance.example',
        ]);
        Bus::fake();

        $event = $this->interactiveReplyEvent('open_admin');
        $this->postSignedBirdWebhook($event, 'webhook-open-admin')
            ->assertOk()
            ->assertJson(['status' => 'handled']);
        $this->postSignedBirdWebhook($event, 'webhook-open-admin')
            ->assertOk()
            ->assertJson(['status' => 'duplicate']);

        $this->assertSame(1, \App\Models\AdminActivityLog::query()
            ->where('action', 'whatsapp.interactive.open_admin')
            ->count());
        Bus::assertDispatched(SendWhatsAppInteractiveResponse::class, 1);
        Bus::assertDispatched(SendWhatsAppInteractiveResponse::class, fn ($job): bool =>
            $job->notificationId === $notification->id
            && $job->connection === 'database'
            && $job->queue === 'whatsapp'
        );
    }

    public function test_reply_customer_reply_marks_intent_without_creating_another_conversation(): void
    {
        [, $conversation] = $this->customerAndConversation();
        app(WhatsAppReminderService::class)->queueDanaTunaiInterest($conversation);
        $notification = WhatsAppNotification::query()->firstOrFail();
        $notification->forceFill([
            'status' => 'sent',
            'provider_message_id' => 'wamid.original-reminder',
            'sent_at' => now(),
        ])->save();
        Bus::fake();

        $this->postSignedBirdWebhook(
            $this->interactiveReplyEvent('reply_customer'),
            'webhook-reply-customer'
        )->assertOk()->assertJson(['status' => 'handled']);

        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'whatsapp.interactive.reply_customer',
            'entity_type' => 'whatsapp_notification',
            'entity_id' => (string) $notification->id,
        ]);
        $this->assertDatabaseCount('chat_conversations', 1);
        Bus::assertNotDispatched(SendWhatsAppInteractiveResponse::class);
    }

    public function test_open_admin_response_sends_a_protected_admin_url_inside_the_service_window(): void
    {
        [, $conversation] = $this->customerAndConversation();
        app(WhatsAppReminderService::class)->queueDanaTunaiInterest($conversation);
        $notification = WhatsAppNotification::query()->firstOrFail();
        config([
            'whatsapp.enabled' => true,
            'whatsapp.deep_link_enabled' => true,
            'app.url' => 'https://agencyfinance.example',
        ]);
        $provider = Mockery::mock(WhatsAppProviderInterface::class);
        $provider->shouldReceive('sendTextMessage')
            ->once()
            ->with(
                '081315379598',
                'Buka WEB Admin untuk menangani customer: https://agencyfinance.example/admin/chat/'
                    . $conversation->id
            )
            ->andReturn('wamid.admin-link');

        (new SendWhatsAppInteractiveResponse($notification->id))->handle(
            $provider,
            app(WhatsAppReminderService::class)
        );

        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'whatsapp.interactive.admin_link_sent',
            'entity_id' => (string) $notification->id,
        ]);
    }

    private function postSignedBirdWebhook(array $event, string $deliveryId)
    {
        $body = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $signature = base64_encode(hash_hmac(
            'sha256',
            $deliveryId . '.' . $timestamp . '.' . $body,
            'test-webhook-secret',
            true
        ));

        return $this->call('POST', '/api/webhooks/bird/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_WEBHOOK_ID' => $deliveryId,
            'HTTP_WEBHOOK_TIMESTAMP' => $timestamp,
            'HTTP_WEBHOOK_SIGNATURE' => 'v1,' . $signature,
        ], $body);
    }

    private function interactiveReplyEvent(string $slug): array
    {
        return [
            'type' => 'whatsapp.received',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => 'wamid.inbound-reply',
                'direction' => 'inbound',
                'from' => ['phone_number' => '+6281315379598'],
                'to' => ['phone_number' => '+6281770643439'],
                'in_reply_to_message_id' => 'wamid.original-reminder',
                'interactive_reply' => [
                    'type' => 'button',
                    'button' => [
                        'slug' => $slug,
                        'text' => $slug === 'open_admin' ? 'Buka WEB Admin' : 'Bales Customer',
                    ],
                ],
            ],
        ];
    }

    private function customerAndConversation(): array
    {
        $region = Region::create(['code' => 'wa-region', 'name' => 'Jakarta Reminder', 'type' => 'city']);
        $district = District::create(['region_id' => $region->id, 'code' => 'wa-district', 'name' => 'Kecamatan Reminder']);
        $village = Village::create([
            'district_id' => $district->id,
            'code' => 'wa-village',
            'name' => 'Kelurahan Reminder',
            'type' => 'urban_village',
            'postal_code' => '11160',
        ]);
        $customer = Customer::create([
            'full_name' => 'Customer WhatsApp Test',
            'phone' => '081234567890',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => '11160',
            'street_address' => 'Jalan Rahasia',
        ]);
        $motor = MotorProduct::create([
            'code' => 'wa-motor',
            'name' => 'Motor WhatsApp Test',
            'family' => 'Test',
            'status' => 'active',
        ]);
        $conversation = ChatConversation::create([
            'customer_id' => $customer->id,
            'context_type' => 'dana_tunai',
            'motor_product_id' => null,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        return [$customer, $conversation, $motor];
    }
}
