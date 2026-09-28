<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, int $id, PaymentService $service)
    {
        $service->store($request->user(), $id, $request->all());

        return back()->with('success', 'Pembayaran tersimpan.');
    }
}
