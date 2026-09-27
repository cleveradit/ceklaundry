<?php

namespace App\Models\Concerns;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait BelongsToBusiness
{
    protected static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $query) {
            $context = app(TenantContext::class);
            $query->where($query->getModel()->qualifyColumn('business_id'), $context->requireId());
        });
        static::saving(function (Model $model) {
            $id = app(TenantContext::class)->requireId();
            if ($model->business_id !== null && (int) $model->business_id !== $id) {
                throw new LogicException('Relasi bisnis tidak sesuai.');
            }
            if ($model->exists && (int) $model->getOriginal('business_id') !== $id) {
                throw new LogicException('Kepemilikan bisnis tidak dapat dipindahkan.');
            }
            $model->business_id = $id;
        });
    }
}
