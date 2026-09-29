<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Services\NotificationSettingsService;
use App\Services\WaQuotaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationSettingController extends Controller
{
    public function index(Request $request, WaQuotaService $quota)
    {
        $setting = BusinessSetting::query()->firstOrFail();

        return Inertia::render('Owner/NotificationSettings', [
            'setting' => $setting->only(['reminder_enabled', 'reminder_first_days', 'reminder_interval_days',
                'reminder_max_count', 'wa_on_ready', 'wa_on_reminder', 'wa_monthly_limit']),
            'waOccupied' => $quota->occupied($request->user()->business_id),
            'waSummary' => $quota->summary($request->user()->business_id),
        ]);
    }

    public function update(Request $request, NotificationSettingsService $service)
    {
        $service->behavior($request->user(), $request->all());

        return back()->with('success', 'Pengaturan pengingat diperbarui.');
    }
}
