<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\PromoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;
        $promos = DB::table('promos')->where('business_id', $businessId)->orderByDesc('id')->get();
        $ids = $promos->pluck('id')->all();
        $pivot = DB::table('promo_branches')->whereIn('promo_id', $ids)->get()->groupBy('promo_id');

        return Inertia::render('Owner/Promos', [
            'promos' => $promos->map(fn ($promo) => [...(array) $promo, 'branch_ids' => ($pivot[$promo->id] ?? collect())->pluck('branch_id')->all()]),
            'branches' => DB::table('branches')->where('business_id', $businessId)->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request, PromoService $service)
    {
        $service->save($request->user(), $request->all());

        return back()->with('success', 'Promo ditambahkan.');
    }

    public function update(Request $request, int $id, PromoService $service)
    {
        $service->save($request->user(), $request->all(), $id);

        return back()->with('success', 'Promo diperbarui.');
    }
}
