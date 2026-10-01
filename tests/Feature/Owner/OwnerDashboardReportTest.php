<?php

namespace Tests\Feature\Owner;

use App\Services\OwnerReportService;
use Illuminate\Support\Facades\DB;
use Tests\OwnerReportTestCase;

class OwnerDashboardReportTest extends OwnerReportTestCase
{
    public function test_dashboard_uses_actual_kg_payment_time_and_exact_pile_threshold_even_with_reminders_off(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 10, 1)->setTime(12, 0));
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, ['subtotal' => 74500, 'total_akhir' => 74500, 'status_bayar' => 'DP', 'waktu_masuk' => '2026-10-01 00:00:00']);
        $this->payment($business, $owner, $id, 10000, '2026-10-01 00:00:00');
        $this->payment($business, $owner, $id, 20000, '2026-10-01 10:00:00');
        DB::table('transaction_items')->insert(['transaction_id' => $id, 'nama_layanan_snapshot' => 'Cuci', 'satuan_snapshot' => 'kg', 'harga_snapshot' => 7000, 'durasi_jam_snapshot' => 24, 'berat_kg' => '2.0', 'berat_minimum_snapshot' => '3.0', 'subtotal' => 21000, 'created_at' => now(), 'updated_at' => now()]);
        $this->transaction($business, $owner, $branch->id, ['status' => 'DIBATALKAN', 'alasan_pembatalan' => 'Batal']);
        $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => '2026-09-30 23:59:59', 'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => '2026-09-29 12:00:00', 'status_bayar' => 'LUNAS']);
        $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => '2026-09-30 23:59:59', 'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => '2026-09-29 12:00:01', 'status_bayar' => 'LUNAS']);
        DB::table('business_settings')->where('business_id', $business->id)->update(['reminder_enabled' => false, 'reminder_first_days' => 2]);
        [$foreign, $foreignOwner] = $this->tenant();
        $foreignBranch = $this->branch($foreignOwner);
        $this->transaction($foreign, $foreignOwner, $foreignBranch->id);
        $report = app(OwnerReportService::class)->dashboard($owner)['summary'];
        $this->assertSame(1, $report['today_count']);
        $this->assertSame('2.0', $report['today_kg']);
        $this->assertSame(30000, $report['revenue']);
        $this->assertSame(44500, $report['receivables']);
        $this->assertSame(1, $report['piled_up']);
        $this->actingAs($owner)->get('/owner')->assertOk()->assertSee('44500');
    }
}
