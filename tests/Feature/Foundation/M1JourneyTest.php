<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use App\Services\MasterSyncService;
use Illuminate\Support\Facades\DB;
use Tests\FoundationTestCase;

class M1JourneyTest extends FoundationTestCase
{
    public function test_bootstrap_to_admin_catalog_and_second_business_isolation(): void
    {
        $password = $this->password();
        $this->artisan('app:bootstrap-developer')->expectsQuestion('Nama developer', 'Developer Uji')->expectsQuestion('Email developer', 'journey@example.test')->expectsQuestion('Password awal (minimal 12 karakter, maksimal 72 byte)', $password)->expectsOutput('Akun developer dibuat. Ganti password saat pertama masuk.')->assertSuccessful();
        $dev = User::where('email', 'journey@example.test')->firstOrFail();
        $this->actingAs($dev)->get('/dev')->assertRedirect('/password/change');
        $new = $this->password();
        $this->post('/password/change', ['current_password' => $password, 'password' => $new, 'password_confirmation' => $new])->assertSessionHasNoErrors();
        $this->post('/dev/businesses', ['nama' => 'Laundry Perjalanan', 'active_until' => now()->addMonth()->toDateString(), 'owner' => ['nama' => 'Pemilik', 'email' => 'owner-journey@example.test', 'password' => $password]])->assertSessionHasNoErrors()->assertRedirect('/dev');
        $owner = User::where('email', 'owner-journey@example.test')->firstOrFail();
        $this->actingAs($owner)->post('/password/change', ['current_password' => $password, 'password' => $new, 'password_confirmation' => $new])->assertSessionHasNoErrors();
        $this->post('/owner/branches', ['nama' => 'Utama', 'alamat' => 'Jalan 1', 'telepon' => '+62 812-3456-7890', 'is_active' => true])->assertSessionHasNoErrors();
        $branch = DB::table('branches')->where('business_id', $owner->business_id)->value('id');
        $this->post('/owner/admins', ['nama' => 'Petugas', 'email' => 'admin-journey@example.test', 'password' => $password, 'branch_id' => $branch, 'is_active' => true])->assertSessionHasNoErrors();
        $this->post('/owner/masters', ['nama' => 'Cuci Lipat', 'harga' => 7000, 'satuan' => 'kg', 'durasi_jam' => 24, 'is_active' => true])->assertSessionHasNoErrors();
        $preview = app(MasterSyncService::class)->preview($owner->fresh(), [$branch]);
        $this->post('/owner/sync', ['branches' => [$branch], 'fingerprint' => $preview['fingerprint'], 'confirmed' => true])->assertSessionHasNoErrors();
        [$other,$otherOwner] = $this->tenant($dev->fresh());
        $otherBranch = $this->branch($otherOwner);
        $this->get('/owner/branches/'.$otherBranch->id.'/services')->assertNotFound();
        $admin = User::where('email', 'admin-journey@example.test')->firstOrFail();
        $this->actingAs($admin)->post('/password/change', ['current_password' => $password, 'password' => $new, 'password_confirmation' => $new])->assertSessionHasNoErrors();
        $this->get('/app/transactions/create')->assertOk()->assertSee('Cuci Lipat');
        $this->get('/owner/branches')->assertForbidden();
    }
}
