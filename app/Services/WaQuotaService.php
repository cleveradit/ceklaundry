<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class WaQuotaService
{
    public function month(): string
    {
        return now('Asia/Jakarta')->startOfMonth()->toDateString();
    }

    public function occupied(int $businessId, ?string $month = null): int
    {
        return DB::table('notification_logs')->where('business_id', $businessId)->where('kanal', 'whatsapp')
            ->where('wa_quota_month', $month ?? $this->month())
            ->whereIn('status', ['tertunda', 'diproses', 'berhasil', 'perlu_pemeriksaan'])->count();
    }

    public function available(int $businessId): bool
    {
        $limit = DB::table('business_settings')->where('business_id', $businessId)->value('wa_monthly_limit');

        return $limit === null || $this->occupied($businessId) < (int) $limit;
    }

    public function summary(int $businessId): array
    {
        $counts = DB::table('notification_logs')->where('business_id', $businessId)->where('kanal', 'whatsapp')
            ->where('wa_quota_month', $this->month())->whereIn('status', ['tertunda', 'diproses', 'berhasil', 'perlu_pemeriksaan'])
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return ['berhasil' => (int) ($counts['berhasil'] ?? 0),
            'tertunda' => (int) ($counts['tertunda'] ?? 0),
            'diproses' => (int) ($counts['diproses'] ?? 0),
            'perlu_pemeriksaan' => (int) ($counts['perlu_pemeriksaan'] ?? 0)];
    }
}
