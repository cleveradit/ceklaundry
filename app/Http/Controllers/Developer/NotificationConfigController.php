<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\NotificationSettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationConfigController extends Controller
{
    public function edit(int $id)
    {
        $business = Business::query()->findOrFail($id);

        return Inertia::render('Developer/NotificationConfig', [
            'business' => [
                'id' => $business->id, 'nama' => $business->nama,
                'email_sender_name' => $business->email_sender_name,
                'email_sender_address' => $business->email_sender_address,
                'smtp_configured' => $business->smtp_config !== null,
                'wa_enabled' => $business->wa_enabled,
                'wa_provider' => $business->wa_provider,
                'wa_sender_number' => $business->wa_sender_number,
                'wa_token_configured' => $business->wa_token !== null,
                'wa_config_configured' => $business->wa_config !== null,
            ],
        ]);
    }

    public function update(Request $request, int $id, NotificationSettingsService $service)
    {
        $service->technical($request->user(), $id, $request->all());

        return back()->with('success', 'Konfigurasi notifikasi diperbarui.');
    }
}
