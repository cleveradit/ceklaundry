<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $guarded = ['*'];

    protected $hidden = ['wa_token', 'wa_config', 'smtp_config'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_demo' => 'boolean', 'wa_enabled' => 'boolean', 'active_until' => 'immutable_date', 'demo_expires_at' => 'immutable_datetime', 'wa_token' => 'encrypted', 'wa_config' => 'encrypted:array', 'smtp_config' => 'encrypted:array'];
    }
}
