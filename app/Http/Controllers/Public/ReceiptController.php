<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\PublicReceiptService;
use App\Services\ReceiptPrintService;
use App\Services\ReceiptRateLimiter;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function search(Request $request, ReceiptRateLimiter $limiter, PublicReceiptService $receipt)
    {
        $limiter->check($request);
        $code = $receipt->normalize((string) $request->query('kode_resi', ''));
        $receipt->resolve($code);

        return redirect('/t/'.$code);
    }

    public function show(Request $request, string $kodeResi, ReceiptRateLimiter $limiter, PublicReceiptService $receipt)
    {
        $limiter->check($request);

        return response()->view('public.status', ['receipt' => $receipt->resolve($kodeResi)])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function print(Request $request, string $kodeResi, ReceiptRateLimiter $limiter, PublicReceiptService $receipt, ReceiptPrintService $printer)
    {
        $limiter->check($request);
        $data = $receipt->resolve($kodeResi);

        return response()->view('receipts.thermal', ['receipt' => $data, 'qr' => $printer->qr($data['kode_resi']), 'public' => true])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
