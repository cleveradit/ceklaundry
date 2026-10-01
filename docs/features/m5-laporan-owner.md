# M5 — Laporan owner

Status: diimplementasikan pada `main`, [PR #2](https://github.com/cleveradit/ceklaundry/pull/2). Verifikasi lokal dan bukti CI dicatat di [audit M5](../audits/m5-verification.md). Fitur tidak memerlukan migrasi baru; belum dideploy produksi.

## Halaman dan aturan angka

| Halaman | Route | Perilaku |
|---|---|---|
| Ringkasan harian | `/owner` | Lima kartu: transaksi nonbatal dan kg aktual masuk hari ini, pendapatan hari ini, tagihan berjalan, serta cucian menumpuk |
| Riwayat transaksi | `/owner/reports/history` | Filter cabang, tanggal masuk, status transaksi dan status bayar; 25 baris per halaman; transaksi batal dan cabang nonaktif tetap tersedia |
| Pendapatan | `/owner/reports/revenue` | Hari ini, tujuh tanggal WIB termasuk hari ini, bulan ini sampai hari ini, atau rentang khusus; filter cabang; grafik harian/bulanan dan tabel angka |
| Tagihan berjalan | `/owner/reports/receivables` | Transaksi DITERIMA/DIPROSES/SIAP_DIAMBIL dengan BELUM_BAYAR/DP dan sisa positif; total mencakup seluruh halaman; filter cabang |
| CSV riwayat | `/owner/reports/history.csv` | Seluruh hasil filter riwayat, tanpa pembatasan pagination; unduhan streaming |

Pendapatan menjumlahkan `payments.jumlah` menurut `payments.waktu`, bukan tanggal masuk transaksi. DP Juli dan pelunasan Agustus masuk ke bulan masing-masing. Jika transaksi kemudian dibatalkan, pembayaran tersebut dikeluarkan juga dari laporan periode lampau. Ledger pembayaran tetap ada; aplikasi tidak membuat refund atau pendapatan negatif pengganti.

Rentang tanggal memakai WIB `[awal 00.00, sehari setelah akhir 00.00)`. Berat dashboard memakai `transaction_items.berat_kg` aktual untuk satuan kg, bukan berat minimum penagihan. Item dan payment dihitung terpisah sehingga beberapa payment tidak menggandakan berat. Menumpuk berarti SIAP_DIAMBIL dengan usia sejak waktu siap minimal `reminder_first_days × 24 jam`; saklar reminder tidak mengubah angka ini.

## Konsistensi dan akses

`OwnerReportService` menyusun seluruh angka dan baris dari satu snapshot MySQL melalui `ReportSnapshot`. Koneksi khusus `owner_reports` memakai REPEATABLE READ dan transaksi READ ONLY; koneksi penulis tetap READ COMMITTED. Snapshot CSV bertahan selama seluruh chunk 500 baris. Tidak ada cache agregat lintas request. Dua request yang dipisahkan pembayaran, pembatalan, atau merge dapat memiliki hasil berbeda.

Semua route khusus owner, terscope bisnis dari akun login. Admin/developer ditolak; `branch_id` bisnis lain menghasilkan 404. Laporan tersedia dalam lifecycle BACA_SAJA. Identitas pelanggan berasal dari record pelanggan terkini, sehingga laporan baru mengikuti hasil merge. Respons panel dan CSV memakai no-store.

## Format CSV

Kolom berurutan persis:

```text
kode_resi,cabang,nama_pelanggan,no_hp,status,status_bayar,subtotal,potongan_stempel,potongan_promo,total,total_terbayar,sisa,waktu_masuk,estimasi_selesai,waktu_siap_diambil,waktu_diambil
```

File UTF-8 dengan BOM, delimiter koma, escaping quote RFC 4180 dan pemisah baris CRLF. Rupiah berupa integer tanpa simbol/pemisah ribuan; tanggal ISO 8601 dengan offset `+07:00`, tanggal kosong menjadi kolom kosong. Teks yang dapat memulai formula spreadsheet diberi awalan apostrof, termasuk setelah whitespace; koma, quote dan newline dalam nama tetap di-escape.

## Bukti dan handoff

Suite MySQL, snapshot dua koneksi/501 baris, browser desktop dan viewport HP, fixture 200 bisnis/50.000 transaksi, serta P95 dashboard dipetakan di [audit M5](../audits/m5-verification.md). Perintah QA ada di [development](../development.md). Demo/PWA M6 masih menunggu tiket.
