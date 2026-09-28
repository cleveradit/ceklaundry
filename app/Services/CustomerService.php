<?php

namespace App\Services;

use App\Models\User;
use App\Support\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function validated(array $data): array
    {
        $data['no_hp'] = Input::phone((string) ($data['no_hp'] ?? ''));
        $data['email'] = empty($data['email']) ? null : Input::email((string) $data['email']);
        $data['nama'] = trim((string) ($data['nama'] ?? ''));

        return Validator::make($data, [
            'nama' => ['required', 'string', 'max:100'],
            'no_hp' => ['required', 'regex:/^62[1-9][0-9]{7,12}$/D', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
        ])->validate();
    }

    public function save(User $actor, array $data, ?int $id = null): int
    {
        return app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($data, $id) {
            abort_unless(in_array($fresh->role, ['owner', 'admin'], true), 403);
            $valid = $this->validated($data);
            $customer = $id === null ? null : DB::table('customers')->where('business_id', $business->id)->where('id', $id)->lockForUpdate()->first();
            abort_if($id !== null && ! $customer, 404);
            $duplicate = DB::table('customers')->where('business_id', $business->id)->where('no_hp', $valid['no_hp'])->when($id, fn ($query) => $query->where('id', '<>', $id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['no_hp' => 'Nomor HP sudah terdaftar pada bisnis ini.']);
            }
            if ($customer) {
                DB::table('customers')->where('id', $id)->update([...$valid, 'updated_at' => now()]);

                return $id;
            }

            return DB::table('customers')->insertGetId([...$valid, 'business_id' => $business->id, 'stamp_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function search(User $actor, string $query): array
    {
        abort_unless(in_array($actor->role, ['owner', 'admin'], true), 403);
        $needle = trim($query);
        if ($needle === '') {
            return [];
        }
        $phone = Input::phone($needle);

        return DB::table('customers')->where('business_id', $actor->business_id)->where(function ($builder) use ($needle, $phone) {
            $builder->where('nama', 'like', '%'.addcslashes($needle, '%_\\').'%')
                ->orWhere('no_hp', 'like', '%'.addcslashes($phone, '%_\\').'%');
        })->orderBy('nama')->limit(30)->get(['id', 'nama', 'no_hp', 'email', 'stamp_count'])->all();
    }
}
