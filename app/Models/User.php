<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['nama', 'email'];

    protected $hidden = ['password', 'owner_business_id'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'must_change_password' => 'boolean', 'business_id' => 'integer', 'branch_id' => 'integer'];
    }

    public function getRememberTokenName()
    {
        return '';
    }
}
