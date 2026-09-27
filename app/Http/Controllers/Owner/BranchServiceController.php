<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use App\Services\ServiceCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BranchServiceController extends Controller
{
    public function index(int $branch)
    {
        $record = Branch::query()->findOrFail($branch);
        Gate::authorize('view', $record);

        return Inertia::render('Management', ['kind' => 'services', 'records' => Service::query()->where('branch_id', $branch)->orderBy('nama')->get(['id', 'nama', 'satuan', 'harga', 'durasi_jam', 'berat_minimum', 'is_active']), 'branch' => $record->only(['id', 'nama', 'is_active'])]);
    }

    public function store(Request $request, int $branch, ServiceCatalogService $service)
    {
        $service->save($request->user(), $request->all(), null, $branch);

        return back()->with('success', 'Layanan cabang ditambahkan.');
    }

    public function update(Request $request, int $branch, int $id, ServiceCatalogService $service)
    {
        $service->save($request->user(), $request->all(), $id, $branch);

        return back()->with('success', 'Layanan cabang diperbarui.');
    }
}
