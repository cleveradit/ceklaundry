<?php

namespace Tests\Feature\Owner;

use App\Services\OwnerReportService;
use Tests\OwnerReportTestCase;

class ReceivablesReportTest extends OwnerReportTestCase
{
    public function test_receivables_include_only_positive_active_balances_and_total_all_pages(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, ['subtotal' => 74500, 'total_akhir' => 74500, 'status_bayar' => 'DP']);
        $this->payment($business, $owner, $id, 10000, now()->toDateTimeString());
        $this->payment($business, $owner, $id, 20000, now()->toDateTimeString());
        for ($i = 0; $i < 25; $i++) {
            $this->transaction($business, $owner, $branch->id);
        }
        $this->transaction($business, $owner, $branch->id, ['status' => 'DIBATALKAN', 'alasan_pembatalan' => 'Batal']);
        $this->transaction($business, $owner, $branch->id, ['status' => 'SUDAH_DIAMBIL', 'status_bayar' => 'LUNAS', 'waktu_siap_diambil' => now(), 'waktu_diambil' => now()]);
        $this->transaction($business, $owner, $branch->id, ['subtotal' => 0, 'total_akhir' => 0, 'status_bayar' => 'LUNAS']);
        $result = app(OwnerReportService::class)->receivables($owner, $this->filters($owner));
        $this->assertSame(44500 + 25 * 7000, $result['total']);
        $this->assertSame(26, $result['transactions']->total());
        $row = collect($result['transactions']->items())->firstWhere('id', $id);
        $this->assertSame(30000, $row['paid']);
        $this->assertSame(44500, $row['remaining']);
    }
}
