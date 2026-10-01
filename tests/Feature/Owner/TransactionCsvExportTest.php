<?php

namespace Tests\Feature\Owner;

use App\Services\OwnerReportService;
use Illuminate\Support\Facades\DB;
use Tests\OwnerReportTestCase;

class TransactionCsvExportTest extends OwnerReportTestCase
{
    public function test_csv_has_exact_columns_bom_rfc4180_dates_safe_text_and_one_row_per_filtered_transaction(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner, '+Cabang');
        $id = $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => '2026-07-28 12:00:00', 'subtotal' => 74500, 'total_akhir' => 74500, 'status_bayar' => 'DP']);
        $this->payment($business, $owner, $id, 10000, '2026-07-28 12:00:00');
        $this->payment($business, $owner, $id, 20000, '2026-07-29 12:00:00');
        DB::table('customers')->where('id', DB::table('transactions')->where('id', $id)->value('customer_id'))->update(['nama' => " \t=SUM(1,1),\"Rani\"\nBaru"]);
        $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => '2026-08-01 00:00:00']);
        $response = $this->actingAs($owner)->get('/owner/reports/history.csv?start=2026-07-28&end=2026-07-29')->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, substr($csv, 3));
        rewind($stream);
        $this->assertSame(OwnerReportService::CSV_HEADERS, fgetcsv($stream, null, ',', '"', ''));
        $row = fgetcsv($stream, null, ',', '"', '');
        $this->assertSame("'+Cabang", $row[1]);
        $this->assertSame("' \t=SUM(1,1),\"Rani\"\nBaru", $row[2]);
        $this->assertSame(['74500', '0', '0', '74500', '30000', '44500'], array_slice($row, 6, 6));
        $this->assertSame('2026-07-28T12:00:00+07:00', $row[12]);
        $this->assertFalse(fgetcsv($stream, null, ',', '"', ''));
        fclose($stream);
    }
}
