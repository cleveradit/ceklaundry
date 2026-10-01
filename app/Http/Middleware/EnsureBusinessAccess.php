<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Business;
use App\Services\DemoSessionService;
use App\Services\LifecycleService;
use Closure;
use Illuminate\Http\Request;

class EnsureBusinessAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        abort_unless($user->is_active, 403, 'Akun tidak aktif. Hubungi pengelola.');
        if ($user->role !== 'developer') {
            $business = Business::query()->findOrFail($user->business_id);
            abort_if(app(LifecycleService::class)->status($business) === 'DEMO_EXPIRED', 410, 'Demo berakhir.');
            abort_unless(app(LifecycleService::class)->panelAllowed($business), 403, 'Akses bisnis saat ini tidak aktif. Hubungi pengelola.');
            if ($business->is_demo) {
                app(DemoSessionService::class)->assert($request, $user, $business);
            }
            if ($user->role === 'admin') {
                abort_unless(Branch::query()->whereKey($user->branch_id)->where('is_active', true)->exists(), 403, 'Cabang tidak aktif.');
            }
        }

        return $next($request);
    }
}
