<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['dp_enabled' => 'boolean', 'reminder_enabled' => 'boolean', 'wa_on_ready' => 'boolean', 'wa_on_reminder' => 'boolean'];
    }
}
