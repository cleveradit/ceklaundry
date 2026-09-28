<?php

namespace Tests\Feature\Operations;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\FoundationTestCase;

class OperationsDashboardTest extends FoundationTestCase
{
    public function test_dashboard_and_search_are_branch_scoped_with_correct_late_states(): void
    {
        [$business, $owner] = $this->tenant();
        $branchA = $this->branch($owner, 'Cabang A');
        $branchB = $this->branch($owner, 'Cabang B');
        $late = $this->transaction($business, $owner, $branchA->id, ['waktu_masuk' => now()->subDays(2), 'estimasi_selesai' => now()->subHour()]);
        $other = $this->transaction($business, $owner, $branchB->id, ['waktu_masuk' => now()->subDays(2), 'estimasi_selesai' => now()->subHour()]);
        DB::table('transaction_items')->insert(['transaction_id' => $late, 'nama_layanan_snapshot' => 'Cuci', 'satuan_snapshot' => 'kg', 'harga_snapshot' => 7000, 'durasi_jam_snapshot' => 24, 'berat_kg' => '1.0', 'subtotal' => 7000, 'created_at' => now(), 'updated_at' => now()]);
        $admin = User::factory()->create(['business_id' => $business->id, 'branch_id' => $branchA->id, 'role' => 'admin']);
        $codeA = DB::table('transactions')->find($late)->kode_resi;
        $codeB = DB::table('transactions')->find($other)->kode_resi;
        $this->actingAs($admin)->get('/app')->assertOk()->assertSee($codeA)->assertDontSee($codeB);
        $this->actingAs($admin)->get('/app/transactions?q='.$codeB)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('App/Transactions')->has('transactions', 0)->etc());
        $this->actingAs($admin)->get('/app/transactions?q=Pelanggan')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('App/Transactions')->has('transactions', 1)->etc());
        $phoneA = DB::table('customers')->where('id', DB::table('transactions')->find($late)->customer_id)->value('no_hp');
        $this->actingAs($admin)->get('/app/transactions?q='.$phoneA)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('App/Transactions')->has('transactions', 1)->etc());
        $this->actingAs($admin)->get('/app/transactions?q=%25')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('App/Transactions')->has('transactions', 0)->etc());
        $this->actingAs($admin)->get('/app/transactions/'.$other)->assertNotFound();
        $this->actingAs($owner)->get('/app?branch_id='.$branchB->id)->assertOk()->assertSee($codeB)->assertDontSee($codeA);
        $this->actingAs($owner)->get('/app/transactions/'.$other)->assertOk();
    }
}
