<?php

namespace App\Models;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransactionItem extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::addGlobalScope('parent', function ($query) {
            $context = app(TenantContext::class);
            $parents = DB::table('transactions')->select('id')->where('business_id', $context->requireId());
            if ($context->branchId !== null) {
                $parents->where('branch_id', $context->branchId);
            }
            $query->whereIn('transaction_id', $parents);
        });
    }
}
