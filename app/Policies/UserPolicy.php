<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->role === 'owner';
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && ! $user->must_change_password;
    }

    public function update(User $user, User $target): bool
    {
        return $this->create($user) && $target->role === 'admin' && $user->business_id === $target->business_id;
    }
}
