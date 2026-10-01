import { Link } from '@inertiajs/react';
import { rupiah } from '@/lib/utils';
import type { ReportPage } from './report-types';

export default function ReportRows({ transactions }: { transactions: ReportPage }) {
  return <section className="panel">
    <div className="section-heading"><h2>{transactions.total} transaksi</h2><span>Halaman {transactions.current_page} dari {transactions.last_page}</span></div>
    {transactions.data.length === 0 ? <p className="ops-empty">Tidak ada transaksi yang cocok.</p> : <div className="report-records">{transactions.data.map(row => <article className="report-record" key={row.id}>
      <div><Link className="report-code" href={`/app/transactions/${row.id}`}>{row.kode_resi}</Link><p>{row.customer_name}</p><small>{row.branch_name} · {row.waktu_masuk} WIB</small></div>
      <div><span className={row.status === 'DIBATALKAN' ? 'pill pill-muted' : 'pill'}>{row.status.replaceAll('_', ' ')}</span><p>{row.status_bayar.replaceAll('_', ' ')}</p></div>
      <dl className="report-money"><div><dt>Total</dt><dd>{rupiah(row.total_akhir)}</dd></div><div><dt>Terbayar</dt><dd>{rupiah(row.paid)}</dd></div><div><dt>Sisa</dt><dd>{rupiah(row.remaining)}</dd></div></dl>
    </article>)}</div>}
    <nav className="report-pagination" aria-label="Halaman laporan">{transactions.prev_page_url && <Link className="button button-outline" href={transactions.prev_page_url}>Sebelumnya</Link>}{transactions.next_page_url && <Link className="button button-outline" href={transactions.next_page_url}>Berikutnya</Link>}</nav>
  </section>;
}
