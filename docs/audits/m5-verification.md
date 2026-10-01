# Verifikasi M5 — Laporan owner

Status: implementasi diuji pada MySQL 8.4 dan Chrome 154.0.8037.92 pada 1 Oktober 2026, branch `codex/m5-reports`, [PR #2](https://github.com/cleveradit/ceklaundry/pull/2). [CI pada commit kode akhir `691737e`](https://github.com/cleveradit/ceklaundry/actions/runs/36869810998) lulus seluruh gate backend, frontend, dokumentasi, browser M1–M5 dan kapasitas/performa. Kode bukti: **L** = tes otomatis lokal; **B** = browser lokal; **C** = CI remote; **P** = pemeriksaan kode; **S** = perlu staging/perangkat.

## Hasil lokal

- `docker compose exec -T app php artisan test --fail-on-warning`: **126 tes/1095 assertion lulus**, 645,74 detik. Regresi M1–M4 termasuk proses concurrency tetap lulus.
- Pint lulus **207 file**; frontend lint, TypeScript dan Vite build lulus. Quality-gate probe membuktikan kegagalan tipe/lint menghasilkan exit nonzero. Validator spesifikasi dan `git diff --check` lulus pada handoff; 191 tautan Markdown lokal pada dokumen yang berubah/baru terverifikasi.
- Browser M1–M5 lulus berurutan pada database QA terpisah. M5 memeriksa kartu dashboard, filter riwayat Juli, pendapatan/grafik Rp3.000, unduhan CSV BOM/teks formula, larangan admin, serta empat halaman tanpa overflow pada viewport 390 × 844. Runner M5 diulang pada image akhir setelah label tanggal grafik diperbaiki; screenshot desktop dan HP diperiksa.
- `OwnerReportSnapshotTest`: ekspor **501 transaksi** melintasi dua chunk. Setelah chunk pertama, koneksi penulis menjalankan PaymentService, CancellationService dan CustomerMergeService; chunk kedua tetap membaca identitas/status/payment lama. Request laporan berikutnya melihat perubahan yang sudah commit. Tes lain membuktikan isolasi REPEATABLE READ, penolakan write MySQL 1792, dan koneksi penulis tetap READ COMMITTED.
- Query riwayat tetap **4** pada fixture 1 dan 31 transaksi; tidak bertambah per baris. Payment diaggregate sebelum join; kg dashboard memakai query item terpisah.
- Fixture kapasitas aktual: **200 bisnis, 50.000 transaksi, 99.994 payment** pada database uji, tersebar dalam 30 hari. Seed dan runner memeriksa jumlah bisnis/transaksi sebelum pengukuran. `m5-performance.cjs` menjalankan 20 navigasi sesudah login pada viewport 390 × 844, download 4 Mbps/upload 1 Mbps, latency 150 ms dan CPU throttle 4×. **P95 lokal 1796 ms**, memenuhi KIN-03 `<2500 ms`. Hasil lengkap lokal di ignored `test-results/m5-performance.json`.
- [CI `691737e`](https://github.com/cleveradit/ceklaundry/actions/runs/36869810998) lulus seluruh gate. Artifact `browser-screenshots/m5-performance.json` memuat **P95 1716 ms**, 20 sampel, 200 bisnis/50.000 transaksi/99.994 payment dan profil yang sama. Fixture berkredensial tidak diunggah sebagai artifact.

## Matriks AC

| AC | Bukti | Status |
|---|---|---|
| US-501.1 | TransactionHistoryReportTest: filter gabungan dua cabang, pagination, input tidak sah dan isolasi bisnis; browser menerapkan rentang Juli | L/B |
| US-501.2 | Transaksi tepat di batas WIB, status batal dan cabang nonaktif tetap tersedia | L |
| US-502.1 | RevenueReportTest: DP 30.000 pada 28 Juli dan pelunasan 44.500 pada 2 Agustus, waktu masuk Juni | L |
| US-502.2 | CancellationService nyata mengubah pendapatan menjadi nol; payment tetap tersimpan | L |
| US-502.3 | Preset bulan ini menjumlah payment, cabang lain terpisah, tengah malam akhir dikeluarkan | L |
| US-502.4 | Batal September mengubah laporan Juli/Agustus tanpa tambahan baris refund | L/P |
| US-503.1 | ReceivablesReportTest: sisa 44.500 setelah beberapa payment, total lintas pagination, terminal/batal/total nol dikeluarkan | L |
| US-504.1 | OwnerDashboardReportTest: lima angka, menumpuk tepat 48 jam dibanding 47:59:59, bisnis lain tidak ikut | L/B |
| US-504.2 | Kg aktual 2,0 dengan minimum 3,0 dan beberapa payment tetap 2,0; transaksi batal dikeluarkan dan reminder off tidak mengubah threshold | L |
| US-505.1 | RevenueChartTest: total bucket harian/bulanan sama dengan total payment; browser grafik dan tabel angka | L/B |
| US-505.2 | Bucket nol, periode kosong dan lintas Desember/Januari; snapshot tanpa cache agregat lintas request | L/P |
| US-506.1 | TransactionCsvExportTest dan browser: filter riwayat yang sama, satu baris/transaksi, 16 kolom tepat | L/B |
| US-506.2 | CSV formula dengan whitespace/quote/koma/newline, BOM/CRLF/ISO WIB; snapshot lintas chunk/payment/cancel/merge; route owner saja | L/B |

## Kriteria selesai plan M5

| Kriteria | Bukti | Status |
|---|---|---|
| DP Juli/pelunasan Agustus mengikuti payment | RevenueReportTest 30.000/44.500 | L |
| Pembatalan kemudian hari mengikuti definisi laporan | Batal September mengecualikan periode lama, dua payment asal tetap ada | L/P |
| WIB, cabang, dashboard, grafik, CSV selaras | Feature suite owner, snapshot integrasi, runner M5 | L/B |
| Owner terisolasi dan admin tidak mengakses laporan owner | Foreign branch ditolak pada seluruh empat route report/CSV, admin/developer ditolak; dashboard bisnis lain terpisah | L/B |

## Catatan kapasitas dan batas bukti

Seed kapasitas awal berhenti sebelum penuh karena akun fixture baru wajib mengganti password; run CI awal tidak digunakan sebagai bukti kapasitas. Seed sudah diperbaiki untuk menyiapkan akun QA, exit nonzero saat gagal, dan memeriksa jumlah dataset sebelum performance runner.

Pengukuran browser memakai throttling di workstation/runner CI, bukan benchmark VPS produksi 2–4 vCPU/4–8 GB. Snapshot membuktikan interleaving write yang commit di tengah pembacaan melalui dua koneksi; tidak mengklaim load test banyak pengguna paralel. Browser HP berupa viewport emulasi, belum perangkat HP fisik. File credential fixture lokal dihapus dan QA dihentikan setelah verifikasi; runtime development beserta datanya dipertahankan. Uji printer fisik tetap dilewati sesuai instruksi pengguna M2; provider nyata, restore backup fisik dan cutover lintas instance mengikuti batas [audit M3](m3-verification.md). Belum merge atau deploy produksi. M6 demo/PWA belum diimplementasikan.
