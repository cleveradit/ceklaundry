<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\ManualNotificationService;
use Illuminate\Http\Request;

class ManualNotificationController extends Controller
{
    public function email(Request $request, int $id, ManualNotificationService $service)
    {
        $service->email($request->user(), $id, $request->all());

        return back()->with('success', 'Permintaan email dicatat. Hasil pengiriman dapat dilihat pada riwayat.');
    }

    public function whatsapp(Request $request, int $id, ManualNotificationService $service)
    {
        $url = $service->whatsappLink($request->user(), $id, (string) $request->input('type'),
            (string) $request->input('request_key'), $request->boolean('confirm_unknown'));

        return $request->expectsJson() ? response()->json(['url' => $url]) : redirect()->away($url);
    }
}
