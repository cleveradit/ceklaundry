import type { Shared } from '@/types';
import { tanggal } from '@/lib/utils';
import { Link } from '@inertiajs/react';
export default function BusinessLifecycleBanner({ business, role }: { business: Shared['business']; role?: 'developer' | 'owner' | 'admin' }) {
  if (!business || (!business.warning && business.status !== 'DEMO')) return null;
  return <aside role="status" className={`notice ${!business.writable ? 'notice-danger' : ''}`}>
    <strong>{business.status === 'BACA_SAJA' ? 'Bisnis dalam mode baca-saja' : business.status === 'TENGGANG' ? 'Masa tenggang berlangsung' : business.status === 'DEMO' ? 'MODE DEMO' : 'Masa aktif segera berakhir'}</strong>
    <span>{business.status === 'DEMO' ? 'Data ini adalah data percobaan.' : `Masa aktif sampai ${tanggal(business.active_until)}. Hubungi pengelola untuk perpanjangan.${!business.writable ? ' Data tetap dapat dibaca; perubahan bisnis dinonaktifkan.' : ''}`}</span>
    {business.status === 'DEMO' && (role === 'owner' || role === 'admin') && <Link href="/demo/role" method="post" as="button" className="button button-secondary">{role === 'owner' ? 'Lihat sebagai Admin' : 'Kembali sebagai Owner'}</Link>}
  </aside>;
}
