<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function history(Request $request, OwnerReportService $reports)
    {
        return Inertia::render('Owner/TransactionHistory', $reports->history($request->user(), $reports->filters($request->user(), $request->query())));
    }

    public function revenue(Request $request, OwnerReportService $reports)
    {
        return Inertia::render('Owner/RevenueReport', $reports->revenue($request->user(), $reports->filters($request->user(), $request->query(), true)));
    }

    public function receivables(Request $request, OwnerReportService $reports)
    {
        return Inertia::render('Owner/ReceivablesReport', $reports->receivables($request->user(), $reports->filters($request->user(), $request->only(['branch_id', 'page']))));
    }
}
