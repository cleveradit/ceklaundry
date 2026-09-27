<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && ! $user->must_change_password && $user->role === 'developer';
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Business $business): bool
    {
        return $this->viewAny($user);
    }
}
