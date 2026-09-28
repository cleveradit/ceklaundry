<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\OperationalAccess;
use App\Services\OperationsDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, OperationsDashboardService $dashboard, OperationalAccess $access)
    {
        $actor = $request->user();
        $branches = DB::table('branches')->where('business_id', $actor->business_id)->orderBy('nama')->get(['id', 'nama', 'is_active']);
        $branchId = $actor->role === 'admin' ? (int) $actor->branch_id : (int) ($request->integer('branch_id') ?: ($branches->first()?->id ?? 0));
        $summary = $branchId ? $dashboard->summary($actor, $branchId) : null;

        return Inertia::render('AdminHome', ['branches' => $actor->role === 'owner' ? $branches : [], 'branchId' => $branchId,
            'branchName' => $branches->firstWhere('id', $branchId)?->nama, 'summary' => $summary]);
    }
}
