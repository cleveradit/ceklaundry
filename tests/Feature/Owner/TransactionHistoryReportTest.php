<?php

namespace Tests\Feature\Owner;

use App\Models\User;
use App\Services\OwnerReportService;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\OwnerReportTestCase;

class TransactionHistoryReportTest extends OwnerReportTestCase
{
    public function test_history_combines_filters_and_preserves_cancelled_inactive_branch_history_at_wib_boundaries(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $other = $this->branch($owner, 'Lain');
        DB::table('branches')->where('id', $branch->id)->update(['is_active' => false]);
        $inside = $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => '2026-07-31 00:00:00', 'status' => 'DIBATALKAN', 'alasan_pembatalan' => 'Salah catat', 'status_bayar' => 'DP']);
        $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => '2026-07-30 23:59:59']);
        $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => '2026-08-01 00:00:00']);
        $this->transaction($business, $owner, $other->id, ['waktu_masuk' => '2026-07-31 00:00:00']);
        $this->actingAs($owner)->get('/owner/reports/history?'.http_build_query(['branch_id' => $branch->id, 'start' => '2026-07-31', 'end' => '2026-07-31', 'status' => 'DIBATALKAN', 'status_bayar' => 'DP']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Owner/TransactionHistory')
            ->has('transactions.data', 1)->where('transactions.data.0.id', $inside)->has('branches', 2)->etc());
    }

    public function test_history_is_paginated_and_rejects_invalid_filters_and_foreign_branches(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        for ($i = 0; $i < 26; $i++) {
            $this->transaction($business, $owner, $branch->id);
        }
        $report = app(OwnerReportService::class)->history($owner, $this->filters($owner));
        $this->assertSame(26, $report['transactions']->total());
        $this->assertCount(25, $report['transactions']->items());
        $this->actingAs($owner)->get('/owner/reports/history?branch_id='.$branch->id.'&page=2')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('transactions.data', 1)->where('filters.branch_id', $branch->id)->etc());
        foreach (['start=2026-08-02&end=2026-08-01', 'start=2026-02-30&end=2026-03-01', 'status=BOGUS', 'status_bayar=BOGUS', 'page=0', 'start=2026-08-01'] as $query) {
            $this->getJson('/owner/reports/history?'.$query)->assertUnprocessable();
        }
        [$foreign, $foreignOwner] = $this->tenant();
        $foreignBranch = $this->branch($foreignOwner);
        $this->transaction($foreign, $foreignOwner, $foreignBranch->id);
        foreach (['history', 'revenue', 'receivables', 'history.csv'] as $report) {
            $this->actingAs($owner)->get('/owner/reports/'.$report.'?branch_id='.$foreignBranch->id)->assertNotFound();
        }
        $this->actingAs($owner)->get('/owner/reports/history')->assertInertia(fn (Assert $page) => $page->where('transactions.total', 26)->etc());
    }

    public function test_all_report_routes_are_owner_only_and_remain_readable_in_read_only_mode(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $admin = User::factory()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'role' => 'admin']);
        foreach ([$admin, $this->developer()] as $actor) {
            foreach (['/owner', '/owner/reports/history', '/owner/reports/revenue', '/owner/reports/receivables', '/owner/reports/history.csv'] as $route) {
                $this->actingAs($actor)->get($route)->assertForbidden();
            }
        }
        DB::table('businesses')->where('id', $business->id)->update(['active_until' => now()->subDays(8)->toDateString()]);
        $this->actingAs($owner)->get('/owner/reports/history')->assertOk();
        $this->get('/owner/reports/history.csv')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }
}
