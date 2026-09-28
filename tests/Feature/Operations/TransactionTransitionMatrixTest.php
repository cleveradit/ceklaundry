<?php

namespace Tests\Feature\Operations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FoundationTestCase;

class TransactionTransitionMatrixTest extends FoundationTestCase
{
    public function test_all_five_by_five_status_requests_follow_the_state_machine(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $states = ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL', 'SUDAH_DIAMBIL', 'DIBATALKAN'];
        $forward = ['DITERIMA' => 'DIPROSES', 'DIPROSES' => 'SIAP_DIAMBIL', 'SIAP_DIAMBIL' => 'SUDAH_DIAMBIL'];

        foreach ($states as $source) {
            foreach ($states as $target) {
                $ready = in_array($source, ['SIAP_DIAMBIL', 'SUDAH_DIAMBIL'], true);
                $paid = $ready;
                $id = $this->transaction($business, $owner, $branch->id, [
                    'status' => $source, 'status_bayar' => $paid ? 'LUNAS' : 'BELUM_BAYAR',
                    'waktu_siap_diambil' => $ready ? now() : null,
                    'waktu_diambil' => $source === 'SUDAH_DIAMBIL' ? now() : null,
                    'alasan_pembatalan' => $source === 'DIBATALKAN' ? 'Riwayat fixture' : null,
                ]);
                if ($paid) {
                    DB::table('payments')->insert(['business_id' => $business->id, 'transaction_id' => $id,
                        'request_key' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64),
                        'jumlah' => 7000, 'metode' => 'tunai', 'waktu' => now(), 'recorded_by' => $owner->id, 'created_at' => now()]);
                }
                $historyBefore = DB::table('status_histories')->where('transaction_id', $id)->count();
                $version = DB::table('transactions')->find($id)->version;
                $allowed = $source === $target || ($forward[$source] ?? null) === $target
                    || ($target === 'DIBATALKAN' && in_array($source, ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL'], true));
                $response = $this->actingAs($owner)->post('/app/transactions/'.$id.'/status', [
                    'status' => $target, 'expected_version' => $version, 'alasan_pembatalan' => 'Salah input',
                ]);
                $response->assertStatus($allowed ? 302 : 422);
                $after = DB::table('transactions')->find($id);
                $this->assertSame($allowed ? $target : $source, $after->status, "$source → $target");
                $this->assertSame($historyBefore + (int) ($allowed && $source !== $target), DB::table('status_histories')->where('transaction_id', $id)->count(), "$source → $target");
                $this->assertSame($version + (int) ($allowed && $source !== $target), $after->version, "$source → $target");
            }
        }
    }
}
