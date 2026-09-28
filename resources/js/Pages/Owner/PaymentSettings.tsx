import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Errors } from '@/Components/Field';
export default function PaymentSettings({ dpEnabled }: { dpEnabled: boolean }) {
  const form = useForm({ dp_enabled: dpEnabled });
  return <AppLayout title="Pengaturan pembayaran" subtitle="Atur apakah pembayaran pertama boleh berupa DP."><section className="panel ops-form"><h2>Uang muka (DP)</h2><p>Pembayaran penuh selalu boleh. DP yang sudah dimulai tetap dapat dicicil atau dilunasi walau saklar dimatikan.</p><Errors errors={form.errors} /><form onSubmit={event => { event.preventDefault(); form.put('/owner/settings/payment'); }}><label className="checkbox-field"><input type="checkbox" checked={form.data.dp_enabled} onChange={event => form.setData('dp_enabled', event.target.checked)} /><span>Izinkan DP baru</span></label><button className="button button-primary" disabled={form.processing}>Simpan pengaturan</button></form></section></AppLayout>;
}
