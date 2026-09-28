<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OperationsDashboardService
{
    public function summary(User $actor, int $branchId): array
    {
        app(OperationalAccess::class)->branch($actor, $branchId, false);
        $base = DB::table('transactions')->where('business_id', $actor->business_id)->where('branch_id', $branchId);
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $todayBase = (clone $base)->where('waktu_masuk', '>=', $today)->where('waktu_masuk', '<', $today->addDay());
        $todayRows = (clone $todayBase)
            ->orderByDesc('waktu_masuk')->limit(100)->get(['id', 'kode_resi', 'status', 'status_bayar', 'total_akhir', 'waktu_masuk']);
        $counts = (clone $base)->whereIn('status', ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL'])->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');
        $ready = (clone $base)->where('status', 'SIAP_DIAMBIL')->orderBy('waktu_siap_diambil')->orderBy('id')->limit(100)
            ->get(['id', 'kode_resi', 'waktu_siap_diambil', 'status_bayar', 'total_akhir'])
            ->map(fn ($row) => [...(array) $row, 'umur_hari' => max(0, intdiv((int) CarbonImmutable::parse($row->waktu_siap_diambil, 'Asia/Jakarta')->diffInSeconds(CarbonImmutable::now('Asia/Jakarta'), false), 86400))]);
        $late = (clone $base)->whereIn('status', ['DITERIMA', 'DIPROSES'])->where('estimasi_selesai', '<', now('Asia/Jakarta'))
            ->orderBy('estimasi_selesai')->limit(100)->get(['id', 'kode_resi', 'status', 'estimasi_selesai']);
        $todayCount = (clone $todayBase)->where('status', '<>', 'DIBATALKAN')->count();
        $kg = DB::table('transaction_items as item')->join('transactions as tx', 'tx.id', '=', 'item.transaction_id')
            ->where('tx.business_id', $actor->business_id)->where('tx.branch_id', $branchId)
            ->where('tx.waktu_masuk', '>=', $today)->where('tx.waktu_masuk', '<', $today->addDay())
            ->where('tx.status', '<>', 'DIBATALKAN')->where('item.satuan_snapshot', 'kg')->sum('item.berat_kg');

        return ['today' => $todayRows, 'counts' => $counts, 'ready' => $ready, 'late' => $late,
            'today_count' => $todayCount, 'today_kg' => (string) $kg];
    }
}
