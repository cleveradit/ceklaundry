# Implementation Plan: TICKET-038 (Pendapatan berbasis tanggal pembayaran)

**Ticket:** `TICKET-038`
**Status:** `DONE`
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-037`
**Tahap:** M5 — laporan pendapatan

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M5](../../plan.md#9-m5--laporan-owner), [PRD 7.5/7.10 dan FR-O11](../../initiate-file/prd.md), [US-502](../../initiate-file/user-stories.md) |
| Keterlacakan | US-502 AC1–4; AND-24, ISO-01/02/05, SEC-05, KIN-04, LOK-01/03 |
| Pendapatan | `SUM(payments.jumlah)` menurut `payments.waktu` dalam periode WIB, hanya untuk transaksi yang statusnya **kini bukan** `DIBATALKAN`. Tidak ada payment sintetis pada transaksi Rp0. |
| Pembatalan | Pembatalan kemudian hari mengeluarkan semua payment transaksi itu dari laporan lama secara retrospektif; aplikasi tidak membuat ledger refund. |
| Periode | Hari ini, tujuh hari terakhir termasuk hari ini, bulan ini, dan tanggal bebas; cabang opsional harus milik bisnis owner. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-037–043 pada 1 Oktober 2026. |

## 2. Objective

Owner melihat uang yang benar-benar diterima per periode dan cabang berdasarkan waktu payment. Angka laporan berubah secara sengaja jika transaksi lama kemudian dibatalkan.

## 3. Non-Negotiable Technical Contract

1. `app/Services/OwnerReportService.php`: tambahkan kueri pendapatan dari `payments` ke transaksi terscope bisnis/cabang; filter `payments.waktu` WIB half-open, status transaksi terkini bukan batal, dan jumlah uang integer.
2. `app/Http/Controllers/Owner/ReportController.php`, `routes/web.php`: `GET /owner/reports/revenue` memvalidasi preset/rentang/cabang dan mengembalikan hasil dari satu snapshot baca konsisten `REPEATABLE READ` sesuai [arsitektur](../../initiate-file/architecture.md#8-reporting-public-security-pwa).
3. `resources/js/Pages/Owner/RevenueReport.tsx`: tampilkan total, periode, cabang, serta penjelasan bahwa pembatalan mengubah laporan lampau.
4. `tests/Feature/Owner/RevenueReportTest.php`: uji DP lintas bulan, pembatalan retrospektif, batas WIB, preset tujuh hari/bulan, payment ganda, dan isolasi.

## 4. Scope of Changes

1. Implementasikan total pembayaran tanpa memakai `transactions.waktu_masuk`, `total_akhir`, atau status bayar sebagai pengganti payment.
2. Pastikan filter cabang membatasi transaksi parent dan payment; gunakan indeks skema `payments(business_id,waktu)` dan relasi terscope.
3. Tampilkan nol pada periode tanpa pembayaran dan tolak rentang/preset tidak sah.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Lintas bulan | DP Rp30.000 pada 28 Juli, pelunasan Rp44.500 pada 2 Agustus | Juli Rp30.000; Agustus Rp44.500 | `[x]` |
| Batal belakangan | Transaksi contoh dibatalkan September lalu laporan Juli/Agustus dibuka ulang | Keduanya Rp0 untuk transaksi itu; tidak ada refund September | `[x]` |
| Tujuh hari dan WIB | Payment pada tepi 00.00 WIB, preset tujuh hari saat 1 Oktober | Hari ini + enam hari sebelumnya, batas akhir eksklusif | `[x]` |
| Cabang | Payment di dua cabang sendiri dan satu bisnis lain | Total cabang terpilih tepat; bisnis lain tidak masuk | `[x]` |
| Gagal | Cabang asing, tanggal terbalik, preset tidak dikenal | Ditolak tanpa agregat atau data lintas tenant | `[x]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=RevenueReportTest`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk git diff --check`

Expected: total cocok penjumlahan payment nonbatal pada periode, termasuk perubahan retrospektif.

## 7. Out of Scope

1. Refund, laporan laba/rugi, pajak, dan pencatatan biaya.
2. Grafik dan ekspor pada tiket berikutnya.

## 8. Completion Checklist

- [x] Otorisasi implementasi tercatat dan status menjadi `READY`.
- [x] AC US-502 dan kasus negatif lulus.
- [x] Hasil verifikasi dicatat.

## 9. Hasil verifikasi

Diimplementasikan pada `main`, [PR #2](https://github.com/cleveradit/ceklaundry/pull/2). Matriks AC, kasus batas dan batas bukti ada di [audit M5](../../audits/m5-verification.md). Suite MySQL penuh lulus 126 tes/1095 assertion, Pint 207 file, frontend dan browser M1–M5 lulus. [CI `691737e`](https://github.com/cleveradit/ceklaundry/actions/runs/36869810998) lulus seluruh gate; P95 dashboard 1716 ms pada dataset 200 bisnis/50.000 transaksi. Kode sudah di `main`; belum deploy produksi.
