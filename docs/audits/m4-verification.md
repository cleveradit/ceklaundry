# Verifikasi M4 — Loyalti dan promo

Status: implementasi M4 diuji pada MySQL 8.4 dan Chrome 154 di QA lokal pada 30 September 2026. [CI GitHub pada commit `9d0e433`](https://github.com/cleveradit/ceklaundry/actions/runs/36725111623) lulus seluruh gate di branch `main`. Kode bukti: **L** = tes otomatis lokal; **B** = perjalanan browser lokal; **P** = pemeriksaan kode/tes parsial; **S** = perlu staging/perangkat.

## Hasil yang sudah dijalankan

- Suite backend MySQL pada revisi akhir: `php artisan test --fail-on-warning` lulus **114 tes/963 assertion**.
- `RedemptionConcurrencyTest` memakai dua proses PHP dan dua koneksi MySQL; satu create penukaran berhasil, satu menerima 409, saldo akhir sama dengan SUM ledger. Tes concurrency M2–M3 tetap lulus dalam suite penuh.
- Browser M1–M4 pada Chrome 154 lulus berurutan pada image akhir. M4 memeriksa pengaturan owner, transaksi hadiah 2 kg dengan minimum 3 kg dan promo 10%, status publik tanpa JavaScript, serta cetak resi. Nominal yang diperiksa: subtotal Rp21.000, hadiah Rp14.000, promo Rp700, total Rp6.300. Fixture berkredensial sudah dihapus dan QA dihentikan.
- Pint lulus 192 file. Frontend lint, TypeScript, Vite build, quality-gate probe, validator spesifikasi final, dan `git diff --check` lulus lokal. [CI run #24](https://github.com/cleveradit/ceklaundry/actions/runs/36725111623) lulus gate MySQL, frontend, dokumentasi, serta browser M1–M4 pada commit kode `9d0e433`.

## Matriks AC

| AC | Bukti dan batas | Status |
|---|---|---|
| US-401.1 | Simpan setting owner, satu record per bisnis; browser form | L/B |
| US-401.2 | Program off menyembunyikan status dan menghentikan award/quote; tidak semua permukaan diuji browser | L/P |
| US-401.3 | Validasi aktif dan kosong saat off di service; foreign master ditolak | L |
| US-401.4 | Pencocokan nama normalisasi dan kg aktif di quote; guard master aktif; perubahan nama cabang belum diuji browser | L/P |
| US-401.5 | Tes off→on mempertahankan saldo; kode award hanya pada transisi LUNAS, tanpa backfill | L/P |
| US-402.1 | Payment pertama LUNAS memberi +1; lintas cabang ditopang ledger tenant dan tes akses | L |
| US-402.2 | Pembatalan memberi −1 pada tes perjalanan | L/B |
| US-402.3 | Transaksi penukaran tidak mendapat earning walau lunas | L |
| US-402.4 | Refund menggunakan negatif delta asal, tidak N terkini; tes pembatalan sesudah program off | L |
| US-402.5 | Kompensasi satu kali, cancel ulang idempoten menurut service; uji ulang khusus belum ada | P |
| US-402.6 | Total nol langsung LUNAS, +1 tanpa payment nol; off tidak memberi backfill | L/P |
| US-402.7 | Saldo −1 tampil bertanda setelah earning terpakai lalu asal dibatalkan | L/B |
| US-403.1 | Rumus berat aktual 5 kg dengan batas 3 kg diperiksa di kode; nominal 2 kg + minimum diverifikasi | P |
| US-403.2 | Rumus `min(berat aktual, batas)`; sisa kuota tidak disimpan | P |
| US-403.3 | Quote menolak saldo kurang; form menampilkan saldo dan pilihan | L/P |
| US-403.4 | Satu indeks hadiah per quote dan ledger unique pada transaksi | P |
| US-403.5 | Dua proses MySQL, satu redemption berhasil dan satu 409 | L |
| US-403.6 | 2 kg aktual, minimum 3 kg: subtotal 21.000, potongan 14.000 | L/B |
| US-403.7 | Fingerprint memuat N/harga/saldo; edit finansial dengan ledger terkunci; stale promo diuji, stale N belum diuji terpisah | L/P |
| US-404.1 | Ledger empat jenis, delta bertanda, SUM dan cache; merge menjumlah ulang | L/B |
| US-404.2 | Tes dua cabang: owner semua baris, admin hanya cabang sendiri tetapi saldo global | L |
| US-405.1 | Status publik menampilkan target ketika aktif dan menyembunyikannya saat off | L/B |
| US-405.2 | Saldo negatif `−1/1` dan penjelasan diperiksa | L/B |
| US-406.1 | Owner membuat promo bertanggal WIB dan pivot cabang; browser form | L/B |
| US-406.2 | Persen 101 dan tenant asing ditolak; perubahan promo tidak mengubah snapshot transaksi lama | L |
| US-407.1 | Quote dan transaksi menyimpan nama/tipe/nilai/potongan promo | L/B |
| US-407.2 | Server menolak promo mati/di luar cabang, tanggal, minimum; tes cabang/minimum ada, tanggal diperiksa dalam kode | L/P |
| US-407.3 | Persentase memakai base setelah hadiah dan half-up; total tidak negatif | L/B |
| US-407.4 | Snapshot lama tetap setelah promo diubah | L |
| US-407.5 | Minimum diperiksa terhadap base; cap nominal dan total nol teruji, kombinasi angka 100.000/20.000 belum diuji khusus | L/P |
| US-407.6 | Satu promo ID/indeks hadiah; potongan client ditolak, total nol tanpa pembayaran; penolakan promo kedua via bentuk input belum diuji khusus | L/P |

## Kriteria selesai plan M4

| Kriteria | Bukti | Status |
|---|---|---|
| Saldo sama tidak dapat dipakai dua kali | Dua proses MySQL, satu 200 dan satu 409 | L |
| Hadiah, minimum, promo, pembulatan | 2 kg/min 3 kg, half-up Rp0,5, cap nominal | L/B |
| Kompensasi pembatalan tambah-saja | Earning −1, redemption refund delta asal; pengulangan cancel diperiksa service | L/P |
| Perubahan konfigurasi menjaga snapshot lama | Promo diedit setelah save, angka transaksi lama tetap; harga item snapshot M2 tetap | L |
| Saldo negatif tidak disamarkan | Tes halaman publik dan browser | L/B |

## Batas dan handoff

Tes paralel M4 secara langsung membuktikan dua penukaran; interleaving payment/cancel/merge terhadap penukaran belum dijalankan sebagai kombinasi proses khusus. Semua jalur tersebut memakai urutan lock business→customer→transaction yang juga diuji oleh suite concurrency M2/M3, sehingga risiko deadlock/delta ganda pada kombinasi baru masih perlu pengujian tambahan sebelum produksi. Uji printer thermal fisik dilewati sesuai instruksi pengguna pada M2. Provider email/WA nyata, restore backup fisik, dan cutover lintas instance tetap memerlukan staging sesuai audit M3. M5 laporan dan M6 demo/PWA tetap pekerjaan terpisah.
