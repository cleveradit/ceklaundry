<?php

namespace App\Services;

use App\Exceptions\StaleQuoteException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TransactionService
{
    public function create(User $actor, array $data): int
    {
        return app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($data) {
            $base = Validator::make($data, [
                'branch_id' => ['required', 'integer'], 'customer_id' => ['nullable', 'integer'],
                'customer' => ['nullable', 'array'], 'items' => ['required', 'array', 'min:1', 'max:100'],
                'request_key' => ['required', 'uuid'], 'quote_fingerprint' => ['required', 'string', 'size:64'],
                'catatan_kondisi' => ['nullable', 'string', 'max:5000'],
                'estimasi_selesai' => ['nullable', 'date'], 'initial_payment' => ['nullable', 'array'],
            ])->validate();
            app(OperationalAccess::class)->branch($fresh, (int) $base['branch_id']);
            $hash = hash('sha256', json_encode($base, JSON_THROW_ON_ERROR));
            $existing = DB::table('transactions')->where('business_id', $business->id)->where('create_request_key', $base['request_key'])->first();
            if ($existing) {
                abort_unless($existing->create_request_hash === $hash && (int) $existing->branch_id === (int) $base['branch_id'], 409, 'Kunci transaksi telah dipakai dengan data berbeda.');

                return $existing->id;
            }
            if (! empty($base['customer_id']) && ! empty($base['customer'])) {
                throw ValidationException::withMessages(['customer' => 'Pilih pelanggan lama atau isi pelanggan baru.']);
            }
            if (! empty($base['customer_id'])) {
                $customer = DB::table('customers')->where('business_id', $business->id)->where('id', $base['customer_id'])->lockForUpdate()->first();
                abort_unless($customer, 404);
            } else {
                $valid = app(CustomerService::class)->validated($base['customer'] ?? []);
                if (DB::table('customers')->where('business_id', $business->id)->where('no_hp', $valid['no_hp'])->exists()) {
                    throw ValidationException::withMessages(['customer.no_hp' => 'Nomor sudah terdaftar. Pilih pelanggan yang ada.']);
                }
                $customerId = DB::table('customers')->insertGetId([...$valid, 'business_id' => $business->id, 'stamp_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $customer = DB::table('customers')->find($customerId);
            }
            $quote = app(PricingService::class)->quote($business->id, (int) $base['branch_id'], $base['items']);
            if (! hash_equals($quote['fingerprint'], $base['quote_fingerprint'])) {
                $quote['estimasi_selesai'] = app(EstimationService::class)->calculate(CarbonImmutable::now('Asia/Jakarta'), $quote['items'], $base['estimasi_selesai'] ?? null)->format('Y-m-d H:i:s');
                throw new StaleQuoteException($quote);
            }
            $at = CarbonImmutable::now('Asia/Jakarta');
            $estimated = app(EstimationService::class)->calculate($at, $quote['items'], $base['estimasi_selesai'] ?? null);
            $transaction = [
                'business_id' => $business->id, 'branch_id' => $base['branch_id'], 'customer_id' => $customer->id,
                'created_by' => $fresh->id,
                'notification_email' => $customer->email, 'create_request_key' => $base['request_key'], 'create_request_hash' => $hash,
                'status' => 'DITERIMA', 'waktu_masuk' => $at, 'estimasi_selesai' => $estimated,
                'subtotal' => $quote['subtotal'], 'total_akhir' => $quote['total_akhir'],
                'status_bayar' => $quote['total_akhir'] === 0 ? 'LUNAS' : 'BELUM_BAYAR',
                'catatan_kondisi' => $base['catatan_kondisi'] ?? null, 'created_at' => $at, 'updated_at' => $at,
            ];
            $id = null;
            for ($attempt = 0; $attempt < 10; $attempt++) {
                try {
                    $id = DB::table('transactions')->insertGetId([...$transaction, 'kode_resi' => app(ReceiptCodeGenerator::class)->generate()]);
                    break;
                } catch (QueryException $exception) {
                    if ((int) ($exception->errorInfo[1] ?? 0) !== 1062 || ! str_contains($exception->getMessage(), 'transactions_kode_resi_unique')) {
                        throw $exception;
                    }
                }
            }
            if ($id === null) {
                throw new RuntimeException('Kode resi belum dapat dibuat. Coba lagi.');
            }
            foreach ($quote['items'] as $item) {
                DB::table('transaction_items')->insert([...$item, 'transaction_id' => $id, 'created_at' => $at, 'updated_at' => $at]);
            }
            DB::table('status_histories')->insert(['business_id' => $business->id, 'transaction_id' => $id, 'status' => 'DITERIMA', 'user_id' => $fresh->id, 'created_at' => $at]);
            if (! empty($base['initial_payment'])) {
                $payment = $base['initial_payment'];
                $payment['request_key'] = $base['request_key'];
                app(PaymentService::class)->insertLocked($fresh, DB::table('transactions')->find($id), $payment);
            }

            return $id;
        });
    }

    public function update(User $actor, int $id, array $data): void
    {
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($id, $data) {
            $visible = app(OperationalAccess::class)->transaction($fresh, $id);
            app(OperationalAccess::class)->branch($fresh, $visible->branch_id);
            DB::table('customers')->where('business_id', $business->id)->where('id', $visible->customer_id)->lockForUpdate()->first();
            $tx = DB::table('transactions')->where('business_id', $business->id)->where('id', $id)->lockForUpdate()->first();
            abort_unless((int) ($data['expected_version'] ?? 0) === (int) $tx->version, 409, 'Transaksi sudah berubah. Muat ulang.');
            abort_unless($tx->status === 'DITERIMA', 422, 'Transaksi sudah terkunci.');
            if (array_intersect(['customer_id', 'branch_id', 'created_by', 'waktu_masuk', 'status_bayar', 'total_akhir', 'promo_id'], array_keys($data))) {
                throw ValidationException::withMessages(['transaksi' => 'Identitas dan nilai server transaksi tidak dapat diubah langsung.']);
            }
            if (! array_key_exists('items', $data) && ! array_intersect(['catatan_kondisi', 'estimasi_selesai', 'item_clothes'], array_keys($data))) {
                throw ValidationException::withMessages(['transaksi' => 'Tidak ada perubahan yang diizinkan.']);
            }
            $fields = ['updated_at' => now(), 'version' => $tx->version + 1];
            if (isset($data['items'])) {
                $hasPayment = DB::table('payments')->where('transaction_id', $id)->exists();
                $hasLedger = DB::table('loyalty_histories')->where('transaction_id', $id)->exists();
                abort_if($tx->status_bayar === 'LUNAS' || $hasPayment || $hasLedger, 422, 'Harga transaksi sudah terkunci.');
                $quote = app(PricingService::class)->quote($business->id, $tx->branch_id, $data['items']);
                abort_unless(hash_equals($quote['fingerprint'], (string) ($data['quote_fingerprint'] ?? '')), 409, 'Harga berubah. Periksa penawaran baru.');
                $fields['subtotal'] = $quote['subtotal'];
                $fields['total_akhir'] = $quote['total_akhir'];
                $fields['status_bayar'] = $quote['total_akhir'] === 0 ? 'LUNAS' : 'BELUM_BAYAR';
                $fields['estimasi_selesai'] = app(EstimationService::class)->calculate(CarbonImmutable::parse($tx->waktu_masuk, 'Asia/Jakarta'), $quote['items'], $data['estimasi_selesai'] ?? null);
                DB::table('transaction_items')->where('transaction_id', $id)->delete();
                foreach ($quote['items'] as $item) {
                    DB::table('transaction_items')->insert([...$item, 'transaction_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
                }
            } else {
                if (array_key_exists('catatan_kondisi', $data)) {
                    $fields['catatan_kondisi'] = Validator::make($data, ['catatan_kondisi' => ['nullable', 'string', 'max:5000']])->validate()['catatan_kondisi'] ?? null;
                }
                if (array_key_exists('estimasi_selesai', $data)) {
                    $fields['estimasi_selesai'] = app(EstimationService::class)->calculate(CarbonImmutable::parse($tx->waktu_masuk, 'Asia/Jakarta'), [['durasi_jam_snapshot' => 1]], $data['estimasi_selesai']);
                }
                if (isset($data['item_clothes'])) {
                    if (! is_array($data['item_clothes'])) {
                        throw ValidationException::withMessages(['item_clothes' => 'Perkiraan jumlah baju tidak valid.']);
                    }
                    foreach ($data['item_clothes'] as $itemId => $count) {
                        if (! ctype_digit((string) $itemId) || ($count !== null && (! ctype_digit((string) $count) || (int) $count > 65535))) {
                            throw ValidationException::withMessages(['item_clothes' => 'Perkiraan jumlah baju tidak valid.']);
                        }
                        $item = DB::table('transaction_items')->where('transaction_id', $id)->where('id', $itemId)->first();
                        abort_unless($item, 404);
                        DB::table('transaction_items')->where('id', $itemId)->update(['perkiraan_jumlah_baju' => $count]);
                    }
                }
            }
            DB::table('transactions')->where('id', $id)->update($fields);
        });
    }
}
