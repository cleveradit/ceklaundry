import { Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Store, Users, Layers, CheckCircle2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import type { Shared } from '@/types';
import { rupiah } from '@/lib/utils';
type Summary = { today_count: number; today_kg: string; revenue: number; receivables: number; piled_up: number; reminder_days: number; as_of: string };
export default function Dashboard({ branchCount, adminCount, serviceCount, summary }: { branchCount: number; adminCount: number; serviceCount: number; summary: Summary }) {
  const { auth } = usePage<Shared>().props;
  const historyToday = `/owner/reports/history?start=${summary.as_of.slice(0, 10)}&end=${summary.as_of.slice(0, 10)}`;
  return <AppLayout title={`Halo, ${auth?.nama.split(' ')[0] ?? 'Owner'}.`} subtitle="Kondisi harian seluruh cabang bisnis Anda.">
    <section className="stats-grid report-stats">{[
      { value: String(summary.today_count), label: 'Transaksi masuk hari ini', href: historyToday, id: 'today-count' },
      { value: `${summary.today_kg.replace('.', ',')} kg`, label: 'Berat aktual masuk hari ini', href: historyToday, id: 'today-kg' },
      { value: rupiah(summary.revenue), label: 'Pendapatan hari ini', href: '/owner/reports/revenue?period=today', id: 'today-revenue' },
      { value: String(summary.piled_up), label: `Siap diambil ≥ ${summary.reminder_days} hari`, href: '/app', id: 'piled-up' },
      { value: rupiah(summary.receivables), label: 'Tagihan berjalan', href: '/owner/reports/receivables', id: 'receivables' },
    ].map(card => <Link className="stat-card" href={card.href} key={card.id}><span>{card.label}</span><strong className="stat-number" data-testid={card.id}>{card.value}</strong><ArrowUpRight className="stat-arrow" size={18} /></Link>)}</section>
    <section className="stats-grid">{[{ count: branchCount, label: 'Cabang laundry', icon: Store, href: '/owner/branches' }, { count: adminCount, label: 'Anggota tim admin', icon: Users, href: '/owner/admins' }, { count: serviceCount, label: 'Layanan master', icon: Layers, href: '/owner/masters' }].map(({ count, label, icon: Icon, href }) => <Link href={href} className="stat-card" key={label}><span className="stat-icon"><Icon size={22} /></span><span className="stat-number">{count}</span><span>{label}</span><ArrowUpRight className="stat-arrow" size={18} /></Link>)}</section>
    <section className="panel"><div className="section-heading"><div><h2>Langkah awal bisnis Anda</h2><p>Lengkapi pengaturan sebelum memulai operasional.</p></div><span className="pill">Fondasi bisnis</span></div>{[{ href: '/owner/branches', done: branchCount > 0, title: 'Tambahkan cabang pertama', text: 'Simpan nama, alamat, dan nomor telepon laundry.' }, { href: '/owner/admins', done: adminCount > 0, title: 'Ajak tim Anda bekerja', text: 'Buat akun admin dan tentukan cabang tugasnya.' }, { href: '/owner/masters', done: serviceCount > 0, title: 'Siapkan daftar layanan', text: 'Atur harga dan durasi, kemudian sebarkan ke cabang.' }].map((item, i) => <Link className="checklist-row" href={item.href} key={item.href}><span className={item.done ? 'step done' : 'step'}>{item.done ? <CheckCircle2 size={22} /> : `0${i + 1}`}</span><span><strong>{item.title}</strong><small>{item.text}</small></span><ArrowUpRight size={19} /></Link>)}</section>
    <div className="ops-actions"><Link className="button button-primary" href="/app">Buka operasional</Link><Link className="button button-outline" href="/owner/settings/payment">Atur pembayaran dan DP</Link><Link className="button button-outline" href="/owner/settings/loyalty">Atur stempel</Link><Link className="button button-outline" href="/owner/promos">Kelola promo</Link></div>
  </AppLayout>;
}
