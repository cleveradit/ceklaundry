<?php

namespace Tests\Feature\Owner;

use App\Services\CancellationService;
use App\Services\OwnerReportService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\OwnerReportTestCase;

class RevenueReportTest extends OwnerReportTestCase
{
    public function test_dp_and_settlement_follow_payment_month_and_cancellation_changes_past_reports(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => '2026-06-01 12:00:00', 'subtotal' => 74500, 'total_akhir' => 74500, 'status_bayar' => 'LUNAS']);
        $this->payment($business, $owner, $id, 30000, '2026-07-28 12:00:00');
        $this->payment($business, $owner, $id, 44500, '2026-08-02 12:00:00');
        $reports = app(OwnerReportService::class);
        $july = $this->filters($owner, ['start' => '2026-07-01', 'end' => '2026-07-31'], true);
        $august = $this->filters($owner, ['start' => '2026-08-01', 'end' => '2026-08-31'], true);
        $this->assertSame(30000, $reports->revenue($owner, $july)['total']);
        $this->assertSame(44500, $reports->revenue($owner, $august)['total']);
        $this->travelTo(now()->setDate(2026, 9, 15));
        app(CancellationService::class)->cancel($owner, $id, 1, 'Salah catat');
        $this->assertSame(0, $reports->revenue($owner, $july)['total']);
        $this->assertSame(0, $reports->revenue($owner, $august)['total']);
        $this->assertSame(2, DB::table('payments')->where('transaction_id', $id)->count());
    }

    public function test_presets_include_seven_wib_days_and_respect_exact_midnight_and_branch(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 10, 1)->setTime(12, 0));
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $other = $this->branch($owner, 'Lain');
        foreach ([['2026-09-24 23:59:59', 100], ['2026-09-25 00:00:00', 200], ['2026-10-01 00:00:00', 300], ['2026-10-02 00:00:00', 400]] as [$time, $amount]) {
            $id = $this->transaction($business, $owner, $branch->id);
            $this->payment($business, $owner, $id, $amount, $time);
        }
        $id = $this->transaction($business, $owner, $other->id);
        $this->payment($business, $owner, $id, 500, '2026-10-01 12:00:00');
        $reports = app(OwnerReportService::class);
        $seven = $reports->revenue($owner, $this->filters($owner, ['period' => 'seven_days', 'branch_id' => $branch->id], true));
        $this->assertSame(500, $seven['total']);
        $this->assertSame('2026-09-25', $seven['filters']['start']);
        $this->assertCount(7, $seven['buckets']);
        $this->assertSame(300, $reports->revenue($owner, $this->filters($owner, ['period' => 'today', 'branch_id' => $branch->id], true))['total']);
        $this->assertSame(800, $reports->revenue($owner, $this->filters($owner, ['period' => 'month'], true))['total']);
        $this->actingAs($owner)->get('/owner/reports/revenue?period=today')->assertInertia(fn (Assert $page) => $page->component('Owner/RevenueReport')->where('total', 800)->etc());
        $this->getJson('/owner/reports/revenue?period=invalid')->assertUnprocessable();
        $this->getJson('/owner/reports/revenue?period=custom')->assertUnprocessable();
    }
}
