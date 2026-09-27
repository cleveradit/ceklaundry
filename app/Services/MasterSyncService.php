<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\MasterService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MasterSyncService
{
    private const ATTRIBUTES = ['nama', 'satuan', 'harga', 'durasi_jam', 'berat_minimum'];

    public function preview(User $actor, array $branchIds): array
    {
        return app(BusinessTransaction::class)->run($actor, $actor->business_id, fn (Business $business, User $fresh) => $this->snapshot($fresh, $branchIds));
    }

    private function snapshot(User $actor, array $branchIds): array
    {
        abort_unless($actor->role === 'owner', 403);
        Validator::make(['branches' => $branchIds], ['branches' => ['required', 'array', 'min:1', 'max:200'], 'branches.*' => ['required', 'integer', 'distinct']])->validate();
        sort($branchIds, SORT_NUMERIC);
        $masters = MasterService::query()->where('is_active', true)->orderBy('id')->get();
        $state = ['masters' => $masters->toArray(), 'branches' => []];
        $preview = [];
        foreach ($branchIds as $id) {
            $branch = Branch::query()->findOrFail($id);
            if (! $branch->is_active) {
                throw ValidationException::withMessages(['branches' => 'Semua cabang pilihan harus aktif.']);
            }
            $locals = Service::query()->where('branch_id', $id)->orderBy('id')->get();
            $state['branches'][] = ['branch' => $branch->toArray(), 'services' => $locals->toArray()];
            $changes = [];
            foreach ($masters as $master) {
                // SQL collation is the matching authority, including accent sensitivity.
                $local = Service::query()->where('branch_id', $id)->where('nama', $master->nama)->first();
                $changes[] = ['master_id' => $master->id, 'local_id' => $local?->id, 'nama' => $master->nama, 'action' => ! $local ? 'tambah' : (! $local->is_active ? 'aktifkan' : 'perbarui'), 'before' => $local?->only(self::ATTRIBUTES), 'after' => $master->only(self::ATTRIBUTES)];
            }
            $matched = array_column($changes, 'local_id');
            $preview[] = ['id' => $branch->id, 'nama' => $branch->nama, 'changes' => $changes, 'untouched' => $locals->whereNotIn('id', $matched)->pluck('nama')->values()->all()];
        }

        return ['fingerprint' => hash('sha256', json_encode($state, JSON_THROW_ON_ERROR)), 'branches' => $preview];
    }

    public function apply(User $actor, array $branchIds, string $fingerprint): void
    {
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function (Business $business, User $fresh) use ($branchIds, $fingerprint) {
            $preview = $this->snapshot($fresh, $branchIds);
            abort_unless(hash_equals($preview['fingerprint'], $fingerprint), 409, 'Data berubah sejak pratinjau. Periksa dan konfirmasi ulang.');
            foreach ($preview['branches'] as $branch) {
                foreach ($branch['changes'] as $change) {
                    $local = $change['local_id'] ? Service::query()->findOrFail($change['local_id']) : new Service;
                    $local->forceFill([...$change['after'], 'business_id' => $business->id, 'branch_id' => $branch['id'], 'is_active' => true])->save();
                }
            }
            Audit::record($fresh, $business->id, 'layanan.sinkron_master', ['branches' => $preview['branches']]);
        });
    }
}
