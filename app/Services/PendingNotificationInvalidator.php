<?php

namespace App\Services;

use App\Models\NotificationLog;

class PendingNotificationInvalidator
{
    public function forTransaction(int $businessId, int $transactionId): int
    {
        // Caller already holds this business's root lock.
        return NotificationLog::query()->where('business_id', $businessId)->where('transaction_id', $transactionId)
            ->whereIn('status', ['tertunda', 'diproses'])->whereNull('delivery_started_at')
            ->update(['status' => 'dilewati_kondisi', 'reason_code' => 'transaksi_terminal', 'processing_token' => null,
                'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now()]);
    }

    public function invalidate(string $reason, ?string $channel = null, ?string $type = null): int
    {
        // Caller owns the business root lock. In-flight delivery is never revoked here.
        return NotificationLog::query()->whereIn('status', ['tertunda', 'diproses'])->whereNull('delivery_started_at')
            ->when($channel, fn ($query) => $query->where('kanal', $channel))
            ->when($type, fn ($query) => $query->where('tipe', $type))
            ->update(['status' => 'dilewati_kondisi', 'reason_code' => $reason, 'processing_token' => null, 'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now()]);
    }

    public function aboveReminderCount(int $max): int
    {
        return NotificationLog::query()->where('tipe', 'pengingat')->where('reminder_number', '>', $max)
            ->whereIn('status', ['tertunda', 'diproses'])->whereNull('delivery_started_at')
            ->update(['status' => 'dilewati_kondisi', 'reason_code' => 'batas_pengingat_berubah', 'processing_token' => null,
                'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now()]);
    }
}
