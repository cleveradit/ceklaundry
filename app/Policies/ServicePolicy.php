<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['owner', 'admin'], true);
    }

    public function view(User $user, Service $record): bool
    {
        return $user->is_active && $user->business_id === $record->business_id && ($user->role === 'owner' || ($user->role === 'admin' && $user->branch_id === $record->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->is_active && ! $user->must_change_password && $user->role === 'owner';
    }

    public function update(User $user, Service $record): bool
    {
        return $this->create($user) && $user->business_id === $record->business_id;
    }
}
