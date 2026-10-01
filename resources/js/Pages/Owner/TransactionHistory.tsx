import AppLayout from '@/Layouts/AppLayout';
import ReportFilters from '@/Components/Owner/ReportFilters';
import ReportRows from '@/Components/Owner/ReportRows';
import type { Branch, Filters, ReportPage } from '@/Components/Owner/report-types';

export default function TransactionHistory({ transactions, branches, filters }: { transactions: ReportPage; branches: Branch[]; filters: Filters }) {
  const query = new URLSearchParams();
  for (const key of ['branch_id', 'start', 'end', 'status', 'status_bayar'] as const) if (filters[key]) query.set(key, String(filters[key]));
  return <AppLayout title="Riwayat transaksi" subtitle="Seluruh cabang bisnis Anda. Rentang tanggal mengikuti waktu masuk dalam WIB." action={<a className="button button-outline" href={`/owner/reports/history.csv?${query}`}>Ekspor CSV</a>}>
    <ReportFilters branches={branches} filters={filters} action="/owner/reports/history" />
    <ReportRows transactions={transactions} />
  </AppLayout>;
}
