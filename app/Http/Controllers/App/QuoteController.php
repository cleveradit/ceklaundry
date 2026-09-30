<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\EstimationService;
use App\Services\OperationalAccess;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function store(Request $request, PricingService $pricing, OperationalAccess $access, EstimationService $estimation)
    {
        $branchId = (int) $request->input('branch_id');
        $access->branch($request->user(), $branchId);
        $quote = $pricing->quote($request->user()->business_id, $branchId, $request->input('items', []),
            $request->filled('customer_id') ? (int) $request->input('customer_id') : null,
            $request->input('reward_item_index') === null ? null : (int) $request->input('reward_item_index'),
            $request->filled('promo_id') ? (int) $request->input('promo_id') : null);
        $quote['estimasi_selesai'] = $estimation->calculate(CarbonImmutable::now('Asia/Jakarta'), $quote['items'], $request->input('estimasi_selesai'))->format('Y-m-d H:i:s');

        return response()->json($quote);
    }
}
