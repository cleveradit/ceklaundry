<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\MasterSyncService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterSyncController extends Controller
{
    public function index()
    {
        return $this->page(session('sync_preview'));
    }

    private function page(?array $preview = null)
    {
        return Inertia::render('Owner/Sync', ['branches' => Branch::query()->where('is_active', true)->orderBy('nama')->get(['id', 'nama']), 'preview' => $preview]);
    }

    public function preview(Request $request, MasterSyncService $service)
    {
        $data = $request->validate(['branches' => ['required', 'array'], 'branches.*' => ['integer', 'distinct']]);

        return redirect('/owner/sync')->with('sync_preview', $service->preview($request->user(), $data['branches']));
    }

    public function apply(Request $request, MasterSyncService $service)
    {
        $data = $request->validate(['branches' => ['required', 'array'], 'branches.*' => ['integer', 'distinct'], 'fingerprint' => ['required', 'string', 'size:64'], 'confirmed' => ['accepted']]);
        $service->apply($request->user(), $data['branches'], $data['fingerprint']);

        return redirect('/owner/sync')->with('success', 'Seluruh cabang berhasil disinkronkan.');
    }
}
