import { Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { Plus, Search, Store, Layers, Users, Pencil, KeyRound, X, ArrowUpRight } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Field, Errors } from '@/Components/Field';
import { rupiah, tanggal } from '@/lib/utils';
import type { Shared } from '@/types';

type Kind = 'businesses' | 'branches' | 'admins' | 'masters' | 'services';
type Row = { id: number; nama: string; is_active: boolean; [key: string]: string | number | boolean | null };
type Branch = { id: number; nama: string; is_active: boolean };
const settings: Record<Kind, { title: string; subtitle: string; singular: string }> = {
  businesses: { title: 'Bisnis laundry', subtitle: 'Kelola akses dan masa aktif bisnis dalam satu tempat.', singular: 'bisnis' },
  branches: { title: 'Cabang laundry', subtitle: 'Setiap cabang, tetap dalam jangkauan Anda.', singular: 'cabang' },
  admins: { title: 'Tim admin', subtitle: 'Berikan akses yang tepat kepada orang yang Anda percaya.', singular: 'admin' },
  masters: { title: 'Layanan master', subtitle: 'Satu katalog utama untuk harga dan layanan bisnis Anda.', singular: 'layanan' },
  services: { title: 'Layanan cabang', subtitle: 'Sesuaikan layanan dengan kebutuhan pelanggan di cabang ini.', singular: 'layanan' },
};
const initial = { nama: '', alamat: '', telepon: '', email: '', password: '', branch_id: '', is_active: true, satuan: 'kg', harga: '', durasi_jam: '24', berat_minimum: '', active_until: '', owner_nama: '', owner_email: '' };

export default function Management({ kind, records, branches = [], branch }: { kind: Kind; records: Row[]; branches?: Branch[]; branch?: Branch }) {
  const { business } = usePage<Shared>().props;
  const writable = (business?.writable ?? true) && (branch?.is_active ?? true);
  const [search, setSearch] = useState('');
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Row | null>(null);
  const [resetting, setResetting] = useState(false);
  const dialog = useRef<HTMLDialogElement>(null);
  const form = useForm(initial);
  const catalog = kind === 'masters' || kind === 'services';
  const info = settings[kind];
  const endpoint = kind === 'businesses' ? '/dev/businesses' : kind === 'services' ? `/owner/branches/${branch?.id}/services` : `/owner/${kind}`;
  const filtered = records.filter(row => row.nama.toLocaleLowerCase('id').includes(search.toLocaleLowerCase('id')));
  useEffect(() => { if (open) dialog.current?.showModal(); }, [open]);

  function edit(row: Row | null, reset = false) {
    setEditing(row); setResetting(reset); form.clearErrors();
    form.setData({ ...initial, ...(row ? Object.fromEntries(Object.keys(initial).map(key => [key, key === 'is_active' ? row.is_active : String(row[key] ?? initial[key as keyof typeof initial])])) : {}), password: '' });
    setOpen(true);
  }
  function close() { setOpen(false); form.reset('password'); }
  function submit(event: FormEvent) {
    event.preventDefault();
    if (editing && !resetting && !form.data.is_active && !window.confirm(`Nonaktifkan ${info.singular} ini? Akses yang terkait akan dibatasi.`)) return;
    if (resetting && !window.confirm('Simpan password sementara dan cabut sesi lama akun ini?')) return;
    form.transform(data => kind === 'businesses' && !editing ? { nama: data.nama, active_until: data.active_until, owner: { nama: data.owner_nama, email: data.owner_email, password: data.password } } : { ...data, berat_minimum: data.berat_minimum === '' ? null : data.berat_minimum });
    const options = { preserveScroll: true, onSuccess: close, onFinish: () => form.reset('password') };
    if (resetting && editing) { form.post(`${endpoint}/${editing.id}/${kind === 'businesses' ? 'reset-owner' : 'reset'}`, options); }
    else if (editing) { form.put(`${endpoint}/${editing.id}`, options); }
    else { form.post(endpoint, options); }
  }
  const active = records.filter(row => row.is_active).length;
  const Icon = catalog ? Layers : kind === 'admins' ? Users : Store;
  return <AppLayout title={branch ? `${info.title} · ${branch.nama}` : info.title} subtitle={info.subtitle} action={<Button onClick={() => edit(null)} disabled={!writable}><Plus size={18} />Tambah {info.singular}</Button>}>
    <div className="overview-strip"><span className="icon-tile"><Icon size={26} /></span><div><strong>{records.length}</strong><span>Total {info.singular}</span></div><div className="overview-divider" /><div><strong>{active}</strong><span>{kind === 'businesses' ? 'Diaktifkan' : 'Aktif'}</span></div><p>{catalog ? 'Harga tersimpan dalam rupiah. Perubahan katalog tidak mengubah transaksi lama.' : kind === 'admins' ? 'Satu admin bertugas pada satu cabang. Satu cabang boleh memiliki banyak admin.' : 'Data tetap tersimpan ketika akses dinonaktifkan.'}</p></div>
    <section className="panel"><div className="section-heading"><div><h2>Daftar {info.singular}</h2><p>{filtered.length} {info.singular} ditampilkan</p></div><label className="search-field"><Search size={18} /><input aria-label={`Cari ${info.singular}`} placeholder={`Cari ${info.singular}…`} value={search} onChange={e => setSearch(e.target.value)} /></label></div>
      {filtered.length === 0 ? <div className="empty-state"><Icon size={40} strokeWidth={1.3} /><h3>{search ? 'Tidak ada hasil yang cocok' : `Belum ada ${info.singular}`}</h3><p>{search ? 'Coba kata pencarian lain.' : `Mulai dengan menambahkan ${info.singular} pertama Anda.`}</p>{!search && <Button variant="outline" onClick={() => edit(null)} disabled={!writable}>Tambah {info.singular}</Button>}</div> : <div className="record-list">{filtered.map(row => <article className="record" key={row.id}>
        <div className="record-identity"><span className="record-icon"><Icon size={20} /></span><div><strong>{row.nama}</strong><small>{catalog ? `${row.satuan === 'kg' ? 'Kiloan' : 'Satuan'} · ${row.durasi_jam} jam` : kind === 'admins' ? `${row.email} · ${branches.find(b => b.id === Number(row.branch_id))?.nama ?? 'Cabang'}` : kind === 'branches' ? String(row.alamat) : `Aktif sampai ${tanggal(String(row.active_until ?? ''))}`}</small></div></div>
        <div className="record-detail">{catalog ? <><strong>{rupiah(String(row.harga))}<small> / {row.satuan}</small></strong><small>{row.berat_minimum ? `Minimum ${String(row.berat_minimum).replace('.', ',')} kg` : 'Tanpa minimum berat'}</small></> : kind === 'businesses' ? <><strong>{row.branch_count} cabang</strong><small>{row.transaction_count} transaksi · 30 hari</small></> : kind === 'branches' ? <span>{row.telepon}</span> : null}</div>
        <span className={`pill ${row.is_active ? '' : 'pill-muted'}`}>{kind === 'businesses' ? String(row.status).replaceAll('_', ' ') : row.is_active ? 'Aktif' : 'Nonaktif'}</span>
        <div className="record-actions">{kind === 'branches' && <Link className="button button-ghost" href={`/owner/branches/${row.id}/services`}>Layanan <ArrowUpRight size={15} /></Link>}<Button variant="ghost" onClick={() => edit(row)} disabled={!writable} aria-label={`Ubah ${row.nama}`}><Pencil size={16} /><span>Ubah</span></Button>{(kind === 'admins' || kind === 'businesses') && <Button variant="ghost" onClick={() => edit(row, true)} disabled={!writable} aria-label={`Reset password ${row.nama}`}><KeyRound size={17} /></Button>}</div>
      </article>)}</div>}
    </section>
    {catalog && <div className="info-card"><Layers size={22} /><div><strong>Siap menyamakan layanan antar-cabang?</strong><p>Lihat pratinjau sebelum menyalin layanan dari master.</p></div><Link className="button button-outline" href="/owner/sync">{kind === 'services' ? 'Salin dari master' : 'Sebarkan ke cabang'}<ArrowUpRight size={16} /></Link></div>}
    {open && <dialog ref={dialog} className="drawer" onCancel={close} aria-labelledby="form-title"><form onSubmit={submit}><div className="drawer-heading"><div><p className="eyebrow">{resetting ? 'KEAMANAN AKUN' : 'PENGATURAN'}</p><h2 id="form-title">{resetting ? 'Reset password' : editing ? `Ubah ${info.singular}` : `Tambah ${info.singular}`}</h2></div><Button type="button" variant="ghost" onClick={close} aria-label="Tutup formulir"><X size={22} /></Button></div><div className="drawer-body"><Errors errors={form.errors} />
      {resetting ? <><p>Masukkan password sementara untuk <strong>{editing?.nama}</strong>. Akun wajib menggantinya saat masuk kembali.</p><Field label="Password sementara" type="password" autoComplete="new-password" required minLength={12} value={form.data.password} onChange={e => form.setData('password', e.target.value)} /></> : <>
        {!(kind === 'businesses' && editing) && <Field label={`Nama ${info.singular}`} required maxLength={kind === 'businesses' ? 150 : 100} value={form.data.nama} onChange={e => form.setData('nama', e.target.value)} />}
        {kind === 'businesses' && <><Field label="Masa aktif sampai (WIB)" type="date" required value={form.data.active_until} onChange={e => form.setData('active_until', e.target.value)} />{!editing && <><Field label="Nama owner" required value={form.data.owner_nama} onChange={e => form.setData('owner_nama', e.target.value)} /><Field label="Email owner" type="email" required value={form.data.owner_email} onChange={e => form.setData('owner_email', e.target.value)} /></>}</>}
        {kind === 'branches' && <><label className="field"><span>Alamat cabang</span><textarea required rows={3} value={form.data.alamat} onChange={e => form.setData('alamat', e.target.value)} /></label><Field label="Nomor telepon" placeholder="0812…" required value={form.data.telepon} onChange={e => form.setData('telepon', e.target.value)} /></>}
        {kind === 'admins' && <><Field label="Email admin" type="email" required value={form.data.email} onChange={e => form.setData('email', e.target.value)} /><label className="field"><span>Cabang tugas</span><select required value={form.data.branch_id} onChange={e => form.setData('branch_id', e.target.value)}><option value="">Pilih cabang</option>{branches.map(b => <option key={b.id} value={b.id}>{b.nama}{!b.is_active ? ' (nonaktif)' : ''}</option>)}</select></label>{editing && <p className="form-hint">Perubahan email mencabut sesi lama. Perpindahan cabang langsung membatasi akses cabang sebelumnya.</p>}</>}
        {!editing && (kind === 'businesses' || kind === 'admins') && <Field label="Password awal (minimal 12 karakter)" type="password" autoComplete="new-password" required minLength={12} value={form.data.password} onChange={e => form.setData('password', e.target.value)} />}
        {catalog && <><label className="field"><span>Satuan layanan</span><select value={form.data.satuan} onChange={e => { form.setData('satuan', e.target.value); if (e.target.value === 'item') form.setData('berat_minimum', ''); }}><option value="kg">Kilogram (kg)</option><option value="item">Satuan (item)</option></select></label><div className="form-grid"><Field label="Harga per satuan (Rp)" type="number" min={1} max={4294967295} step={1} required value={form.data.harga} onChange={e => form.setData('harga', e.target.value)} /><Field label="Durasi (jam)" type="number" min={1} max={65535} required value={form.data.durasi_jam} onChange={e => form.setData('durasi_jam', e.target.value)} /></div>{form.data.satuan === 'kg' && <Field label="Berat minimum (kg, opsional)" type="number" step="0.1" min="0.1" max="9999.9" value={form.data.berat_minimum} onChange={e => form.setData('berat_minimum', e.target.value)} />}<p className="form-hint">Layanan express cukup memakai durasi lebih pendek dan harga yang sesuai.</p></>}
        {(editing || kind !== 'businesses') && <label className="checkbox-field"><input type="checkbox" checked={form.data.is_active} onChange={e => form.setData('is_active', e.target.checked)} /><span>{info.singular[0].toUpperCase() + info.singular.slice(1)} aktif</span></label>}
      </>}
    </div><div className="drawer-footer"><Button type="button" variant="outline" onClick={close}>Batal</Button><Button type="submit" disabled={form.processing}>{form.processing ? 'Menyimpan…' : resetting ? 'Simpan password sementara' : 'Simpan perubahan'}</Button></div></form></dialog>}
  </AppLayout>;
}
