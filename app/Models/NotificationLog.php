<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotificationLog extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['is_manual' => 'boolean', 'attempt_count' => 'integer', 'payload_snapshot' => 'array'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('branch', function (Builder $query) {
            $branch = app(TenantContext::class)->branchId;
            if ($branch !== null) {
                $query->whereIn('transaction_id', DB::table('transactions')->select('id')->where('business_id', app(TenantContext::class)->requireId())->where('branch_id', $branch));
            }
        });
    }
}
