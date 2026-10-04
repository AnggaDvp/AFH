<?php

namespace App\Http\Controllers;

use App\Services\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::check() && Auth::user()->hasAdminAccess()) {
            return redirect()->route('admin.home');
        }

        return Inertia::render('admin/LoginPage');
    }

    public function store(Request $request, AdminActivityLogger $activityLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials + ['is_active' => true]) || !Auth::user()?->hasAdminAccess()) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak valid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $admin = Auth::user();
        $admin->forceFill(['last_login_at' => now()])->save();
        $activityLogger->record($admin, 'admin.login');

        return redirect()->intended(route('admin.home'));
    }

    public function destroy(Request $request, AdminActivityLogger $activityLogger): RedirectResponse
    {
        $admin = $request->user();
        if ($admin) {
            $activityLogger->record($admin, 'admin.logout');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
