<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Input;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BranchService
{
    public function save(User $actor, array $data, ?int $id = null): Branch
    {
        return app(BusinessTransaction::class)->run($actor, $actor->business_id, function (Business $business, User $fresh) use ($data, $id) {
            $branch = $id ? Branch::query()->findOrFail($id) : new Branch;
            Gate::forUser($fresh)->authorize($id ? 'update' : 'create', $id ? $branch : Branch::class);
            $data['telepon'] = Input::phone((string) ($data['telepon'] ?? ''));
            $valid = Validator::make($data, ['nama' => ['required', 'string', 'max:100'], 'alamat' => ['required', 'string', 'max:5000'], 'telepon' => ['required', 'regex:/^62[1-9][0-9]{7,12}$/'], 'is_active' => ['required', 'boolean']])->validate();
            if ($id && ! $valid['is_active'] && Transaction::query()->where('branch_id', $id)->whereIn('status', ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL'])->exists()) {
                throw ValidationException::withMessages(['is_active' => 'Cabang masih memiliki cucian aktif. Selesaikan atau batalkan transaksi terlebih dahulu.']);
            }
            $branch->forceFill([...$valid, 'business_id' => $business->id])->save();

            return $branch;
        });
    }
}
