<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;

class DemoIdentity
{
    public function email(int $businessId, string $role): string
    {
        return "demo-{$role}-{$businessId}@example.invalid";
    }

    public function reserved(User $user, ?Business $business = null): bool
    {
        $business ??= $user->business_id ? Business::query()->find($user->business_id) : null;

        return (bool) ($business?->is_demo && in_array($user->role, ['owner', 'admin'], true)
            && $user->email === $this->email((int) $business->id, $user->role));
    }

    public function pair(Business $business): array
    {
        abort_unless($business->is_demo, 403);
        $owner = User::query()->where('business_id', $business->id)->where('role', 'owner')
            ->where('email', $this->email($business->id, 'owner'))->firstOrFail();
        $admin = User::query()->where('business_id', $business->id)->where('role', 'admin')
            ->where('email', $this->email($business->id, 'admin'))->firstOrFail();

        return [$owner, $admin];
    }
}
