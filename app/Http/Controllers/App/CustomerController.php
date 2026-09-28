<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function lookup(Request $request, CustomerService $service)
    {
        $query = (string) $request->query('q', '');
        abort_if(mb_strlen($query) > 100, 422, 'Kata kunci terlalu panjang.');

        return response()->json(['customers' => collect($service->search($request->user(), $query))
            ->map(fn ($row) => ['id' => $row->id, 'nama' => $row->nama, 'no_hp' => $row->no_hp])]);
    }

    public function index(Request $request, CustomerService $service)
    {
        $customers = $request->filled('q') ? $service->search($request->user(), $request->query('q'))
            : DB::table('customers')->where('business_id', $request->user()->business_id)->orderBy('nama')->limit(100)->get(['id', 'nama', 'no_hp', 'email', 'stamp_count']);

        return Inertia::render('App/Customers', ['customers' => $customers, 'query' => $request->query('q', '')]);
    }

    public function store(Request $request, CustomerService $service)
    {
        $service->save($request->user(), $request->all());

        return back()->with('success', 'Pelanggan ditambahkan.');
    }

    public function update(Request $request, int $id, CustomerService $service)
    {
        $service->save($request->user(), $request->all(), $id);

        return back()->with('success', 'Pelanggan diperbarui.');
    }
}
