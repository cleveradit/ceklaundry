# Implementation Plan: TICKET-034 (Promo dan urutan potongan transaksi)

**Ticket:** `TICKET-034`  
**Status:** `DONE`

**Hasil:** Implementasi dan tingkat bukti aktual dicatat pada [audit M4](../../audits/m4-verification.md). Matriks di bawah adalah rencana penerimaan; audit memisahkan tes langsung dari pemeriksaan parsial.

**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-031`, `TICKET-033`  
**Tahap:** M4 — harga akhir

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M4](../../plan.md#8-m4--loyalti-dan-promo), [PRD 7.2/7.8](../../initiate-file/prd.md#7-aturan-bisnis--perhitungan), US-407 AC1–6 |
| Keterlacakan | US-407; FR-P02/P03/FR-A18; ISO-01/05/06, AND-02/08/09/18/19/22/23, UX-03/04, LOK-01 |
| Urutan | `subtotal - potongan_stempel = promo_eligible_base`; minimum promo diuji terhadap base itu; diskon persen half-up dari base atau nominal dibatasi base; `total_akhir >= 0`. |
| Kelayakan | Maksimal satu promo aktif, dalam tanggal WIB inklusif, mencakup cabang, dan memenuhi minimum. Promo kedua serta potongan bebas dari client ditolak. |
| Snapshot | Nama/tipe/nilai/potongan promo disalin ke transaksi. Create/edit finansial sah memakai katalog dan promo terkini, quote stale 409; transaksi lama tidak berubah saat promo diubah/nonaktif. Total nol menjadi LUNAS tanpa payment Rp0. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-030–036 pada 30 September 2026. |

## 2. Objective

Admin/owner dapat memilih satu promo saat membuat atau mengedit transaksi yang masih sah. Harga server menerapkan promo setelah hadiah stempel dan menyimpan snapshot yang stabil.

## 3. Non-Negotiable Technical Contract

1. `app/Services/PricingService.php`, `app/Http/Controllers/App/QuoteController.php`: validasi promo tenant/cabang/periode/status/minimum; hitung integer half-up dan cap; sertakan konfigurasi dan hasil pada fingerprint quote.
2. `app/Services/TransactionService.php`: create/edit menolak field total/potongan/snapshot dari client, validasi ulang promo setelah business/customer lock, tulis `promo_id`, snapshot dan potongan bersama item/total/version; quote berubah menghasilkan 409 tanpa partial write.
3. `app/Http/Controllers/App/TransactionController.php`, `resources/js/Pages/App/TransactionCreate.tsx`, `resources/js/Pages/App/TransactionEdit.tsx`: tampilkan promo sah, minimum terhadap base setelah hadiah, subtotal dan kedua potongan; konfirmasi quote baru setelah 409.
4. `tests/Feature/Promo/PromoApplicationTest.php`, `tests/Integration/PromoQuoteRaceTest.php`: MySQL QA, harga, tanggal WIB, cabang asing, perubahan owner vs save dan regresi total nol/payment.

## 4. Scope of Changes

1. Sambungkan katalog promo ke form transaksi tanpa membocorkan promo bisnis/cabang lain.
2. Reprice server dengan urutan hadiah lalu promo, cap nominal/hasil, dan snapshot penuh pada create/edit finansial sah.
3. Pertahankan harga lama ketika promo kemudian berubah; edit finansial yang masih sah wajib validasi konfigurasi saat edit, bukan memakai hak lama.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Gabungan | Subtotal Rp21.000, hadiah Rp14.000, promo 10% | Promo Rp700, total Rp6.300; snapshot kedua potongan benar | [audit M4](../../audits/m4-verification.md) |
| Minimum | Subtotal Rp100.000, hadiah Rp20.000, minimum promo Rp90.000 lalu Rp80.000 | Yang pertama ditolak; kedua persen 10% memberi Rp8.000 | [audit M4](../../audits/m4-verification.md) |
| Nominal/total nol | Base Rp80.000, promo nominal Rp100.000 | Potongan Rp80.000, total nol LUNAS, tanpa payment Rp0 | [audit M4](../../audits/m4-verification.md) |
| Batas | Persen menghasilkan pecahan Rp0,5; tanggal WIB awal/akhir inklusif | Half-up rupiah; hari batas sah | [audit M4](../../audits/m4-verification.md) |
| Invalid | Promo mati/kedaluwarsa/cabang asing, promo kedua, potongan client | Ditolak server; tidak ada transaksi parsial | [audit M4](../../audits/m4-verification.md) |
| Perubahan | Owner mengubah promo setelah preview atau sesudah transaksi tersimpan | Save lama 409; snapshot transaksi lama tetap | [audit M4](../../audits/m4-verification.md) |
| Edit | Edit finansial masih sah dengan promo lama yang kini mati | Ditolak dan minta quote sah baru; edit nonfinansial tidak reprice | [audit M4](../../audits/m4-verification.md) |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=PromoApplicationTest`
2. `rtk proxy docker compose exec -T app php artisan test --filter=PromoQuoteRaceTest`
3. `rtk proxy docker compose exec -T app php artisan test --filter=PricingQuoteTest`
4. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
5. `rtk git diff --check`

Expected: seluruh hasil harga cocok PRD 7.2 dan tidak ada potongan negatif/overflow.

## 7. Out of Scope

1. Promo bertumpuk, kupon pelanggan, pajak, dan diskon manual.
2. Laporan pendapatan/CSV owner M5.

## 8. Completion Checklist

- [x] Implementasi, pengujian lokal/QA, serta batas bukti dicatat pada [audit M4](../../audits/m4-verification.md).
