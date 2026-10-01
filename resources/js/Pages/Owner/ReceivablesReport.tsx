import AppLayout from '@/Layouts/AppLayout';
import ReportFilters from '@/Components/Owner/ReportFilters';
import ReportRows from '@/Components/Owner/ReportRows';
import type { Branch, Filters, ReportPage } from '@/Components/Owner/report-types';
import { rupiah } from '@/lib/utils';

export default function ReceivablesReport({ total, transactions, branches, filters }: { total: number; transactions: ReportPage; branches: Branch[]; filters: Filters }) {
  return <AppLayout title="Tagihan berjalan" subtitle="Sisa pembayaran dari cucian aktif yang belum lunas.">
    <ReportFilters branches={branches} filters={filters} action="/owner/reports/receivables" branchOnly />
    <section className="panel padded"><p>Total seluruh tagihan berjalan</p><strong className="report-total" data-testid="receivables-total">{rupiah(total)}</strong></section>
    <ReportRows transactions={transactions} />
  </AppLayout>;
}
