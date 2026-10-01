import AppLayout from '@/Layouts/AppLayout';
import ReportFilters from '@/Components/Owner/ReportFilters';
import RevenueChart, { type Bucket } from '@/Components/Owner/RevenueChart';
import type { Branch, Filters } from '@/Components/Owner/report-types';
import { rupiah } from '@/lib/utils';

export default function RevenueReport({ total, buckets, branches, filters }: { total: number; buckets: Bucket[]; branches: Branch[]; filters: Filters }) {
  return <AppLayout title="Pendapatan" subtitle="Uang yang diterima berdasarkan tanggal pembayaran dalam WIB.">
    <ReportFilters branches={branches} filters={filters} action="/owner/reports/revenue" revenue />
    <section className="panel padded"><p>Total pendapatan · {filters.start} sampai {filters.end}</p><strong className="report-total" data-testid="revenue-total">{rupiah(total)}</strong><p className="form-hint">Pembayaran dari transaksi yang kini dibatalkan dikeluarkan, termasuk dari laporan periode lampau. Pengembalian dana ditangani di luar aplikasi.</p></section>
    <RevenueChart buckets={buckets} />
  </AppLayout>;
}
