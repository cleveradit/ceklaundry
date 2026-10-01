<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoFixtureSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoProvisioner
{
    /** @return array{business: Business, owner: User, admin: User} */
    public function provision(): array
    {
        return DB::transaction(function () {
            $at = CarbonImmutable::now('Asia/Jakarta');
            $business = new Business;
            $business->forceFill([
                'nama' => 'Laundry Demo', 'is_active' => true, 'is_demo' => true,
                'demo_expires_at' => $at->addDays(7), 'active_until' => null,
                'wa_enabled' => true, 'wa_provider' => 'fonnte', 'wa_token' => 'demo-tanpa-jaringan',
                'wa_sender_number' => '6281200000000', 'email_sender_name' => 'Laundry Demo',
                'email_sender_address' => 'demo@example.invalid',
                'created_at' => $at, 'updated_at' => $at,
            ])->save();
            $identity = app(DemoIdentity::class);
            $owner = (new User)->forceFill([
                'nama' => 'Pemilik Demo', 'email' => $identity->email($business->id, 'owner'),
                'password' => Str::random(64), 'role' => 'owner', 'business_id' => $business->id,
                'is_active' => true, 'must_change_password' => false,
            ]);
            $owner->save();
            $result = app(TenantContext::class)->run($business->id, fn () => app(DemoFixtureSeeder::class)->seed($business, $owner), $owner);

            return ['business' => $business, 'owner' => $owner, 'admin' => $result];
        }, 3);
    }
}
