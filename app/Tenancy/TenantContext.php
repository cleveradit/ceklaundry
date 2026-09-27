<?php

namespace App\Tenancy;

use App\Models\User;
use Closure;
use LogicException;

final class TenantContext
{
    public ?int $businessId = null;

    public ?int $branchId = null;

    public function requireId(): int
    {
        return $this->businessId ?? throw new LogicException('Konteks bisnis belum ditetapkan.');
    }

    public function set(int $businessId, ?User $actor = null): void
    {
        if ($actor && ($actor->role === 'developer' || $actor->business_id !== $businessId)) {
            throw new LogicException('Konteks bisnis tidak sesuai akun.');
        }
        $this->businessId = $businessId;
        $this->branchId = $actor?->role === 'admin' ? $actor->branch_id : null;
    }

    public function clear(): void
    {
        $this->businessId = $this->branchId = null;
    }

    public function run(int $id, Closure $callback, ?User $actor = null): mixed
    {
        $previous = [$this->businessId, $this->branchId];
        $this->set($id, $actor);
        try {
            return $callback();
        } finally {
            [$this->businessId, $this->branchId] = $previous;
        }
    }
}
