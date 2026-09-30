<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoyaltySettingsService
{
    public function save(User $actor, array $data): void
    {
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($data) {
            abort_unless($fresh->role === 'owner', 403);
            $valid = Validator::make($data, [
                'is_active' => ['required', 'boolean'],
                'stempel_dibutuhkan' => ['required', 'integer', 'between:1,255'],
                'master_service_id' => ['nullable', 'integer'],
                'berat_maks_gratis' => ['nullable', 'numeric', 'gt:0', 'max:9999.9', 'decimal:0,1'],
            ])->validate();
            if ($valid['is_active'] && (empty($valid['master_service_id']) || empty($valid['berat_maks_gratis']))) {
                throw ValidationException::withMessages(['master_service_id' => 'Pilih layanan hadiah kg dan batas berat sebelum mengaktifkan program.']);
            }
            if (! empty($valid['master_service_id'])) {
                $master = DB::table('master_services')->where('business_id', $business->id)
                    ->where('id', $valid['master_service_id'])->first();
                abort_unless($master, 404);
                if (! $master->is_active || $master->satuan !== 'kg') {
                    throw ValidationException::withMessages(['master_service_id' => 'Hadiah harus layanan master kg yang aktif.']);
                }
            }
            DB::table('loyalty_settings')->where('business_id', $business->id)->update([
                'is_active' => $valid['is_active'],
                'stempel_dibutuhkan' => $valid['stempel_dibutuhkan'],
                'master_service_id' => $valid['master_service_id'] ?? null,
                'berat_maks_gratis' => $valid['berat_maks_gratis'] ?? null,
                'updated_at' => now('Asia/Jakarta'),
            ]);
        });
    }
}
