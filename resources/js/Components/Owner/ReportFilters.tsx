import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import type { Shared } from '@/types';
import type { Branch, Filters } from './report-types';

export default function ReportFilters({ branches, filters, action, revenue = false, branchOnly = false }: { branches: Branch[]; filters: Filters; action: string; revenue?: boolean; branchOnly?: boolean }) {
  const [period, setPeriod] = useState(filters.period);
  const { errors } = usePage<Shared & { errors: Record<string, string> }>().props;
  return <form action={action} method="get" className="panel report-filters">
    <label className="field"><span>Cabang</span><select name="branch_id" defaultValue={filters.branch_id ?? ''}><option value="">Semua cabang</option>{branches.map(branch => <option key={branch.id} value={branch.id}>{branch.nama}{!branch.is_active ? ' (nonaktif)' : ''}</option>)}</select></label>
    {revenue && <label className="field"><span>Periode</span><select name="period" value={period} onChange={event => setPeriod(event.target.value)}><option value="today">Hari ini</option><option value="seven_days">7 hari terakhir</option><option value="month">Bulan ini</option><option value="custom">Rentang tanggal</option></select></label>}
    {!branchOnly && (!revenue || period === 'custom') && <>
      <label className="field"><span>Tanggal awal</span><input name="start" type="date" defaultValue={filters.start ?? ''} required={revenue} /></label>
      <label className="field"><span>Tanggal akhir</span><input name="end" type="date" defaultValue={filters.end ?? ''} required={revenue} /></label>
    </>}
    {!branchOnly && !revenue && <>
      <label className="field"><span>Status transaksi</span><select name="status" defaultValue={filters.status ?? ''}><option value="">Semua status</option>{['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL', 'SUDAH_DIAMBIL', 'DIBATALKAN'].map(status => <option key={status} value={status}>{status.replaceAll('_', ' ')}</option>)}</select></label>
      <label className="field"><span>Status bayar</span><select name="status_bayar" defaultValue={filters.status_bayar ?? ''}><option value="">Semua pembayaran</option>{['BELUM_BAYAR', 'DP', 'LUNAS'].map(status => <option key={status} value={status}>{status.replaceAll('_', ' ')}</option>)}</select></label>
    </>}
    {revenue && <label className="field"><span>Grafik per</span><select name="granularity" defaultValue={filters.granularity}><option value="day">Hari</option><option value="month">Bulan</option></select></label>}
    <button className="button button-primary" type="submit">Terapkan filter</button>
    {Object.values(errors ?? {}).length > 0 && <p className="field-error" role="alert">{Object.values(errors).join(' ')}</p>}
  </form>;
}
