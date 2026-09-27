<?php

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait ScopedToBranch
{
    protected static function bootScopedToBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $query) {
            $branch = app(TenantContext::class)->branchId;
            if ($branch !== null) {
                $query->where($query->getModel()->qualifyColumn('branch_id'), $branch);
            }
        });
    }
}
