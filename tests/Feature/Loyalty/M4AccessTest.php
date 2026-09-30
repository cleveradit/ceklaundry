<?php

namespace Tests\Feature\Loyalty;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\FoundationTestCase;

class M4AccessTest extends FoundationTestCase
{
    public function test_admin_sees_global_balance_but_only_own_branch_ledger(): void
    {
        [$business, $owner] = $this->tenant();
        $branch1 = $this->branch($owner);
        $branch2 = $this->branch($owner, 'Cabang Kedua');
        $customerId = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Rani',
            'no_hp' => '6281234567890', 'stamp_count' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $firstId = $this->transaction($business, $owner, $branch1->id);
        $secondId = $this->transaction($business, $owner, $branch2->id);
        DB::table('transactions')->whereIn('id', [$firstId, $secondId])->update(['customer_id' => $customerId]);
        foreach ([$firstId, $secondId] as $id) {
            DB::table('loyalty_histories')->insert(['business_id' => $business->id, 'customer_id' => $customerId,
                'transaction_id' => $id, 'jenis' => 'perolehan', 'jumlah' => 1, 'created_at' => now()]);
        }
        $code1 = DB::table('transactions')->find($firstId)->kode_resi;
        $code2 = DB::table('transactions')->find($secondId)->kode_resi;
        $this->actingAs($owner)->get('/app/customers/'.$customerId.'/loyalty')->assertOk()
            ->assertSee($code1)->assertSee($code2)->assertInertia(fn (Assert $page) => $page->component('App/CustomerLoyalty')->where('customer.saldo', 2)->has('history', 2)->etc());
        $admin = User::factory()->create(['role' => 'admin', 'business_id' => $business->id,
            'branch_id' => $branch1->id, 'must_change_password' => false]);
        $this->actingAs($admin)->get('/app/customers/'.$customerId.'/loyalty')->assertOk()
            ->assertSee($code1)->assertDontSee($code2)->assertInertia(fn (Assert $page) => $page->component('App/CustomerLoyalty')->where('customer.saldo', 2)->has('history', 1)->etc());
        $this->get('/owner/promos')->assertForbidden();
        [$other] = $this->tenant();
        $foreignId = DB::table('customers')->insertGetId(['business_id' => $other->id, 'nama' => 'Lain',
            'no_hp' => '6281234567891', 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/app/customers/'.$foreignId.'/loyalty')->assertNotFound();
    }
}
