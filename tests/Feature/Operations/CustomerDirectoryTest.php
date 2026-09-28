<?php

namespace Tests\Feature\Operations;

use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\FoundationTestCase;

class CustomerDirectoryTest extends FoundationTestCase
{
    public function test_phone_normalization_uniqueness_and_shared_identity(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $customer = app(CustomerService::class)->save($owner, ['nama' => 'Rani', 'no_hp' => '08 139-876-5432', 'email' => 'RANI@EXAMPLE.TEST']);
        $this->assertSame('6281398765432', DB::table('customers')->find($customer)->no_hp);
        $this->assertSame('rani@example.test', DB::table('customers')->find($customer)->email);
        try {
            app(CustomerService::class)->save($owner, ['nama' => 'Nama Sama', 'no_hp' => '+6281398765432']);
            $this->fail('Nomor ekuivalen diterima dua kali.');
        } catch (ValidationException) {
            $this->assertSame(1, DB::table('customers')->where('business_id', $business->id)->count());
        }
        $other = app(CustomerService::class)->save($owner, ['nama' => 'Rani', 'no_hp' => '081399999999']);
        $this->assertNotSame($customer, $other);
        $tx = $this->transaction($business, $owner, $branch->id, ['customer_id' => $customer, 'notification_email' => 'lama@example.test']);
        app(CustomerService::class)->save($owner, ['nama' => 'Rani Baru', 'no_hp' => '081322222222', 'email' => 'baru@example.test'], $customer);
        $this->assertSame($customer, DB::table('transactions')->find($tx)->customer_id);
        $this->assertSame('lama@example.test', DB::table('transactions')->find($tx)->notification_email);
        $admin = User::factory()->create(['business_id' => $business->id, 'branch_id' => $branch->id, 'role' => 'admin']);
        $this->actingAs($admin)->get('/app/customers?q=Rani')->assertOk()->assertSee('Rani Baru');
        $this->get('/app/customers/lookup?q=081322222222')->assertOk()->assertJsonCount(1, 'customers')->assertJsonPath('customers.0.id', $customer);
        [$otherBusiness, $otherOwner] = $this->tenant();
        $this->actingAs($otherOwner)->get('/app/customers/lookup?q=Rani')->assertOk()->assertJsonCount(0, 'customers');
    }
}
