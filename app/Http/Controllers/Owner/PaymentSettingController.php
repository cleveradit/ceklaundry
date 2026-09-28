<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Services\BusinessTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentSettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Owner/PaymentSettings', ['dpEnabled' => (bool) BusinessSetting::query()->value('dp_enabled')]);
    }

    public function update(Request $request)
    {
        $valid = $request->validate(['dp_enabled' => ['required', 'boolean']]);
        app(BusinessTransaction::class)->run($request->user(), $request->user()->business_id, function ($business, User $fresh) use ($valid) {
            abort_unless($fresh->role === 'owner', 403);
            DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => $valid['dp_enabled'], 'updated_at' => now()]);
        });

        return back()->with('success', 'Pengaturan DP diperbarui.');
    }
}
