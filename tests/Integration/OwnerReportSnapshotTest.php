<?php

namespace Tests\Integration;

use App\Services\CancellationService;
use App\Services\CustomerMergeService;
use App\Services\OwnerReportService;
use App\Services\PaymentService;
use App\Services\ReportSnapshot;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\OwnerReportTestCase;

class OwnerReportSnapshotTest extends OwnerReportTestCase
{
    public function test_streamed_csv_keeps_one_snapshot_across_chunks_during_payment_cancellation_and_merge(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => true]);
        $id = $this->transaction($business, $owner, $branch->id);
        $original = DB::table('transactions')->find($id);
        $target = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Pelanggan Baru', 'no_hp' => '6281234567890', 'created_at' => now(), 'updated_at' => now()]);
        for ($i = 0; $i < 500; $i++) {
            $this->transaction($business, $owner, $branch->id);
        }
        $changed = false;
        DB::listen(function ($event) use (&$changed, $owner, $id, $original, $target) {
            if (! $changed && $event->connectionName === 'owner_reports' && str_contains($event->sql, 'limit 500 offset 0')) {
                $changed = true;
                app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 3000, 'metode' => 'tunai']);
                $version = (int) DB::table('transactions')->where('id', $id)->value('version');
                app(CancellationService::class)->cancel($owner, $id, $version, 'Batal saat ekspor');
                app(CustomerMergeService::class)->merge($owner, $original->customer_id, $target);
            }
        });
        $stream = fopen('php://temp', 'w+');
        app(OwnerReportService::class)->export($owner, $this->filters($owner), $stream);
        rewind($stream);
        fgetcsv($stream, null, ',', '"', '');
        $rows = [];
        while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
            $rows[$row[0]] = $row;
        }
        fclose($stream);
        $this->assertTrue($changed);
        $this->assertCount(501, $rows);
        $old = $rows[$original->kode_resi];
        $this->assertSame('Pelanggan Uji', $old[2]);
        $this->assertSame('DITERIMA', $old[4]);
        $this->assertSame('0', $old[10]);
        $this->assertSame('7000', $old[11]);
        $fresh = app(OwnerReportService::class)->history($owner, $this->filters($owner, ['status' => 'DIBATALKAN']))['transactions']->items()[0];
        $this->assertSame('Pelanggan Baru', $fresh['customer_name']);
        $this->assertSame(3000, $fresh['paid']);
    }

    public function test_report_query_count_does_not_grow_with_row_count(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $queries = 0;
        DB::listen(function ($event) use (&$queries) {
            if ($event->connectionName === 'owner_reports') {
                $queries++;
            }
        });
        $reports = app(OwnerReportService::class);
        $filters = $this->filters($owner);
        $this->transaction($business, $owner, $branch->id);
        $reports->history($owner, $filters);
        $small = $queries;
        for ($i = 0; $i < 30; $i++) {
            $id = $this->transaction($business, $owner, $branch->id);
            $this->payment($business, $owner, $id, 1000, now()->toDateTimeString());
        }
        $queries = 0;
        $reports->history($owner, $filters);
        $this->assertSame($small, $queries);
        $this->assertLessThanOrEqual(5, $queries);
    }

    public function test_snapshot_keeps_old_data_after_another_connection_commits_and_is_read_only(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id);
        $this->payment($business, $owner, $id, 1000, now()->toDateTimeString());
        app(ReportSnapshot::class)->read(function (Connection $db) use ($business, $owner, $id) {
            $this->assertSame('REPEATABLE-READ', $db->selectOne('SELECT @@transaction_isolation as isolation')->isolation);
            $this->assertSame(1000, (int) $db->table('payments')->where('transaction_id', $id)->sum('jumlah'));
            $this->payment($business, $owner, $id, 2000, now()->toDateTimeString());
            DB::table('transactions')->where('id', $id)->update(['status' => 'DIBATALKAN', 'alasan_pembatalan' => 'Batal']);
            $this->assertSame(1000, (int) $db->table('payments')->where('transaction_id', $id)->sum('jumlah'));
            $this->assertSame('DITERIMA', $db->table('transactions')->where('id', $id)->value('status'));
            try {
                $db->table('transactions')->where('id', $id)->update(['version' => 2]);
                $this->fail('Report transaction must be read-only.');
            } catch (QueryException $exception) {
                $this->assertSame(1792, $exception->errorInfo[1]);
            }
        });
        $this->assertSame(3000, (int) DB::table('payments')->where('transaction_id', $id)->sum('jumlah'));
        $this->assertSame(0, app(OwnerReportService::class)->revenue($owner, $this->filters($owner, ['period' => 'today'], true))['total']);
        $this->assertSame('READ-COMMITTED', DB::selectOne('SELECT @@transaction_isolation as isolation')->isolation);
    }
}
