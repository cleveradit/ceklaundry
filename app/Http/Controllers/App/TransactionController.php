<?php

namespace App\Http\Controllers\App;

use App\Exceptions\StaleQuoteException;
use App\Http\Controllers\Controller;
use App\Services\ManualReceiptLinkService;
use App\Services\OperationalAccess;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $query = DB::table('transactions as t')->join('customers as c', 'c.id', '=', 't.customer_id')
            ->where('t.business_id', $actor->business_id);
        if ($actor->role === 'admin') {
            $query->where('t.branch_id', $actor->branch_id);
        } elseif ($request->filled('branch_id')) {
            app(OperationalAccess::class)->branch($actor, (int) $request->integer('branch_id'), false);
            $query->where('t.branch_id', $request->integer('branch_id'));
        }
        if ($request->filled('q')) {
            $needle = trim((string) $request->query('q'));
            $query->where(function ($builder) use ($needle) {
                $like = '%'.addcslashes($needle, '%_\\').'%';
                $builder->where('t.kode_resi', 'like', $like)->orWhere('c.nama', 'like', $like)
                    ->orWhere('c.no_hp', 'like', $like);
            });
        }

        return Inertia::render('App/Transactions', ['transactions' => $query->orderByDesc('t.waktu_masuk')->limit(100)
            ->get(['t.id', 't.kode_resi', 't.status', 't.status_bayar', 't.total_akhir', 't.waktu_masuk', 'c.nama as customer_name']),
            'query' => $request->query('q', '')]);
    }

    public function create(Request $request)
    {
        $actor = $request->user();
        $branches = DB::table('branches')->where('business_id', $actor->business_id)->where('is_active', true)->orderBy('nama')->get(['id', 'nama']);
        $branchId = $actor->role === 'admin' ? (int) $actor->branch_id : (int) ($request->integer('branch_id') ?: ($branches->first()?->id ?? 0));
        app(OperationalAccess::class)->branch($actor, $branchId);
        $services = DB::table('services')->where('business_id', $actor->business_id)->where('branch_id', $branchId)->where('is_active', true)->orderBy('nama')->get(['id', 'nama', 'satuan', 'harga', 'berat_minimum', 'durasi_jam']);
        $customers = DB::table('customers')->where('business_id', $actor->business_id)->orderBy('nama')->limit(100)->get(['id', 'nama', 'no_hp']);

        return Inertia::render('App/TransactionCreate', ['branches' => $actor->role === 'owner' ? $branches : [], 'branchId' => $branchId, 'services' => $services, 'customers' => $customers]);
    }

    public function store(Request $request, TransactionService $service)
    {
        try {
            $id = $service->create($request->user(), $request->all());
        } catch (StaleQuoteException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage(), 'quote' => $exception->quote], 409);
            }
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['url' => '/app/transactions/'.$id], 201);
        }

        return redirect('/app/transactions/'.$id)->with('success', 'Transaksi tersimpan.');
    }

    public function edit(Request $request, int $id, OperationalAccess $access)
    {
        $tx = $access->transaction($request->user(), $id);
        abort_unless($tx->status === 'DITERIMA' && $tx->status_bayar !== 'LUNAS', 422, 'Harga transaksi sudah terkunci.');
        abort_if(DB::table('payments')->where('transaction_id', $id)->exists() || DB::table('loyalty_histories')->where('transaction_id', $id)->exists(), 422, 'Harga transaksi sudah terkunci.');
        $services = DB::table('services')->where('business_id', $request->user()->business_id)->where('branch_id', $tx->branch_id)->where('is_active', true)
            ->orderBy('nama')->get(['id', 'nama', 'satuan', 'harga']);
        $items = DB::table('transaction_items')->where('transaction_id', $id)->orderBy('id')->get(['service_id', 'berat_kg', 'jumlah_unit', 'perkiraan_jumlah_baju']);

        return Inertia::render('App/TransactionEdit', ['transaction' => $tx, 'services' => $services, 'items' => $items]);
    }

    public function show(Request $request, int $id, OperationalAccess $access, ManualReceiptLinkService $links)
    {
        $actor = $request->user();
        $tx = $access->transaction($actor, $id);
        $customer = DB::table('customers')->where('business_id', $actor->business_id)->where('id', $tx->customer_id)->first();
        $items = DB::table('transaction_items')->where('transaction_id', $id)->orderBy('id')->get();
        $payments = DB::table('payments')->where('business_id', $actor->business_id)->where('transaction_id', $id)->orderBy('id')->get(['id', 'jumlah', 'metode', 'waktu']);
        $history = DB::table('status_histories')->where('business_id', $actor->business_id)->where('transaction_id', $id)->orderBy('created_at')->get(['status', 'created_at']);
        $paid = (int) $payments->sum('jumlah');
        $notifications = DB::table('notification_logs')->where('business_id', $actor->business_id)->where('transaction_id', $id)
            ->orderByDesc('id')->limit(100)->get(['kanal', 'tipe', 'tujuan', 'status', 'reason_code', 'attempt_count', 'created_at', 'sent_at']);

        return Inertia::render('App/TransactionDetail', ['transaction' => $tx, 'customer' => $customer,
            'items' => $items, 'payments' => $payments, 'history' => $history,
            'paid' => $paid, 'manualLink' => $links->link($tx, $customer->no_hp, $paid), 'notifications' => $notifications]);
    }

    public function update(Request $request, int $id, TransactionService $service)
    {
        $service->update($request->user(), $id, $request->all());

        return back()->with('success', 'Transaksi diperbarui.');
    }
}
