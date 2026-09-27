<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Branch::class);

        return Inertia::render('Management', ['kind' => 'branches', 'records' => Branch::query()->orderBy('nama')->get(['id', 'nama', 'alamat', 'telepon', 'is_active'])]);
    }

    public function store(Request $request, BranchService $service)
    {
        $service->save($request->user(), $request->all());

        return back()->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function update(Request $request, int $id, BranchService $service)
    {
        $service->save($request->user(), $request->all(), $id);

        return back()->with('success', 'Cabang berhasil diperbarui.');
    }
}
