<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeveloperBusinessSummary
{
    public function list(User $actor): array
    {
        Gate::forUser($actor)->authorize('viewAny', Business::class);
        $now = now();

        return Business::query()->select(['id', 'nama', 'active_until', 'is_active', 'is_demo', 'demo_expires_at'])
            ->addSelect(['branch_count' => DB::table('branches')->selectRaw('COUNT(*)')->whereColumn('branches.business_id', 'businesses.id')])
            ->addSelect(['transaction_count' => DB::table('transactions')->selectRaw('COUNT(*)')->whereColumn('transactions.business_id', 'businesses.id')->whereBetween('waktu_masuk', [$now->copy()->subDays(30), $now])])
            ->orderByDesc('id')->get()->map(fn (Business $business) => [
                'id' => $business->id, 'nama' => $business->nama, 'active_until' => $business->active_until?->format('Y-m-d'),
                'is_active' => $business->is_active, 'status' => app(LifecycleService::class)->status($business),
                'branch_count' => (int) $business->branch_count, 'transaction_count' => (int) $business->transaction_count,
            ])->all();
    }
}
