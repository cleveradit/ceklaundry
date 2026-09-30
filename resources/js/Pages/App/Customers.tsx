import { Link, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Errors, Field } from '@/Components/Field';
import type { Shared } from '@/types';

type Customer = { id: number; nama: string; no_hp: string; email: string | null; stamp_count: number };
export default function Customers({ customers, query }: { customers: Customer[]; query: string }) {
  const writable = usePage<Shared>().props.business?.writable ?? true;
  const [editing, setEditing] = useState<number | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [merge, setMerge] = useState(false);
  const form = useForm({ nama: '', no_hp: '', email: '' });
  const mergeForm = useForm({ source_id: '', target_id: '', confirmed: true });
  function open(row?: Customer) { setFormOpen(true); setEditing(row?.id ?? null); form.setData({ nama: row?.nama ?? '', no_hp: row?.no_hp ?? '', email: row?.email ?? '' }); form.clearErrors(); }
  function submit(event: FormEvent) { event.preventDefault(); const options = { onSuccess: () => { setFormOpen(false); setEditing(null); form.reset(); } }; if (editing) form.put(`/app/customers/${editing}`, options); else form.post('/app/customers', options); }
  return <AppLayout title="Pelanggan" subtitle="Direktori bersama bisnis; riwayat transaksi tetap dibatasi menurut cabang." action={<Link className="button button-outline" href="/app/transactions/create">Transaksi baru</Link>}>
    <form action="/app/customers" method="get" className="panel ops-form"><Field label="Cari nama atau nomor HP" name="q" defaultValue={query} /><button className="button button-outline" type="submit">Cari</button></form>
    <section className="panel"><div className="section-heading"><h2>Daftar pelanggan</h2><button className="button button-primary" disabled={!writable} onClick={() => open()}>Tambah pelanggan</button></div>{customers.length === 0 ? <p className="ops-empty">Belum ada pelanggan yang cocok.</p> : customers.map(row => <div className="ops-row" key={row.id}><strong>{row.nama}</strong><span>{row.no_hp}</span><span>{row.email ?? 'Tanpa email'}</span><span>{row.stamp_count} stempel</span><Link className="button button-outline" href={`/app/customers/${row.id}/loyalty`}>Riwayat stempel</Link><button className="button button-outline" disabled={!writable} onClick={() => open(row)}>Ubah</button></div>)}</section>
    {formOpen && <section className="panel ops-form"><h2>{editing ? 'Ubah pelanggan' : 'Tambah pelanggan'}</h2><Errors errors={form.errors} /><form onSubmit={submit}><Field label="Nama" required maxLength={100} value={form.data.nama} onChange={e => form.setData('nama', e.target.value)} /><Field label="Nomor HP" required value={form.data.no_hp} onChange={e => form.setData('no_hp', e.target.value)} /><Field label="Email (opsional)" type="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} /><p className="form-hint">Perubahan email master tidak mengubah email transaksi lama.</p><div className="ops-actions"><button className="button button-primary" disabled={form.processing}>Simpan</button><button className="button button-outline" type="button" onClick={() => { setFormOpen(false); setEditing(null); form.reset(); }}>Batal</button></div></form></section>}
    <section className="panel ops-form"><button className="button button-outline" disabled={!writable} onClick={() => setMerge(!merge)}>Gabung pelanggan duplikat</button>{merge && <form onSubmit={event => { event.preventDefault(); if (window.confirm('Gabungkan pelanggan? Riwayat sumber akan pindah ke tujuan dan tindakan ini tidak dapat dibatalkan.')) mergeForm.post('/app/customers/merge', { onSuccess: () => setMerge(false) }); }}><Errors errors={mergeForm.errors} /><label className="field"><span>Pelanggan sumber</span><select required value={mergeForm.data.source_id} onChange={e => mergeForm.setData('source_id', e.target.value)}><option value="">Pilih sumber</option>{customers.map(row => <option key={row.id} value={row.id}>{row.nama} · {row.no_hp}</option>)}</select></label><label className="field"><span>Pelanggan tujuan (identitas dipertahankan)</span><select required value={mergeForm.data.target_id} onChange={e => mergeForm.setData('target_id', e.target.value)}><option value="">Pilih tujuan</option>{customers.map(row => <option key={row.id} value={row.id}>{row.nama} · {row.no_hp}</option>)}</select></label><button className="button button-danger" disabled={mergeForm.processing}>Konfirmasi penggabungan</button></form>}</section>
  </AppLayout>;
}
