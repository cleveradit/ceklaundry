# Implementation Plan: TICKET-014 (Pembayaran permanen dan saklar DP)

**Ticket:** `TICKET-014`
**Status:** `DONE`
**Hasil:** [Audit M2](../../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-013`
**Tahap:** M2 — pembayaran

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD 6.1/7.4](../../initiate-file/prd.md), [US-206/207](../../initiate-file/user-stories.md), [arsitektur 4.1/4.2](../../initiate-file/architecture.md) |
| Keterlacakan | US-206 AC1–15, US-207 AC1/2 (guard diselesaikan TICKET-015); AND-01/02/06/13/17/18, ISO-02/06 |
| DP | Partial pertama memakai setting saat payment; setelah DP berjalan, cicilan/pelunasan tetap sah walau saklar mati. Pembayaran penuh tetap boleh. |
| Permanen | Payment tambah-saja, waktu/pencatat server, tanpa edit/delete/backdate dan tanpa baris Rp0. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Pembayaran awal, DP, cicilan dan pelunasan tercatat persis sekali serta tidak melebihi tagihan. Saklar DP owner berlaku pada pembayaran pertama yang parsial, bukan pada saat transaksi dibuat.

## 3. Non-Negotiable Technical Contract

1. `app/Services/PaymentService.php`: business root→customer→transaction lock; SUM payment dan DP setting dibaca ulang; positive ≤ sisa; insert append-only; turunkan status bayar dan naikkan versi sekali.
2. `app/Services/TransactionService.php`: payment awal dalam unit create yang sama, memakai request key turunan UUID create; kegagalan payment membatalkan transaksi, item dan history.
3. `app/Http/Controllers/App/PaymentController.php`, `routes/web.php`: POST payment key/hash; replay existing setelah otorisasi dan sebelum aturan payment baru; tidak ada route update/delete.
4. `app/Http/Controllers/Owner/PaymentSettingController.php`, `resources/js/Pages/Owner/PaymentSettings.tsx`, `routes/web.php`: UI owner dan toggle `business_settings.dp_enabled` di bawah business root lock.
5. `resources/js/Pages/App/TransactionCreate.tsx`, `resources/js/Pages/App/TransactionDetail.tsx`: opsi bayar saat masuk, sisa tagihan, form cicilan/pelunasan mobile.
6. `tests/Feature/Operations/PaymentTest.php`, `tests/Integration/PaymentConcurrencyTest.php`: real MySQL toggle/payment/overpay/replay race.

## 4. Scope of Changes

1. Sertakan metode tunai/transfer, nominal rupiah bulat, jam WIB; hitung status `BELUM_BAYAR`/`DP`/`LUNAS` dari SUM.
2. Payment hanya tiga state aktif dan bisnis writable; developer tanpa detail. Guard pickup final di TICKET-015.
3. Event loyalti M4 belum aktif; kontrak hook pelunasan pertama tidak boleh mengklaim stempel telah diberikan.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| DP dan lunas | Rp74.500; Rp30.000 lalu Rp44.500 | DP/sisa Rp44.500 lalu LUNAS/sisa0; dua row permanen | Lihat [audit M2](../../audits/m2-verification.md) |
| Pembayaran awal | Create Rp74.500 dengan DP awal Rp30.000; DP mati sebelum commit | Saat on payment+transaksi satu commit; saat off partial awal menggagalkan seluruh create | Lihat [audit M2](../../audits/m2-verification.md) |
| Total nol | Transaksi total Rp0 dari fixture/fitur potongan mendatang | LUNAS tanpa row pembayaran Rp0 | Lihat [audit M2](../../audits/m2-verification.md) |
| Toggle | Partial pertama off; DP on lalu off, cicilan Rp20.000 | Pertama ditolak; DP berjalan tetap boleh cicil/lunas | Lihat [audit M2](../../audits/m2-verification.md) |
| Batas | Rp0/negatif/overpay/overflow/terminal/backdate | Ditolak tanpa row atau perubahan status | Lihat [audit M2](../../audits/m2-verification.md) |
| Retry | Key/hash sama respons hilang; hash berbeda | Satu row; replay sama termasuk setelah DP off; beda409 | Lihat [audit M2](../../audits/m2-verification.md) |
| Paralel | Dua payment sisa sama; toggle off vs partial awal | Hanya urutan sah; SUM≤total, status konsisten | Lihat [audit M2](../../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=PaymentTest
rtk proxy docker compose exec -T app php artisan test --filter=PaymentConcurrencyTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

## 7. Out of Scope

1. Payment gateway, refund ledger, edit/hapus payment.
2. Stempel pertama LUNAS M4 dan laporan pendapatan M5; keduanya wajib diuji ulang pada payment nyata.

## 8. Completion Checklist

- [x] Otorisasi implementasi, matriks dan verifikasi selesai.
- [x] Guard pickup TICKET-015 dan integrasi M4/M5 dicatat.
