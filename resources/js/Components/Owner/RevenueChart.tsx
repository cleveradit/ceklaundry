import { rupiah } from '@/lib/utils';
export type Bucket = { date: string; total: number };

export default function RevenueChart({ buckets }: { buckets: Bucket[] }) {
  const max = buckets.reduce((maximum, bucket) => Math.max(maximum, bucket.total), 1);
  const x = (index: number) => 35 + (buckets.length === 1 ? 325 : index * 650 / Math.max(1, buckets.length - 1));
  const y = (value: number) => 180 - value / max * 150;
  const points = buckets.map((bucket, index) => `${x(index)},${y(bucket.total)}`).join(' ');
  return <section className="panel padded">
    <h2>Grafik pendapatan</h2><p className="form-hint">Periode tanpa pembayaran ditampilkan sebagai nol.</p>
    <svg className="revenue-chart" viewBox="0 0 720 220" role="img" aria-label={`Pendapatan ${buckets[0]?.date ?? ''} sampai ${buckets.at(-1)?.date ?? ''}. Angka lengkap ada di tabel di bawah.`}>
      <line x1="35" y1="180" x2="685" y2="180" stroke="#b7c7b8" />
      <polyline points={points} fill="none" stroke="#12634f" strokeWidth="3" />
      {buckets.length <= 31 && buckets.map((bucket, index) => <circle key={bucket.date} cx={x(index)} cy={y(bucket.total)} r="4" fill="#12634f"><title>{bucket.date}: {rupiah(bucket.total)}</title></circle>)}
    </svg>
    <div className="chart-period"><span>{buckets[0]?.date}</span><span>{buckets.at(-1)?.date}</span></div>
    <details><summary>Angka per periode ({buckets.length})</summary><div className="revenue-buckets">{buckets.map(bucket => <div className="data-row" key={bucket.date}><span>{bucket.date}</span><strong>{rupiah(bucket.total)}</strong></div>)}</div></details>
  </section>;
}
