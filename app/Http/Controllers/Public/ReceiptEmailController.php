<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ReceiptRateLimiter;
use App\Services\TransactionEmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ReceiptEmailController extends Controller
{
    public function request(Request $request, string $kodeResi, ReceiptRateLimiter $limiter, TransactionEmailVerificationService $service)
    {
        $limiter->check($request);
        $service->request($kodeResi, (string) $request->input('email'), $request);

        return redirect('/t/'.$kodeResi)->with('success', 'Jika alamat dapat digunakan, tautan konfirmasi akan dikirim.');
    }

    public function show(Request $request, string $kodeResi, ReceiptRateLimiter $limiter)
    {
        $limiter->check($request);
        abort_unless(URL::hasValidSignature($request), 403, 'Tautan tidak berlaku.');

        return response()->view('public.confirm-email', ['code' => $kodeResi, 'version' => (int) $request->query('version')])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function confirm(Request $request, string $kodeResi, ReceiptRateLimiter $limiter, TransactionEmailVerificationService $service)
    {
        $limiter->check($request);
        abort_unless(URL::hasValidSignature($request), 403, 'Tautan tidak berlaku.');
        $service->confirm($kodeResi, (int) $request->query('version'));

        return redirect('/t/'.$kodeResi)->with('success', 'Email transaksi berhasil dikonfirmasi.');
    }
}
