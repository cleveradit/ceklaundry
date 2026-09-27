<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['stamp_count' => 'integer'];
    }
}
