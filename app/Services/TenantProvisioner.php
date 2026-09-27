<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\LoyaltySetting;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class TenantProvisioner
{
    public function provision(User $actor, array $data): Business
    {
        return DB::transaction(function () use ($actor, $data) {
            $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
            Gate::forUser($fresh)->authorize('create', Business::class);
            $valid = Validator::make($data, ['nama' => ['required', 'string', 'max:150'], 'active_until' => ['required', 'date_format:Y-m-d'], 'owner' => ['required', 'array']])->validate();
            $owner = app(AccountService::class)->validateAccount($valid['owner']);
            $business = new Business;
            $business->forceFill(['nama' => $valid['nama'], 'active_until' => $valid['active_until'], 'is_active' => true, 'is_demo' => false])->save();
            app(TenantContext::class)->run($business->id, function () use ($business, $owner) {
                (new User)->forceFill([...$owner, 'role' => 'owner', 'business_id' => $business->id, 'must_change_password' => true])->save();
                (new BusinessSetting)->forceFill(['business_id' => $business->id])->save();
                (new LoyaltySetting)->forceFill(['business_id' => $business->id])->save();
            });

            return $business;
        }, 3);
    }
}
