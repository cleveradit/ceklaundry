import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Errors } from '@/Components/Field';

type Settings = { is_active: number | boolean; stempel_dibutuhkan: number; master_service_id: number | null; berat_maks_gratis: string | null };
export default function LoyaltySettings({ settings, masters }: { settings: Settings; masters: { id: number; nama: string }[] }) {
  const form = useForm({ is_active: Boolean(settings.is_active), stempel_dibutuhkan: String(settings.stempel_dibutuhkan), master_service_id: String(settings.master_service_id ?? ''), berat_maks_gratis: settings.berat_maks_gratis ?? '' });
  return <AppLayout title="Program stempel" subtitle="Atur hadiah untuk semua cabang bisnis Anda."><section className="panel ops-form"><h2>Hadiah pelanggan</h2><p>Setiap transaksi yang pertama kali lunas mendapat satu stempel saat program aktif. Transaksi yang menukar hadiah tidak mendapat stempel baru.</p><Errors errors={form.errors} /><form onSubmit={event => { event.preventDefault(); form.put('/owner/settings/loyalty'); }}>
    <label className="checkbox-field"><input type="checkbox" checked={form.data.is_active} onChange={event => form.setData('is_active', event.target.checked)} /><span>Aktifkan program stempel</span></label>
    <label className="field"><span>Stempel untuk satu hadiah</span><input type="number" min="1" max="255" required value={form.data.stempel_dibutuhkan} onChange={event => form.setData('stempel_dibutuhkan', event.target.value)} /></label>
    <label className="field"><span>Layanan hadiah (kg)</span><select value={form.data.master_service_id} onChange={event => form.setData('master_service_id', event.target.value)}><option value="">Belum dipilih</option>{masters.map(master => <option key={master.id} value={master.id}>{master.nama}</option>)}</select></label>
    <label className="field"><span>Berat gratis maksimal (kg)</span><input type="number" min="0.1" max="9999.9" step="0.1" value={form.data.berat_maks_gratis} onChange={event => form.setData('berat_maks_gratis', event.target.value)} /></label>
    <p>Hadiah hanya dapat ditukar di cabang yang memiliki layanan kg aktif dengan nama yang cocok. Jika layanan master diubah, sinkronkan layanan cabang terlebih dahulu.</p>
    <button className="button button-primary" disabled={form.processing}>Simpan pengaturan</button>
  </form></section></AppLayout>;
}
