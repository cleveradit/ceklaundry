import type { Shared } from '@/types';
import { tanggal } from '@/lib/utils';
export default function BusinessLifecycleBanner({ business }: { business: Shared['business'] }) {
  if (!business || (!business.warning && business.status !== 'DEMO')) return null;
  return <aside role="status" className={`notice ${!business.writable ? 'notice-danger' : ''}`}>
    <strong>{business.status === 'BACA_SAJA' ? 'Bisnis dalam mode baca-saja' : business.status === 'TENGGANG' ? 'Masa tenggang berlangsung' : business.status === 'DEMO' ? 'Anda sedang mencoba demo' : 'Masa aktif segera berakhir'}</strong>
    <span>{business.status === 'DEMO' ? 'Data ini adalah data percobaan.' : `Masa aktif sampai ${tanggal(business.active_until)}. Hubungi pengelola untuk perpanjangan.${!business.writable ? ' Data tetap dapat dibaca; perubahan bisnis dinonaktifkan.' : ''}`}</span>
  </aside>;
}
