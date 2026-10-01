<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\ManualNotificationService;
use Illuminate\Http\Request;

class ManualNotificationController extends Controller
{
    public function email(Request $request, int $id, ManualNotificationService $service)
    {
        $service->email($request->user(), $id, $request->all());

        $demo = Business::query()->find($request->user()->business_id)?->is_demo;

        return back()->with('success', $demo ? 'Email disimulasikan. Tidak ada pesan yang dikirim.' : 'Permintaan email dicatat. Hasil pengiriman dapat dilihat pada riwayat.');
    }

    public function whatsapp(Request $request, int $id, ManualNotificationService $service)
    {
        $url = $service->whatsappLink($request->user(), $id, (string) $request->input('type'),
            (string) $request->input('request_key'), $request->boolean('confirm_unknown'));

        if (str_starts_with($url, 'demo-preview:')) {
            return response()->view('public.demo-message', ['message' => substr($url, strlen('demo-preview:'))])
                ->header('Cache-Control', 'no-store');
        }

        return $request->expectsJson() ? response()->json(['url' => $url]) : redirect()->away($url);
    }
}
