<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountService;
use App\Services\DeveloperBusinessSummary;
use App\Services\LifecycleService;
use App\Services\TenantProvisioner;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BusinessController extends Controller
{
    public function index(Request $request, DeveloperBusinessSummary $summary)
    {
        return Inertia::render('Management', ['kind' => 'businesses', 'records' => $summary->list($request->user())]);
    }

    public function store(Request $request, TenantProvisioner $provisioner)
    {
        $provisioner->provision($request->user(), $request->all());

        return back()->with('success', 'Bisnis dan akun owner berhasil dibuat.');
    }

    public function update(Request $request, int $id, LifecycleService $lifecycle)
    {
        $lifecycle->update($request->user(), $id, $request->all());

        return back()->with('success', 'Masa aktif dan akses bisnis diperbarui.');
    }

    public function resetOwner(Request $request, int $id, AccountService $accounts)
    {
        $owner = User::query()->where('business_id', $id)->where('role', 'owner')->firstOrFail();
        $accounts->operatorReset($request->user(), $owner->id, (string) $request->input('password'));

        return back()->with('success', 'Password sementara disimpan. Owner wajib mengganti password.');
    }
}
