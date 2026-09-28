<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperationalAccess
{
    public function branch(User $actor, int $branchId, bool $write = true): object
    {
        abort_unless(in_array($actor->role, ['owner', 'admin'], true), 403);
        abort_if($actor->role === 'admin' && (int) $actor->branch_id !== $branchId, 404);
        $branch = DB::table('branches')->where('business_id', $actor->business_id)->where('id', $branchId)->first();
        abort_unless($branch, 404);
        abort_if($write && ! $branch->is_active, 403, 'Cabang tidak aktif.');

        return $branch;
    }

    public function transaction(User $actor, int $id): object
    {
        abort_unless(in_array($actor->role, ['owner', 'admin'], true), 403);
        $query = DB::table('transactions')->where('business_id', $actor->business_id)->where('id', $id);
        if ($actor->role === 'admin') {
            $query->where('branch_id', $actor->branch_id);
        }

        return $query->first() ?? abort(404);
    }
}
