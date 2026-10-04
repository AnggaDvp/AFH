<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_regenerates_session_records_last_login_and_opens_panel(): void
    {
        $admin = $this->admin('admin', true);
        $this->get('/admin/login')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/LoginPage'));
        $response = $this->post('/admin/login', [
            'email' => $admin->email, 'password' => 'phase-one-password',
        ]);

        $response->assertRedirect('/admin')
            ->assertSessionHasNoErrors();
        $this->assertSame($admin->id, Auth::id());
        $this->assertNotNull($admin->fresh()->last_login_at);
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'admin.login',
        ]);
        $this->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/IndexPage')
            ->where('adminRole', 'admin'));
    }

    public function test_staff_can_login_access_chat_and_has_limited_capabilities(): void
    {
        $staff = $this->admin('staff', false);
        $this->post('/admin/login', [
            'email' => $staff->email, 'password' => 'phase-one-password',
        ])->assertRedirect('/admin');

        $this->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('adminRole', 'staff'));
        $this->get('/admin/chat')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/ChatIndexPage')
            ->where('adminRole', 'staff'));
        $this->assertTrue($staff->fresh()->canAccessAdminSection('customers'));
        $this->assertTrue($staff->fresh()->canAccessAdminSection('chat'));
        $this->assertFalse($staff->fresh()->canAccessAdminSection('motors'));
        $this->assertFalse($staff->fresh()->canAccessAdminSection('reports'));
        $this->assertFalse($staff->fresh()->canAccessAdminSection('settings'));
    }

    public function test_login_rejects_inactive_accounts_with_the_same_safe_error(): void
    {
        $inactive = $this->admin('staff', false, false);
        $this->from('/admin/login')->post('/admin/login', [
            'email' => $inactive->email, 'password' => 'phase-one-password',
        ])->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak valid.']);
        $this->assertGuest();
        $this->assertNull($inactive->fresh()->last_login_at);
    }

    public function test_customer_session_does_not_authenticate_admin_and_admin_routes_require_auth(): void
    {
        $this->withSession(['customer_id' => 42])
            ->get('/admin')->assertRedirect('/admin/login');
        $this->withSession(['customer_id' => 42])
            ->get('/admin/chat')->assertRedirect('/admin/login');
        $this->withSession(['customer_id' => 42])
            ->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_staff_is_allowed_but_unknown_role_is_denied_admin_access(): void
    {
        $customerUser = $this->admin('staff', false);
        $this->actingAs($customerUser)->get('/admin')->assertOk();

        $invalidRole = $this->admin('manager', false);
        $this->actingAs($invalidRole)->get('/admin')->assertForbidden();
        $this->actingAs($invalidRole)->post('/admin/login', [
            'email' => $invalidRole->email, 'password' => 'phase-one-password',
        ])->assertRedirect();
    }

    public function test_legacy_admin_flag_is_migrated_to_admin_role(): void
    {
        $legacy = $this->admin('staff', true);
        $this->assertSame('admin', $legacy->adminRole());
        $this->actingAs($legacy)->get('/admin')->assertOk();
    }

    public function test_admin_logout_is_post_only_and_clears_admin_session(): void
    {
        $admin = $this->admin('admin', true);
        $this->actingAs($admin)
            ->withSession(['admin_marker' => 'private', 'customer_id' => 99])
            ->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
        $this->assertGuest('web');
        $this->assertFalse($this->app['session']->driver()->has('customer_id'));
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'admin.logout',
        ]);
    }

    private function admin(string $role, bool $legacyAdmin = false, bool $active = true): User
    {
        static $sequence = 0;
        $sequence++;

        return User::forceCreate([
            'name' => 'Panel Test',
            'email' => 'panel-' . $sequence . '@example.test',
            'password' => Hash::make('phase-one-password'),
            'is_admin' => $legacyAdmin,
            'role' => $role,
            'is_active' => $active,
        ]);
    }
}
