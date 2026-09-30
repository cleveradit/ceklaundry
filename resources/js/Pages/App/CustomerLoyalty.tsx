import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

type Entry = { jenis: string; jumlah: number; created_at: string; kode_resi: string; transaction_id: number };
export default function CustomerLoyalty({ customer, history }: { customer: { nama: string; saldo: number }; history: Entry[] }) {
  return <AppLayout title={`Stempel ${customer.nama}`} subtitle={`Saldo keseluruhan: ${customer.saldo} stempel. Saldo negatif berarti stempel yang telah dipakai perlu diperoleh kembali.`} action={<Link className="button button-outline" href="/app/customers">Kembali</Link>}>
    <section className="panel"><h2>Riwayat stempel yang dapat Anda lihat</h2>{history.length === 0 ? <p>Belum ada riwayat di cabang yang dapat Anda akses.</p> : history.map((entry, index) => <div className="ops-row" key={index}><strong>{entry.jenis.replaceAll('_', ' ')}</strong><span>{entry.jumlah > 0 ? '+' : ''}{entry.jumlah}</span><Link href={`/app/transactions/${entry.transaction_id}`}>{entry.kode_resi}</Link><small>{entry.created_at} WIB</small></div>)}</section>
  </AppLayout>;
}
