<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\LoyaltySetting;
use App\Models\MasterService;
use App\Models\Service;
use App\Models\User;
use App\Support\ServiceName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceCatalogService
{
    public function save(User $actor, array $data, ?int $id = null, ?int $branchId = null): Model
    {
        return app(BusinessTransaction::class)->run($actor, $actor->business_id, function (Business $business, User $fresh) use ($data, $id, $branchId) {
            $class = $branchId === null ? MasterService::class : Service::class;
            $service = $id ? $class::query()->findOrFail($id) : new $class;
            Gate::forUser($fresh)->authorize($id ? 'update' : 'create', $id ? $service : $class);
            if ($branchId !== null) {
                $branch = Branch::query()->findOrFail($branchId);
                abort_if($id && $service->branch_id !== $branchId, 404);
                if (! $branch->is_active) {
                    throw ValidationException::withMessages(['branch_id' => 'Cabang tidak aktif.']);
                }
            }
            $data['nama'] = ServiceName::normalize((string) ($data['nama'] ?? ''));
            $unique = Rule::unique($service->getTable(), 'nama')->where('business_id', $business->id)->ignore($id);
            if ($branchId !== null) {
                $unique->where('branch_id', $branchId);
            }
            $valid = Validator::make($data, [
                'nama' => ['required', 'string', 'max:100', $unique], 'satuan' => ['required', Rule::in(['kg', 'item'])],
                'harga' => ['required', 'integer', 'min:1', 'max:4294967295'], 'durasi_jam' => ['required', 'integer', 'min:1', 'max:65535'],
                'berat_minimum' => ['nullable', 'numeric', 'min:0.1', 'max:9999.9', 'decimal:0,1'], 'is_active' => ['required', 'boolean'],
            ])->validate();
            if ($valid['satuan'] === 'item' && ($valid['berat_minimum'] ?? null) !== null) {
                throw ValidationException::withMessages(['berat_minimum' => 'Layanan satuan tidak memiliki berat minimum.']);
            }
            if ($id && $branchId === null && (! $valid['is_active'] || $valid['satuan'] !== 'kg') && LoyaltySetting::query()->where('is_active', true)->where('master_service_id', $id)->exists()) {
                throw ValidationException::withMessages(['is_active' => 'Layanan masih dipakai sebagai hadiah aktif. Matikan program atau ganti hadiah terlebih dahulu.']);
            }
            $service->forceFill([...$valid, 'business_id' => $business->id]);
            if ($branchId !== null) {
                $service->branch_id = $branchId;
            }
            $service->save();

            return $service;
        });
    }
}
