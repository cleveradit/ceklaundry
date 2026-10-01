<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;

class DemoSessionService
{
    public function assert(Request $request, User $user, Business $business): array
    {
        abort_unless($business->is_demo && app(LifecycleService::class)->status($business) === 'DEMO', 410, 'Demo berakhir.');
        $pair = $request->session()->get('demo_pair');
        abort_unless(is_array($pair) && (int) ($pair['business_id'] ?? 0) === $business->id, 403, 'Sesi demo tidak berlaku.');
        [$owner, $admin] = app(DemoIdentity::class)->pair($business);
        abort_unless((int) ($pair['owner_id'] ?? 0) === $owner->id && (int) ($pair['admin_id'] ?? 0) === $admin->id, 403, 'Sesi demo tidak berlaku.');
        $firstBranch = Branch::query()->where('business_id', $business->id)->orderBy('id')->first();
        abort_unless($owner->is_active && $admin->is_active && $firstBranch?->is_active
            && $admin->branch_id === $firstBranch->id, 403, 'Akun demo tidak tersedia.');
        abort_unless(($user->id === $owner->id && $user->role === 'owner') ||
            ($user->id === $admin->id && $user->role === 'admin'), 403, 'Sesi demo tidak berlaku.');

        return [$owner, $admin];
    }
}
