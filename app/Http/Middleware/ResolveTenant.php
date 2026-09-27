<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): mixed
    {
        $context = app(TenantContext::class);
        $context->clear();
        try {
            $user = User::query()->find($request->user()?->id);
            abort_unless($user, 401);
            Auth::setUser($user);
            if ($user->role !== 'developer') {
                $context->set($user->business_id, $user);
            }

            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
