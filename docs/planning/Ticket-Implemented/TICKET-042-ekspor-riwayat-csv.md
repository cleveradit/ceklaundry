# Implementation Plan: TICKET-042 (Ekspor riwayat transaksi CSV)

**Ticket:** `TICKET-042`
**Status:** `DONE`
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-037`, `TICKET-038`, `TICKET-039`
**Tahap:** M5 — ekspor owner

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M5](../../plan.md#9-m5--laporan-owner), [PRD FR-O16/7.10](../../initiate-file/prd.md), [US-506](../../initiate-file/user-stories.md), [arsitektur laporan](../../initiate-file/architecture.md#8-reporting-public-security-pwa) |
| Keterlacakan | US-506 AC1–2; AND-24, ISO-01/02/03/05, SEC-04/05, KIN-04, LOK-01/03 |
| Kolom | Kode resi, cabang, nama/nomor HP customer terkini, status, status bayar, subtotal, potongan stempel/promo, total, total terbayar, sisa, waktu masuk, estimasi, waktu siap/diambil. Satu baris per transaksi. |
| Format | UTF-8 BOM, CSV RFC 4180, waktu ISO WIB, uang numerik tanpa `Rp`; teks yang diawali formula spreadsheet dinetralkan. |
| Konsistensi | Filter sama dengan TICKET-037 dan seluruh baris dari satu snapshot baca terscope owner; ekspor sinkron streaming lewat route panel, bukan URL publik. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-037–043 pada 1 Oktober 2026. |

## 2. Objective

Owner dapat mengunduh riwayat terfilter sebagai CSV yang konsisten dan aman dibuka di spreadsheet. Ekspor tidak menambah hak admin/developer atau membuka data bisnis lain.

## 3. Non-Negotiable Technical Contract

1. `app/Services/OwnerReportService.php`: gunakan filter riwayat TICKET-037; agregasi payment per transaksi tanpa penggandaan item/baris dan ambil identitas customer terkini; ekspor dari satu transaksi baca `REPEATABLE READ` sepanjang stream.
2. `app/Http/Controllers/Owner/ReportExportController.php`, `routes/web.php`: `GET /owner/reports/history.csv` di middleware `role:owner`; header download, `Cache-Control: no-store`, stream sinkron; validasi filter sama dengan halaman riwayat.
3. `resources/js/Pages/Owner/TransactionHistory.tsx`: tombol ekspor mempertahankan semua filter yang aktif.
4. `tests/Feature/Owner/TransactionCsvExportTest.php`, `tests/Integration/OwnerReportSnapshotTest.php`: kolom/format/formula, hak akses, filter, banyak payment, cabang nonaktif/batal, dan perubahan data saat stream dibaca.

## 4. Scope of Changes

1. Ekspor satu baris per transaksi dengan kolom dan urutan kontrak PRD 7.10; batal tetap berlabel dan tidak dianggap pendapatan.
2. Lindungi setiap field teks yang dapat berasal dari input pengguna dari formula injection termasuk awalan `=`, `+`, `-`, `@` dan whitespace sebelum formula.
3. Pastikan query tidak membuat N+1 atau membuka snapshot baru pada setiap chunk streaming.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Filter sama | Riwayat satu cabang/rentang/status | Kode resi CSV tepat sama dengan daftar terfilter | `[x]` |
| Banyak payment | Satu transaksi dengan DP dan pelunasan | Satu baris; terbayar total benar; sisa nol | `[x]` |
| Format | Nama `=SUM(1,1)`, koma, petik, baris baru, karakter Indonesia | BOM/escaping RFC 4180 benar; formula tidak dieksekusi saat dibuka | `[x]` |
| Snapshot | Payment/cancel/merge terjadi saat export sedang berjalan | Seluruh baris mencerminkan satu snapshot, tanpa campuran keadaan lama/baru | `[x]` |
| Akses gagal | Admin/developer, cabang asing, owner bisnis lain | 403/penolakan filter; tidak ada CSV yang memuat tenant lain | `[x]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=TransactionCsvExportTest`
2. `rtk proxy docker compose exec -T app php artisan test --filter=OwnerReportSnapshotTest`
3. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
4. `rtk git diff --check`

Expected: CSV valid, formula aman, satu snapshot dan isolasi tenant terbukti dengan MySQL.

## 7. Out of Scope

1. Ekspor Excel/PDF, unduhan publik, dan tugas ekspor asinkron.
2. CSV payment ledger terpisah atau refund yang tidak ada di aplikasi.

## 8. Completion Checklist

- [x] Otorisasi implementasi tercatat dan status menjadi `READY`.
- [x] AC US-506, uji snapshot, dan keamanan CSV lulus.
- [x] Hasil verifikasi dicatat.

## 9. Hasil verifikasi

Diimplementasikan pada `main`, [PR #2](https://github.com/cleveradit/ceklaundry/pull/2). Matriks AC, kasus batas dan batas bukti ada di [audit M5](../../audits/m5-verification.md). Suite MySQL penuh lulus 126 tes/1095 assertion, Pint 207 file, frontend dan browser M1–M5 lulus. [CI `691737e`](https://github.com/cleveradit/ceklaundry/actions/runs/36869810998) lulus seluruh gate; P95 dashboard 1716 ms pada dataset 200 bisnis/50.000 transaksi. Kode sudah di `main`; belum deploy produksi.
