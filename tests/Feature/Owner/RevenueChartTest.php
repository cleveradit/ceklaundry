<?php

namespace Tests\Feature\Owner;

use App\Services\OwnerReportService;
use Tests\OwnerReportTestCase;

class RevenueChartTest extends OwnerReportTestCase
{
    public function test_daily_and_monthly_buckets_fill_zeros_and_match_total_across_year_boundary(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id);
        $this->payment($business, $owner, $id, 1000, '2025-12-31 23:59:59');
        $this->payment($business, $owner, $id, 2000, '2026-01-02 00:00:00');
        $reports = app(OwnerReportService::class);
        $input = ['start' => '2025-12-31', 'end' => '2026-01-02'];
        $daily = $reports->revenue($owner, $this->filters($owner, $input, true));
        $this->assertSame([['date' => '2025-12-31', 'total' => 1000], ['date' => '2026-01-01', 'total' => 0], ['date' => '2026-01-02', 'total' => 2000]], $daily['buckets']);
        $this->assertSame($daily['total'], array_sum(array_column($daily['buckets'], 'total')));
        $monthly = $reports->revenue($owner, $this->filters($owner, [...$input, 'granularity' => 'month'], true));
        $this->assertSame([['date' => '2025-12', 'total' => 1000], ['date' => '2026-01', 'total' => 2000]], $monthly['buckets']);
        $empty = $reports->revenue($owner, $this->filters($owner, ['start' => '2026-02-01', 'end' => '2026-02-28'], true));
        $this->assertCount(28, $empty['buckets']);
        $this->assertSame(0, $empty['total']);
        $this->actingAs($owner)->getJson('/owner/reports/revenue?granularity=year')->assertUnprocessable();
    }
}
