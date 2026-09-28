import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { rupiah } from '@/lib/utils';
type Row = { id: number; kode_resi: string; customer_name: string; status: string; status_bayar: string; total_akhir: number; waktu_masuk: string };
export default function Transactions({ transactions, query }: { transactions: Row[]; query: string }) {
  return <AppLayout title="Transaksi" subtitle="Cari berdasarkan kode resi, nama pelanggan, atau nomor HP." action={<Link className="button button-primary" href="/app/transactions/create">Transaksi baru</Link>}>
    <form action="/app/transactions" method="get" className="panel ops-form"><label className="field"><span>Kata pencarian</span><input name="q" defaultValue={query} placeholder="Kode resi, nama, atau nomor HP" /></label><button className="button button-outline" type="submit">Cari</button></form>
    <section className="panel">{transactions.length === 0 ? <p className="ops-empty">Tidak ada transaksi yang cocok.</p> : transactions.map(row => <Link href={`/app/transactions/${row.id}`} className="ops-row" key={row.id}><strong>{row.kode_resi}</strong><span>{row.customer_name}</span><span>{row.status.replaceAll('_', ' ')} · {row.status_bayar}</span><span>{rupiah(row.total_akhir)}</span></Link>)}</section>
  </AppLayout>;
}
