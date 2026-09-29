import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Field, Errors } from '@/Components/Field';

type Setting = { reminder_enabled: boolean; reminder_first_days: number; reminder_interval_days: number; reminder_max_count: number; wa_on_ready: boolean; wa_on_reminder: boolean; wa_monthly_limit: number | null };
export default function NotificationSettings({ setting, waOccupied, waSummary }: { setting: Setting; waOccupied: number; waSummary: { berhasil: number; tertunda: number; diproses: number; perlu_pemeriksaan: number } }) {
  const form = useForm({ ...setting, wa_monthly_limit: setting.wa_monthly_limit === null ? '' : String(setting.wa_monthly_limit) });
  function submit(event: FormEvent) { event.preventDefault(); form.transform(data => ({ ...data, wa_monthly_limit: data.wa_monthly_limit === '' ? null : Number(data.wa_monthly_limit) })); form.put('/owner/settings/notifications'); }
  return <AppLayout title="Pengaturan notifikasi" subtitle="Atur waktu pengingat dan batas WA otomatis."><section className="panel"><form onSubmit={submit} className="form-stack"><Errors errors={form.errors} />
    <label className="checkbox-field"><input type="checkbox" checked={form.data.reminder_enabled} onChange={e => form.setData('reminder_enabled', e.target.checked)} /><span>Aktifkan pengingat otomatis</span></label>
    <Field label="Pengingat pertama setelah siap (hari)" type="number" min={1} max={255} value={form.data.reminder_first_days} onChange={e => form.setData('reminder_first_days', Number(e.target.value))} />
    <Field label="Jarak antar pengingat (hari)" type="number" min={1} max={255} value={form.data.reminder_interval_days} onChange={e => form.setData('reminder_interval_days', Number(e.target.value))} />
    <Field label="Jumlah pengingat maksimum" type="number" min={1} max={255} value={form.data.reminder_max_count} onChange={e => form.setData('reminder_max_count', Number(e.target.value))} />
    <label className="checkbox-field"><input type="checkbox" checked={form.data.wa_on_ready} onChange={e => form.setData('wa_on_ready', e.target.checked)} /><span>WA otomatis saat siap diambil</span></label>
    <label className="checkbox-field"><input type="checkbox" checked={form.data.wa_on_reminder} onChange={e => form.setData('wa_on_reminder', e.target.checked)} /><span>WA pengingat otomatis</span></label>
    <Field label="Batas WA per bulan (kosong = tanpa batas)" type="number" min={0} value={form.data.wa_monthly_limit} onChange={e => form.setData('wa_monthly_limit', e.target.value)} />
    <p>Slot WA bulan ini: {waOccupied} (berhasil {waSummary.berhasil}, tertunda {waSummary.tertunda}, diproses {waSummary.diproses}, perlu pemeriksaan {waSummary.perlu_pemeriksaan}). Nilai 0 menghentikan reservasi baru.</p><Button type="submit" disabled={form.processing}>Simpan pengaturan</Button>
  </form></section></AppLayout>;
}
