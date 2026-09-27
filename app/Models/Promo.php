<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'semua_cabang' => 'boolean'];
    }
}
