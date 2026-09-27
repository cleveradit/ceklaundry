<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use BelongsToBusiness;
    use ScopedToBranch;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'harga' => 'integer', 'durasi_jam' => 'integer', 'berat_minimum' => 'decimal:1'];
    }
}
