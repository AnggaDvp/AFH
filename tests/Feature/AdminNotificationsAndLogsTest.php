<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\DanaTunaiApplication;
use App\Models\District;
use App\Models\MotorProduct;
use App\Models\Region;
use App\Models\User;
use App\Models\Village;
use App\Services\AdminNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminNotificationsAndLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_recipient_scoped_paginated_and_marked_read(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');
        $inactive = $this->user('admin', false);
        $this->assertSame(2, app(AdminNotificationService::class)->notifyActiveAdmins(
            'new_chat_message',
            'Pesan baru',
            'Ada pesan customer.',
            ['url' => '/admin/chat/7']
        ));

        $staffNotification = AdminNotification::query()
            ->where('recipient_admin_id', $staff->id)
            ->firstOrFail();
        $this->actingAs($staff)->get('/admin/notifications')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/notifications/IndexPage')
                ->where('adminUnreadNotifications', 1)
                ->where('unreadCount', 1)
                ->where('notifications.total', 1)
                ->where('notifications.data.0.url', '/admin/chat/7'));

        $this->actingAs($admin)->post('/admin/notifications/' . $staffNotification->id . '/read')
            ->assertNotFound();
        $this->actingAs($staff)->post('/admin/notifications/' . $staffNotification->id . '/read')
            ->assertRedirect();
        $this->assertNotNull($staffNotification->fresh()->read_at);

        for ($index = 0; $index < 25; $index++) {
            AdminNotification::create([
                'recipient_admin_id' => $staff->id,
                'type' => 'new_customer',
                'title' => 'Customer baru',
                'message' => 'Aktivitas customer.',
            ]);
        }
        $this->actingAs($staff)->get('/admin/notifications?page=2')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('notifications.current_page', 2)
                ->where('notifications.total', 26)
                ->has('notifications.data', 1)
                ->where('unreadCount', 25));
        $this->actingAs($staff)->post('/admin/notifications/read-all')->assertRedirect();
        $this->assertSame(0, $staff->adminNotifications()->whereNull('read_at')->count());
        $this->assertSame(0, $inactive->adminNotifications()->count());
    }

    public function test_customer_registration_interest_messages_and_attachments_create_safe_notifications(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');
        [$region, $district, $village] = $this->address();

        $this->postJson('/customer/register', [
            'nama' => 'Nadia Customer',
            'telepon' => '081234567890',
            'wilayah' => $region->id,
            'kecamatan' => $district->id,
            'kelurahan' => $village->id,
            'namaJalan' => 'Jalan Contoh 10',
        ])->assertRedirect(route('customer.show'));
        $customer = Customer::firstOrFail();
        $this->assertSame(2, AdminNotification::where('type', 'new_customer')->count());

        $motor = MotorProduct::create([
            'code' => 'NOTIFY-MOTOR',
            'name' => 'Vario 125 CBS',
            'family' => 'Vario',
            'status' => 'active',
        ]);
        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'dana_tunai'])
            ->assertRedirect();
        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/start', ['context_type' => 'motor', 'motor_product_id' => $motor->id])
            ->assertRedirect();
        $motorConversation = $customer->conversations()->where('context_type', 'motor')->firstOrFail();

        Storage::fake('chat_private');
        $this->withSession(['customer_id' => $customer->id])
            ->post('/customer/chat/' . $motorConversation->id . '/messages', [
                'body' => 'PRIVATE_MESSAGE_BODY',
                'attachments' => [UploadedFile::fake()->createWithContent('private-identity.pdf', "%PDF-1.4\nprivate")],
            ])->assertRedirect();

        $this->assertSame(2, AdminNotification::where('type', 'new_dana_tunai_interest')->count());
        $this->assertSame(2, AdminNotification::where('type', 'new_motor_interest')->count());
        $this->assertSame(2, AdminNotification::where('type', 'new_chat_message')->count());
        $this->assertSame(2, AdminNotification::where('type', 'new_attachment')->count());
        $privateNotifications = AdminNotification::query()
            ->whereIn('type', ['new_chat_message', 'new_attachment'])
            ->get();
        foreach ($privateNotifications as $notification) {
            $this->assertStringNotContainsString('PRIVATE_MESSAGE_BODY', $notification->message);
            $this->assertStringNotContainsString('private-identity.pdf', $notification->message);
            $this->assertStringNotContainsString('PRIVATE_MESSAGE_BODY', json_encode($notification->data));
            $this->assertStringNotContainsString('private-identity.pdf', json_encode($notification->data));
        }

        $this->actingAs($admin)->get('/admin/notifications')->assertOk();
        $this->actingAs($staff)->get('/admin/notifications')->assertOk();
        $this->actingAs($admin)->withSession(['customer_id' => $customer->id])
            ->get('/customer')->assertOk()->assertInertia(fn (Assert $page) => $page
                ->where('adminUnreadNotifications', 0));
    }

    public function test_activity_log_page_is_admin_only_and_filters_from_database(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');
        $admin->adminActivityLogs()->create([
            'action' => 'application.status_changed',
            'entity_type' => 'credit_application',
            'entity_id' => '42',
            'metadata' => ['from_status' => 'submitted', 'to_status' => 'under_review'],
            'created_at' => now(),
        ]);
        $admin->adminActivityLogs()->create([
            'action' => 'admin.login',
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($staff)->get('/admin/activity-logs')->assertForbidden();
        $this->actingAs($admin)->get('/admin/activity-logs?action=application.status_changed&entity_type=credit_application')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('admin/activity-logs/IndexPage')
                ->has('logs.data', 1)
                ->where('logs.data.0.entityId', '42')
                ->where('logs.data.0.metadata.to_status', 'under_review'));
    }

    public function test_credit_and_dana_tunai_application_creation_notifies_active_admins(): void
    {
        $admin = $this->user('admin');
        $staff = $this->user('staff');
        $customer = $this->createCustomer();
        $motor = MotorProduct::create([
            'code' => 'NEW-APPLICATION-MOTOR',
            'name' => 'PCX 160',
            'family' => 'PCX',
            'status' => 'active',
        ]);
        $credit = CreditApplication::create([
            'application_number' => 'AFH-CR-NOTIFY-1',
            'customer_id' => $customer->id,
            'motor_product_id' => $motor->id,
            'status' => 'submitted',
            'source' => 'website',
        ]);
        $dana = DanaTunaiApplication::create([
            'application_number' => 'AFH-DT-NOTIFY-1',
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'source' => 'website',
        ]);

        $this->assertSame(4, AdminNotification::where('type', 'new_application')->count());
        $this->assertDatabaseHas('admin_notifications', [
            'recipient_admin_id' => $admin->id,
            'title' => 'Pengajuan kredit baru',
        ]);
        $this->assertDatabaseHas('admin_notifications', [
            'recipient_admin_id' => $staff->id,
            'title' => 'Pengajuan Dana Tunai baru',
        ]);
        $creditNotification = AdminNotification::query()
            ->where('recipient_admin_id', $admin->id)
            ->where('title', 'Pengajuan kredit baru')
            ->firstOrFail();
        $this->assertSame('/admin/pengajuan/' . $credit->id, $creditNotification->data['url']);
        $this->assertDatabaseHas('admin_notifications', [
            'recipient_admin_id' => $staff->id,
            'data->url' => '/admin/dana-tunai/' . $dana->id,
        ]);
    }

    private function user(string $role, bool $active = true): User
    {
        static $sequence = 0;
        $sequence++;

        return User::forceCreate([
            'name' => ucfirst($role) . ' Notification Test',
            'email' => 'notification-' . $sequence . '@example.test',
            'password' => Hash::make('notification-test-password'),
            'is_admin' => false,
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function address(): array
    {
        $region = Region::create(['code' => 'notify-region', 'name' => 'Jakarta Test', 'type' => 'city']);
        $district = District::create(['region_id' => $region->id, 'code' => 'notify-district', 'name' => 'District Test']);
        $village = Village::create([
            'district_id' => $district->id,
            'code' => 'notify-village',
            'name' => 'Village Test',
            'type' => 'urban_village',
            'postal_code' => '11160',
        ]);

        return [$region, $district, $village];
    }

    private function createCustomer(): Customer
    {
        [$region, $district, $village] = $this->address();

        return Customer::create([
            'full_name' => 'Application Customer',
            'phone' => '081234567899',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => $village->postal_code,
            'street_address' => 'Jalan Contoh',
        ]);
    }
}
