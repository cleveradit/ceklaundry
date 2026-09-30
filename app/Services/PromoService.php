<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PromoService
{
    public function save(User $actor, array $data, ?int $id = null): void
    {
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($data, $id) {
            abort_unless($fresh->role === 'owner', 403);
            $existing = $id === null ? null : DB::table('promos')->where('business_id', $business->id)->where('id', $id)->first();
            if ($id !== null) {
                abort_unless($existing, 404);
            }
            $valid = Validator::make($data, [
                'nama' => ['required', 'string', 'max:100'],
                'tipe' => ['required', 'in:persen,nominal'],
                'nilai' => ['required', 'integer', 'min:1', 'max:4294967295'],
                'minimal_total' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
                'mulai' => ['required', 'date_format:Y-m-d'],
                'selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:mulai'],
                'semua_cabang' => ['required', 'boolean'],
                'branch_ids' => ['array'],
                'branch_ids.*' => ['integer', 'distinct'],
                'is_active' => ['required', 'boolean'],
            ])->validate();
            if ($valid['tipe'] === 'persen' && $valid['nilai'] > 100) {
                throw ValidationException::withMessages(['nilai' => 'Persentase harus antara 1 dan 100.']);
            }
            $branches = $valid['semua_cabang'] ? [] : ($valid['branch_ids'] ?? []);
            if (! $valid['semua_cabang'] && count($branches) === 0) {
                throw ValidationException::withMessages(['branch_ids' => 'Pilih sedikitnya satu cabang.']);
            }
            if ($branches && DB::table('branches')->where('business_id', $business->id)->whereIn('id', $branches)->count() !== count($branches)) {
                throw ValidationException::withMessages(['branch_ids' => 'Cabang promo harus milik bisnis ini.']);
            }
            $values = [
                'nama' => trim(preg_replace('/\s+/u', ' ', $valid['nama'])),
                'tipe' => $valid['tipe'], 'nilai' => $valid['nilai'],
                'minimal_total' => $valid['minimal_total'] ?? null,
                'mulai' => $valid['mulai'], 'selesai' => $valid['selesai'],
                'semua_cabang' => $valid['semua_cabang'], 'is_active' => $valid['is_active'],
                'updated_at' => now('Asia/Jakarta'),
            ];
            if ($values['nama'] === '') {
                throw ValidationException::withMessages(['nama' => 'Nama promo wajib diisi.']);
            }
            if ($id === null) {
                $id = DB::table('promos')->insertGetId([...$values, 'business_id' => $business->id, 'created_at' => now('Asia/Jakarta')]);
            } else {
                DB::table('promos')->where('id', $id)->update($values);
                DB::table('promo_branches')->where('promo_id', $id)->delete();
            }
            foreach ($branches as $branchId) {
                DB::table('promo_branches')->insert(['promo_id' => $id, 'branch_id' => $branchId]);
            }
        });
    }
}
