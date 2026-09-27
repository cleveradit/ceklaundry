<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Models\Concerns\ScopedToBranch;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use BelongsToBusiness;
    use ScopedToBranch;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'waktu_masuk' => 'immutable_datetime'];
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
