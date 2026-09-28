<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\OperationalAccess;
use App\Services\PublicReceiptService;
use App\Services\ReceiptPrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptPrintController extends Controller
{
    public function show(Request $request, int $id, OperationalAccess $access, PublicReceiptService $receipt, ReceiptPrintService $printer)
    {
        $tx = $access->transaction($request->user(), $id);
        $data = $receipt->resolve($tx->kode_resi);
        $customer = DB::table('customers')->where('business_id', $request->user()->business_id)->where('id', $tx->customer_id)->first();
        $data['nama_pelanggan'] = $customer->nama;
        $data['no_hp_pelanggan'] = $customer->no_hp;

        return response()->view('receipts.thermal', ['receipt' => $data, 'qr' => $printer->qr($tx->kode_resi), 'public' => false])
            ->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
