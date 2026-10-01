# Implementation Plan: TICKET-039 (Daftar tagihan berjalan)

**Ticket:** `TICKET-039`  
**Status:** `READY`
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-037`  
**Tahap:** M5 — sisa tagihan

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M5](../plan.md#9-m5--laporan-owner), [PRD 7.5/7.10 dan FR-O12](../initiate-file/prd.md), [US-503](../initiate-file/user-stories.md) |
| Keterlacakan | US-503 AC1; AND-24, ISO-01/02/05, SEC-05, KIN-04, LOK-01/03 |
| Tagihan | Hanya transaksi aktif `DITERIMA`/`DIPROSES`/`SIAP_DIAMBIL` dengan status bayar `BELUM_BAYAR` atau `DP`; sisa positif = `total_akhir - SUM(payments.jumlah)`. |
| Historis | `SUDAH_DIAMBIL` dan `DIBATALKAN` tidak muncul; cabang nonaktif tidak menghapus tagihan historis yang masih sah. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-037–043 pada 1 Oktober 2026. |

## 2. Objective

Owner melihat transaksi yang masih berada di laundry dan belum lunas, jumlah sisa per transaksi, serta total tagihan berjalan. Angka tidak terlipat oleh banyak payment.

## 3. Non-Negotiable Technical Contract

1. `app/Services/OwnerReportService.php`: tambahkan kueri tagihan bisnis owner; hitung payment per transaksi lebih dahulu atau pakai cache terverifikasi tanpa join item/payment yang menggandakan jumlah; hasilkan daftar dan total dari snapshot baca konsisten.
2. `app/Http/Controllers/Owner/ReportController.php`, `routes/web.php`: `GET /owner/reports/receivables` hanya untuk owner, pilihan cabang sendiri, termasuk cabang nonaktif untuk histori.
3. `resources/js/Pages/Owner/ReceivablesReport.tsx`: tampilkan sisa per resi dan total keseluruhan, status/cabang, tautan detail yang berizin, serta keadaan kosong.
4. `tests/Feature/Owner/ReceivablesReportTest.php`: uji belum bayar, beberapa DP, lunas, batal, diambil, cabang nonaktif, dan isolasi.

## 4. Scope of Changes

1. Buat daftar transaksi aktif berutang dan total yang mencakup seluruh hasil, bukan hanya halaman pagination saat ini.
2. Pastikan total terbayar berasal dari payment permanen dan tidak negatif; transaksi Rp0 yang langsung LUNAS tidak masuk.
3. Gunakan pola snapshot yang akan dipakai kartu dashboard TICKET-040.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Tagihan | Total Rp74.500, payment Rp30.000 | Sisa Rp44.500; total keseluruhan bertambah Rp44.500 | `[ ]` |
| Banyak payment | Dua cicilan pada satu transaksi dan item lebih dari satu | Satu baris; sisa dihitung satu kali | `[ ]` |
| Status terminal | `SUDAH_DIAMBIL`, `DIBATALKAN`, Rp0 `LUNAS` | Tidak muncul dan tidak menambah total | `[ ]` |
| Batas cabang | Cabang nonaktif sendiri, cabang bisnis lain | Cabang sendiri tetap terbaca; cabang asing ditolak | `[ ]` |
| Akses gagal | Admin/developer membuka route owner | 403 tanpa daftar atau total | `[ ]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=ReceivablesReportTest`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk git diff --check`

Expected: daftar dan total cocok penjumlahan sisa positif transaksi aktif dalam satu snapshot.

## 7. Out of Scope

1. Penagihan otomatis dan akuntansi piutang historis.
2. Perubahan status/payment pada transaksi.

## 8. Completion Checklist

- [ ] Otorisasi implementasi tercatat dan status menjadi `READY`.
- [ ] AC US-503 dan isolasi lulus.
- [ ] Hasil verifikasi dicatat.
