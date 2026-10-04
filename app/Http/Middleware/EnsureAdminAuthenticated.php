<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->guest(route('admin.login'));
        }

        if (!Auth::user()->hasAdminAccess()) {
            abort(403);
        }

        Inertia::share('adminUnreadNotifications', static function (): int {
            if (!str_starts_with((string) request()->route()?->getName(), 'admin.')) {
                return 0;
            }

            $admin = request()->user();

            return $admin && $admin->hasAdminAccess()
                ? app(\App\Services\AdminNotificationService::class)->unreadCountFor($admin)
                : 0;
        });

        return $next($request);
    }
}
