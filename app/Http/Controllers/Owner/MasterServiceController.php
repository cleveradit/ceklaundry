<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\MasterService;
use App\Services\ServiceCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MasterServiceController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', MasterService::class);

        return Inertia::render('Management', ['kind' => 'masters', 'records' => MasterService::query()->orderBy('nama')->get(['id', 'nama', 'satuan', 'harga', 'durasi_jam', 'berat_minimum', 'is_active'])]);
    }

    public function store(Request $request, ServiceCatalogService $service)
    {
        $service->save($request->user(), $request->all());

        return back()->with('success', 'Layanan master ditambahkan.');
    }

    public function update(Request $request, int $id, ServiceCatalogService $service)
    {
        $service->save($request->user(), $request->all(), $id);

        return back()->with('success', 'Layanan master diperbarui. Layanan cabang tidak berubah otomatis.');
    }
}
