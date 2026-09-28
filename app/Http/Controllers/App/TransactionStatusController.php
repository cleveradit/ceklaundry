<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\TransactionStateMachine;
use Illuminate\Http\Request;

class TransactionStatusController extends Controller
{
    public function store(Request $request, int $id, TransactionStateMachine $machine)
    {
        $valid = $request->validate(['status' => ['required', 'in:DITERIMA,DIPROSES,SIAP_DIAMBIL,SUDAH_DIAMBIL,DIBATALKAN'],
            'expected_version' => ['required', 'integer', 'min:1'], 'alasan_pembatalan' => ['nullable', 'string']]);
        $machine->move($request->user(), $id, $valid['status'], $valid['expected_version'], $valid['alasan_pembatalan'] ?? null);

        return back()->with('success', 'Status transaksi diperbarui.');
    }
}
