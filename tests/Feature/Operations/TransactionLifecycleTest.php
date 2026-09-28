<?php

namespace Tests\Feature\Operations;

use App\Services\CancellationService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use App\Services\TransactionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\FoundationTestCase;

class TransactionLifecycleTest extends FoundationTestCase
{
    public function test_financial_lock_status_and_cancellation_keep_payment(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 100000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        $this->assertSame(1, DB::table('transactions')->find($id)->version);
        $itemId = DB::table('transaction_items')->where('transaction_id', $id)->value('id');
        app(TransactionService::class)->update($owner, $id, ['expected_version' => 1, 'item_clothes' => [$itemId => null]]);
        app(TransactionService::class)->update($owner, $id, ['expected_version' => 2, 'catatan_kondisi' => 'noda lama']);
        $this->assertSame(100000, DB::table('transactions')->find($id)->total_akhir);
        app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 100000, 'metode' => 'tunai']);
        try {
            app(TransactionService::class)->update($owner, $id, ['expected_version' => 4, 'items' => $items, 'quote_fingerprint' => $quote['fingerprint']]);
            $this->fail('Edit harga setelah payment diterima.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        app(TransactionStateMachine::class)->move($owner, $id, 'DIPROSES', 4);
        try {
            app(TransactionStateMachine::class)->move($owner, $id, 'SUDAH_DIAMBIL', 5);
            $this->fail('Lompatan status diterima.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        app(CancellationService::class)->cancel($owner, $id, 5, 'Salah timbang');
        app(CancellationService::class)->cancel($owner, $id, 5, 'Alasan baru');
        $this->assertSame('DIBATALKAN', DB::table('transactions')->find($id)->status);
        $this->assertSame('Salah timbang', DB::table('transactions')->find($id)->alasan_pembatalan);
        $this->assertSame(1, DB::table('payments')->where('transaction_id', $id)->count());
        $this->assertSame(1, DB::table('status_histories')->where('transaction_id', $id)->where('status', 'DIBATALKAN')->count());
        $this->assertSame(1, DB::table('audit_logs')->where('aksi', 'transaksi.batal')->count());
    }
}
