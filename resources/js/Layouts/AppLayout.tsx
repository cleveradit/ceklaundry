import { Head, Link, usePage } from '@inertiajs/react';
import { type ReactNode, useEffect } from 'react';
import { LayoutDashboard, Store, Users, Layers, RefreshCw, LogOut, KeyRound, Droplets, ArrowUpRight, ClipboardList } from 'lucide-react';
import type { Shared } from '@/types';
import BusinessLifecycleBanner from '@/Components/BusinessLifecycleBanner';
import PwaUpdateNotice from '@/Components/PwaUpdateNotice';

export default function AppLayout({ title, subtitle, children, action }: { title: string; subtitle?: string; children: ReactNode; action?: ReactNode }) {
  const { props, url } = usePage<Shared>();
  const role = props.auth?.role;
  const links = role === 'developer' ? [{ href: '/dev', label: 'Bisnis laundry', icon: Store }] : role === 'owner' ? [
    { href: '/owner', label: 'Ringkasan', icon: LayoutDashboard }, { href: '/owner/branches', label: 'Cabang', icon: Store },
    { href: '/owner/reports/history', label: 'Riwayat transaksi', icon: ClipboardList }, { href: '/owner/reports/revenue', label: 'Pendapatan', icon: Layers }, { href: '/owner/reports/receivables', label: 'Tagihan berjalan', icon: ClipboardList },
    { href: '/owner/admins', label: 'Tim admin', icon: Users }, { href: '/owner/masters', label: 'Layanan master', icon: Layers }, { href: '/owner/sync', label: 'Sebarkan layanan', icon: RefreshCw }, { href: '/owner/settings/notifications', label: 'Notifikasi', icon: Layers }, { href: '/app', label: 'Operasional', icon: ClipboardList },
  ] : [{ href: '/app', label: 'Ringkasan cabang', icon: Store }, { href: '/app/transactions', label: 'Transaksi', icon: ClipboardList }, { href: '/app/customers', label: 'Pelanggan', icon: Users }];
  useEffect(() => {
    const restore = (event: PageTransitionEvent) => { if (event.persisted) { document.body.style.visibility = 'hidden'; window.location.reload(); } };
    window.addEventListener('pageshow', restore);
    return () => window.removeEventListener('pageshow', restore);
  }, []);
  return <div className="app-shell"><Head title={title} />
    <aside className="sidebar"><Link href={role === 'developer' ? '/dev' : role === 'owner' ? '/owner' : '/app'} className="brand"><Droplets size={30} /><span>CekLaundry<small>RUANG KERJA ANDA</small></span></Link>
      <div className="workspace-label">{role === 'developer' ? 'PENGELOLA APLIKASI' : props.business?.nama ?? 'BISNIS LAUNDRY'}</div>
      <nav aria-label="Menu utama">{links.map(({ href, label, icon: Icon }) => <Link key={href} href={href} className={url.split('?')[0] === href ? 'nav-link active' : 'nav-link'}><Icon size={19} />{label}{url === href && <span className="active-dot" />}</Link>)}</nav>
      <div className="sidebar-bottom">{props.business?.status !== 'DEMO' && <Link href="/password/change" className="nav-link"><KeyRound size={18} />Ganti password</Link>}<Link href="/logout" method="post" as="button" className="nav-link"><LogOut size={18} />Keluar</Link><div className="account"><span className="avatar">{props.auth?.nama.slice(0, 1).toUpperCase()}</span><span><strong>{props.auth?.nama}</strong><small>{role === 'developer' ? 'Developer' : role === 'owner' ? 'Pemilik bisnis' : 'Admin cabang'}</small></span></div></div>
    </aside>
    <div className="main-area"><header className="topbar"><span><span className="online-dot" /> Ruang kerja laundry</span><a href="/" target="_blank" rel="noreferrer">Halaman depan <ArrowUpRight size={15} /></a></header><main className="content">
      <PwaUpdateNotice />
      <BusinessLifecycleBanner business={props.business} role={role} />
      {props.flash?.success && <div className="notice notice-success" role="status">{props.flash.success}</div>}
      <div className="page-heading"><div><p className="eyebrow">{role === 'developer' ? 'ADMINISTRASI' : 'KELOLA DENGAN TENANG'}</p><h1>{title}</h1>{subtitle && <p>{subtitle}</p>}</div>{action}</div>
      {children}
    </main><footer className="app-footer">CekLaundry <span>Semua waktu dalam WIB</span></footer></div>
  </div>;
}
