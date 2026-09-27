<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Management', ['kind' => 'admins', 'records' => User::query()->where('business_id', $request->user()->business_id)->where('role', 'admin')->orderBy('nama')->get(['id', 'nama', 'email', 'branch_id', 'is_active']), 'branches' => Branch::query()->orderBy('nama')->get(['id', 'nama', 'is_active'])]);
    }

    public function store(Request $request, AccountService $service)
    {
        $service->saveAdmin($request->user(), $request->all());

        return back()->with('success', 'Admin ditambahkan. Password wajib diganti saat pertama masuk.');
    }

    public function update(Request $request, int $id, AccountService $service)
    {
        $service->saveAdmin($request->user(), $request->all(), $id);

        return back()->with('success', 'Akun admin berhasil diperbarui.');
    }

    public function reset(Request $request, int $id, AccountService $service)
    {
        $service->operatorReset($request->user(), $id, (string) $request->input('password'));

        return back()->with('success', 'Password sementara disimpan; sesi lama dicabut.');
    }
}
