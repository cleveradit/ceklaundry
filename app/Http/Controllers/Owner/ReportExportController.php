<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerReportService;
use Illuminate\Http\Request;

class ReportExportController extends Controller
{
    public function history(Request $request, OwnerReportService $reports)
    {
        $actor = $request->user();
        $filters = $reports->filters($actor, $request->query());

        return response()->streamDownload(function () use ($actor, $filters, $reports) {
            $stream = fopen('php://output', 'wb');
            try {
                $reports->export($actor, $filters, $stream);
            } finally {
                fclose($stream);
            }
        }, 'riwayat-transaksi.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
