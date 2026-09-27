<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\LifecycleService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [...parent::share($request),
            'auth' => fn () => $request->user()?->only(['id', 'nama', 'email', 'role', 'must_change_password']),
            'business' => function () use ($request) {
                if (! $request->user()?->business_id) {
                    return null;
                }
                $business = Business::query()->find($request->user()->business_id);
                if (! $business) {
                    return null;
                }
                $lifecycle = app(LifecycleService::class);

                return ['nama' => $business->nama, 'status' => $lifecycle->status($business), 'writable' => $lifecycle->writable($business), 'warning' => $lifecycle->warning($business), 'active_until' => $business->active_until?->format('Y-m-d')];
            },
            'flash' => fn () => ['success' => $request->session()->get('success')],
        ];
    }
}
