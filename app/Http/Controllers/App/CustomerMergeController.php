<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\CustomerMergeService;
use Illuminate\Http\Request;

class CustomerMergeController extends Controller
{
    public function store(Request $request, CustomerMergeService $service)
    {
        $valid = $request->validate(['source_id' => ['required', 'integer'], 'target_id' => ['required', 'integer'], 'confirmed' => ['required', 'accepted']]);
        $service->merge($request->user(), $valid['source_id'], $valid['target_id']);

        return back()->with('success', 'Pelanggan berhasil digabung.');
    }
}
