<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureAdminCapability
{
    public function handle(Request $request, Closure $next, string $section)
    {
        /** @var User|null $admin */
        $admin = $request->user();
        abort_unless($admin && $admin->canAccessAdminSection($section), 403);

        return $next($request);
    }
}
