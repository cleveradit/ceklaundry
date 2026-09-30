<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\LoyaltySettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LoyaltySettingController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        return Inertia::render('Owner/LoyaltySettings', [
            'settings' => DB::table('loyalty_settings')->where('business_id', $businessId)
                ->first(['is_active', 'stempel_dibutuhkan', 'master_service_id', 'berat_maks_gratis']),
            'masters' => DB::table('master_services')->where('business_id', $businessId)
                ->where('is_active', true)->where('satuan', 'kg')->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $request, LoyaltySettingsService $service)
    {
        $service->save($request->user(), $request->all());

        return back()->with('success', 'Program stempel diperbarui.');
    }
}
