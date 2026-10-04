<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppReminder;
use App\Models\User;
use App\Models\WhatsAppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminWhatsAppNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'whatsapp.enabled' => true,
            'whatsapp.provider' => 'bird',
            'whatsapp.bird.api_key' => 'test-api-key',
            'whatsapp.bird.region' => 'eu1',
            'whatsapp.bird.whatsapp_number_id' => 'test-number-id',
            'whatsapp.sender_phone' => '081770643439',
            'whatsapp.recipients' => ['081315379598'],
            'whatsapp.reminder_template_name' => 'test_approved_template',
            'whatsapp.reminder_template_language' => 'id_ID',
            'whatsapp.queue_connection' => 'database',
        ]);
    }

    public function test_admin_can_filter_paginated_logs_but_staff_and_customers_cannot(): void
    {
        $admin = $this->user('admin');
        $this->createNotifications(26);

        $this->actingAs($admin)->get('/admin/whatsapp-notifications?status=sent&event_type=new_motor_interest')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/whatsapp-notifications/IndexPage')
                ->where('configuration.provider', 'bird')
                ->where('configuration.sender', '081770643439')
                ->where('configuration.recipients.0', '081315379598')
                ->missing('configuration.apiKey')
                ->has('notifications.data', 1)
                ->where('notifications.total', 1)
                ->where('notifications.data.0.status', 'sent')
                ->missing('notifications.data.0.message'));

        $this->actingAs($this->user('staff'))
            ->get('/admin/whatsapp-notifications')->assertForbidden();

        Auth::logout();
        $this->withSession(['customer_id' => 1])
            ->get('/admin/whatsapp-notifications')->assertRedirect('/admin/login');

        $this->actingAs($admin)->get('/admin/whatsapp-notifications')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->has('notifications.data', 25)
                ->where('notifications.total', 26));

        $this->actingAs($admin)->get('/admin/whatsapp')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/whatsapp-notifications/IndexPage')
                ->missing('notifications.data.0.payload')
                ->missing('notifications.data.0.accessToken'));
    }

    public function test_admin_can_retry_failed_notification_once_and_sent_reminders_are_not_retryable(): void
    {
        $admin = $this->user('admin');
        $failed = $this->createNotification('failed');
        $sent = $this->createNotification('sent');
        $idempotencyKey = $failed->idempotency_key;
        Bus::fake();

        $this->actingAs($admin)
            ->post('/admin/whatsapp/' . $failed->id . '/retry')
            ->assertRedirect();

        $this->assertSame('queued', $failed->fresh()->status);
        $this->assertSame($idempotencyKey, $failed->fresh()->idempotency_key);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'whatsapp.reminder_retry_requested',
            'admin_id' => $admin->id,
            'entity_id' => (string) $failed->id,
        ]);
        Bus::assertDispatched(SendWhatsAppReminder::class, 1);

        $this->post('/admin/whatsapp/' . $failed->id . '/retry')
            ->assertSessionHasErrors('whatsapp');
        $this->post('/admin/whatsapp/' . $sent->id . '/retry')
            ->assertSessionHasErrors('whatsapp');
        Bus::assertDispatched(SendWhatsAppReminder::class, 1);
    }

    public function test_staff_cannot_retry_and_disabled_whatsapp_keeps_failed_reminder_unchanged(): void
    {
        $failed = $this->createNotification('failed');
        $this->actingAs($this->user('staff'))
            ->post('/admin/whatsapp/' . $failed->id . '/retry')
            ->assertForbidden();
        $this->assertSame('failed', $failed->fresh()->status);

        Auth::logout();
        config(['whatsapp.enabled' => false]);
        $this->actingAs($this->user('admin'))
            ->post('/admin/whatsapp/' . $failed->id . '/retry')
            ->assertSessionHasErrors('whatsapp');
        $this->assertSame('failed', $failed->fresh()->status);
    }

    private function createNotifications(int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            WhatsAppNotification::create([
                'event_type' => $index === 1 ? 'new_motor_interest' : 'new_unread_customer_chat',
                'recipient' => '081315379598',
                'message_type' => 'template',
                'template_name' => 'test_approved_template',
                'reference_type' => 'chat_conversation',
                'reference_id' => (string) $index,
                'idempotency_key' => hash('sha256', 'notification-' . $index),
                'status' => $index === 1 ? 'sent' : 'queued',
                'provider_message_id' => $index === 1 ? 'wamid-admin-log' : null,
                'sent_at' => $index === 1 ? now() : null,
            ]);
        }
    }

    private function createNotification(string $status): WhatsAppNotification
    {
        return WhatsAppNotification::create([
            'event_type' => 'new_customer_registration',
            'recipient' => '081315379598',
            'message_type' => 'template',
            'template_name' => 'test_approved_template',
            'reference_type' => 'customer',
            'reference_id' => '1',
            'idempotency_key' => hash('sha256', 'retry-' . $status . '-' . uniqid('', true)),
            'payload' => [
                'customer_id' => null,
                'conversation_id' => null,
                'application_id' => null,
                'motor_product_id' => null,
                'variables' => [
                    'customer_name' => 'Customer Test',
                    'customer_phone' => '081234567890',
                    'service_type' => 'Pendaftaran Customer',
                    'activity_summary' => 'Customer baru mendaftar.',
                ],
            ],
            'status' => $status,
        ]);
    }

    private function user(string $role): User
    {
        return User::forceCreate([
            'name' => 'WhatsApp ' . ucfirst($role),
            'email' => 'whatsapp-' . $role . '-' . User::count() . '@example.test',
            'password' => Hash::make('whatsapp-test-password'),
            'is_admin' => false,
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
