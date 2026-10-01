<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OwnerReportService
{
    private const STATUSES = ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL', 'SUDAH_DIAMBIL', 'DIBATALKAN'];

    public const CSV_HEADERS = ['kode_resi', 'cabang', 'nama_pelanggan', 'no_hp', 'status', 'status_bayar', 'subtotal', 'potongan_stempel', 'potongan_promo', 'total', 'total_terbayar', 'sisa', 'waktu_masuk', 'estimasi_selesai', 'waktu_siap_diambil', 'waktu_diambil'];

    public function filters(User $actor, array $input, bool $revenue = false): array
    {
        $this->owner($actor);
        $values = Validator::make($input, [
            'branch_id' => ['nullable', 'integer', 'min:1'],
            'start' => ['nullable', 'date_format:Y-m-d', 'required_with:end'],
            'end' => ['nullable', 'date_format:Y-m-d', 'required_with:start', 'after_or_equal:start'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'status_bayar' => ['nullable', Rule::in(['BELUM_BAYAR', 'DP', 'LUNAS'])],
            'period' => ['nullable', Rule::in(['today', 'seven_days', 'month', 'custom'])],
            'granularity' => ['nullable', Rule::in(['day', 'month'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [], ['start' => 'tanggal awal', 'end' => 'tanggal akhir', 'branch_id' => 'cabang'])->validate();
        $branchId = isset($values['branch_id']) ? (int) $values['branch_id'] : null;
        if ($branchId) {
            abort_unless(DB::table('branches')->where('business_id', $actor->business_id)->where('id', $branchId)->exists(), 404);
        }
        $start = $values['start'] ?? null;
        $end = $values['end'] ?? null;
        $period = $values['period'] ?? ($start ? 'custom' : 'month');
        if ($revenue) {
            $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
            if ($period === 'custom') {
                Validator::make(['start' => $start, 'end' => $end], ['start' => 'required', 'end' => 'required'])->validate();
            } else {
                $start = match ($period) {
                    'today' => $today->toDateString(),
                    'seven_days' => $today->subDays(6)->toDateString(),
                    default => $today->startOfMonth()->toDateString(),
                };
                $end = $today->toDateString();
            }
        }

        return ['branch_id' => $branchId, 'start' => $start, 'end' => $end,
            'status' => $values['status'] ?? null, 'status_bayar' => $values['status_bayar'] ?? null,
            'period' => $period, 'granularity' => $values['granularity'] ?? 'day', 'page' => (int) ($values['page'] ?? 1)];
    }

    public function history(User $actor, array $filters): array
    {
        $this->owner($actor);

        return app(ReportSnapshot::class)->read(function (Connection $db) use ($actor, $filters) {
            $rows = $this->historyQuery($db, $actor, $filters)->orderByDesc('t.waktu_masuk')->orderByDesc('t.id')
                ->paginate(25, $this->columns(), 'page', $filters['page'])->withQueryString();
            $rows->through(fn ($row) => $this->row($row));

            return ['transactions' => $rows, 'branches' => $this->branches($db, $actor), 'filters' => $filters];
        });
    }

    public function revenue(User $actor, array $filters): array
    {
        $this->owner($actor);

        return app(ReportSnapshot::class)->read(function (Connection $db) use ($actor, $filters) {
            $query = $this->paymentQuery($db, $actor, $filters);
            $total = (int) (clone $query)->sum('p.jumlah');
            $format = $filters['granularity'] === 'month' ? '%Y-%m' : '%Y-%m-%d';
            $values = (clone $query)->selectRaw("DATE_FORMAT(p.waktu, '{$format}') as bucket, SUM(p.jumlah) as total")
                ->groupBy('bucket')->pluck('total', 'bucket');
            $date = CarbonImmutable::parse($filters['start'], 'Asia/Jakarta')->startOfDay();
            $end = CarbonImmutable::parse($filters['end'], 'Asia/Jakarta')->startOfDay();
            if ($filters['granularity'] === 'month') {
                $date = $date->startOfMonth();
            }
            $buckets = [];
            while ($date <= $end) {
                $key = $date->format($filters['granularity'] === 'month' ? 'Y-m' : 'Y-m-d');
                $buckets[] = ['date' => $key, 'total' => (int) ($values[$key] ?? 0)];
                $date = $filters['granularity'] === 'month' ? $date->addMonth() : $date->addDay();
            }

            return ['total' => $total, 'buckets' => $buckets, 'filters' => $filters, 'branches' => $this->branches($db, $actor)];
        });
    }

    public function receivables(User $actor, array $filters): array
    {
        $this->owner($actor);

        return app(ReportSnapshot::class)->read(function (Connection $db) use ($actor, $filters) {
            $query = $this->receivableQuery($db, $actor, $filters);
            $total = (int) (clone $query)->sum(DB::raw('CAST(t.total_akhir AS SIGNED) - COALESCE(p.paid, 0)'));
            $rows = $query->orderBy('t.waktu_masuk')->orderBy('t.id')
                ->paginate(25, $this->columns(), 'page', $filters['page'])->withQueryString();
            $rows->through(fn ($row) => $this->row($row));

            return ['transactions' => $rows, 'total' => $total, 'branches' => $this->branches($db, $actor), 'filters' => $filters];
        });
    }

    public function dashboard(User $actor): array
    {
        $this->owner($actor);
        $now = CarbonImmutable::now('Asia/Jakarta');
        $today = $now->startOfDay();
        $filters = ['branch_id' => null, 'start' => $today->toDateString(), 'end' => $today->toDateString()];

        return app(ReportSnapshot::class)->read(function (Connection $db) use ($actor, $now, $today, $filters) {
            $todayQuery = $db->table('transactions')->where('business_id', $actor->business_id)
                ->where('waktu_masuk', '>=', $today)->where('waktu_masuk', '<', $today->addDay())->where('status', '<>', 'DIBATALKAN');
            $kg = $db->table('transaction_items as i')->join('transactions as t', 't.id', '=', 'i.transaction_id')
                ->where('t.business_id', $actor->business_id)->where('t.status', '<>', 'DIBATALKAN')
                ->where('t.waktu_masuk', '>=', $today)->where('t.waktu_masuk', '<', $today->addDay())
                ->where('i.satuan_snapshot', 'kg')->sum('i.berat_kg');
            $days = (int) $db->table('business_settings')->where('business_id', $actor->business_id)->value('reminder_first_days');

            return ['summary' => ['today_count' => (clone $todayQuery)->count(), 'today_kg' => number_format((float) $kg, 1, '.', ''),
                'revenue' => (int) $this->paymentQuery($db, $actor, $filters)->sum('p.jumlah'),
                'receivables' => (int) $this->receivableQuery($db, $actor, ['branch_id' => null])->sum(DB::raw('CAST(t.total_akhir AS SIGNED) - COALESCE(p.paid, 0)')),
                'piled_up' => $db->table('transactions')->where('business_id', $actor->business_id)->where('status', 'SIAP_DIAMBIL')
                    ->where('waktu_siap_diambil', '<=', $now->subDays($days))->count(), 'reminder_days' => $days, 'as_of' => $now->toIso8601String()],
                'branchCount' => $db->table('branches')->where('business_id', $actor->business_id)->count(),
                'adminCount' => $db->table('users')->where('business_id', $actor->business_id)->where('role', 'admin')->count(),
                'serviceCount' => $db->table('master_services')->where('business_id', $actor->business_id)->count()];
        });
    }

    public function export(User $actor, array $filters, $stream): void
    {
        $this->owner($actor);
        app(ReportSnapshot::class)->read(function (Connection $db) use ($actor, $filters, $stream) {
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, self::CSV_HEADERS, ',', '"', '', "\r\n");
            foreach ($this->historyQuery($db, $actor, $filters)->select($this->columns())->orderByDesc('t.waktu_masuk')->orderByDesc('t.id')->lazy(500) as $value) {
                $row = $this->row($value);
                $texts = array_map($this->csvText(...), [$row['kode_resi'], $row['branch_name'], $row['customer_name'], $row['no_hp'], $row['status'], $row['status_bayar']]);
                $dates = array_map(fn ($key) => $row[$key] ? CarbonImmutable::parse($row[$key], 'Asia/Jakarta')->toIso8601String() : '', ['waktu_masuk', 'estimasi_selesai', 'waktu_siap_diambil', 'waktu_diambil']);
                fputcsv($stream, [...$texts, $row['subtotal'], $row['potongan_stempel'], $row['potongan_promo'], $row['total_akhir'], $row['paid'], $row['remaining'], ...$dates], ',', '"', '', "\r\n");
            }
        });
    }

    private function csvText(string $value): string
    {
        return preg_match('/^[\s\x00-\x20]*[=+\-@]/u', $value) || preg_match('/^[\t\r\n]/', $value) ? "'".$value : $value;
    }

    private function owner(User $actor): void
    {
        abort_unless($actor->role === 'owner' && $actor->business_id, 403);
    }

    private function branches(Connection $db, User $actor): mixed
    {
        return $db->table('branches')->where('business_id', $actor->business_id)->orderBy('nama')->get(['id', 'nama', 'is_active']);
    }

    private function rowsQuery(Connection $db, User $actor, array $filters): Builder
    {
        $payments = $db->table('payments')->where('business_id', $actor->business_id)->selectRaw('transaction_id, SUM(jumlah) as paid')->groupBy('transaction_id');
        $query = $db->table('transactions as t')->join('branches as b', function ($join) {
            $join->on('b.id', '=', 't.branch_id')->on('b.business_id', '=', 't.business_id');
        })->join('customers as c', function ($join) {
            $join->on('c.id', '=', 't.customer_id')->on('c.business_id', '=', 't.business_id');
        })->leftJoinSub($payments, 'p', 'p.transaction_id', '=', 't.id')->where('t.business_id', $actor->business_id);
        if ($filters['branch_id']) {
            $query->where('t.branch_id', $filters['branch_id']);
        }

        return $query;
    }

    private function historyQuery(Connection $db, User $actor, array $filters): Builder
    {
        $query = $this->rowsQuery($db, $actor, $filters);
        $this->dates($query, 't.waktu_masuk', $filters);
        foreach (['status', 'status_bayar'] as $key) {
            if ($filters[$key]) {
                $query->where('t.'.$key, $filters[$key]);
            }
        }

        return $query;
    }

    private function receivableQuery(Connection $db, User $actor, array $filters): Builder
    {
        return $this->rowsQuery($db, $actor, $filters)->whereIn('t.status', ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL'])
            ->whereIn('t.status_bayar', ['BELUM_BAYAR', 'DP'])->whereRaw('t.total_akhir > COALESCE(p.paid, 0)');
    }

    private function paymentQuery(Connection $db, User $actor, array $filters): Builder
    {
        $query = $db->table('payments as p')->join('transactions as t', function ($join) {
            $join->on('t.id', '=', 'p.transaction_id')->on('t.business_id', '=', 'p.business_id');
        })->where('p.business_id', $actor->business_id)->where('t.status', '<>', 'DIBATALKAN');
        if ($filters['branch_id']) {
            $query->where('t.branch_id', $filters['branch_id']);
        }
        $this->dates($query, 'p.waktu', $filters);

        return $query;
    }

    private function dates(Builder $query, string $column, array $filters): void
    {
        if ($filters['start']) {
            $query->where($column, '>=', CarbonImmutable::parse($filters['start'], 'Asia/Jakarta')->startOfDay())
                ->where($column, '<', CarbonImmutable::parse($filters['end'], 'Asia/Jakarta')->startOfDay()->addDay());
        }
    }

    private function columns(): array
    {
        return ['t.id', 't.kode_resi', 'b.nama as branch_name', 'c.nama as customer_name', 'c.no_hp', 't.status', 't.status_bayar',
            't.subtotal', 't.potongan_stempel', 't.potongan_promo', 't.total_akhir', 't.waktu_masuk', 't.estimasi_selesai', 't.waktu_siap_diambil', 't.waktu_diambil', DB::raw('COALESCE(p.paid, 0) as paid')];
    }

    private function row(object $value): array
    {
        $row = (array) $value;
        foreach (['id', 'subtotal', 'potongan_stempel', 'potongan_promo', 'total_akhir', 'paid'] as $key) {
            $row[$key] = (int) $row[$key];
        }
        $row['remaining'] = $row['total_akhir'] - $row['paid'];

        return $row;
    }
}
