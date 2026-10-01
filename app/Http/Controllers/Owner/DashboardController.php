<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, OwnerReportService $reports)
    {
        return Inertia::render('Dashboard', $reports->dashboard($request->user()));
    }
}
