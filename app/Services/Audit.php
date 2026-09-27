<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class Audit
{
    public static function record(User $actor, ?int $businessId, string $action, array $safeDetail): void
    {
        (new AuditLog)->forceFill(['business_id' => $businessId, 'user_id' => $actor->id, 'aksi' => $action, 'detail' => $safeDetail, 'created_at' => now()])->save();
    }
}
