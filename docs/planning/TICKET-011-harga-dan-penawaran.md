# Implementation Plan: TICKET-011 (Kalkulasi harga dan penawaran server)

**Ticket:** `TICKET-011`
**Status:** `DONE`
**Hasil:** [Audit M2](../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-010`
**Tahap:** M2 — fondasi operasional

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M2](../plan.md#6-m2--operasional-inti), [PRD 7.1–7.4 dan 7.8](../initiate-file/prd.md), [US-203/204](../initiate-file/user-stories.md), [arsitektur 4.2/5](../initiate-file/architecture.md) |
| Keterlacakan | US-203 AC2/3/8/10, US-204 AC1/3; AND-01, AND-08, AND-19, AND-22, ISO-02/06, LOK-02/03 |
| Uang/kuantitas | Kalkulasi integer per 0,1 kg, half-up tiap item, minimum kg, maksimal 100 item dan Rp4.294.967.295; browser tidak menentukan nominal |
| Potongan M2 | Stempel/promo tetap nol karena program M4 belum aktif; bentuk snapshot/fingerprint mengikuti kontrak lengkap agar M4 dapat mengisi tanpa mengubah transaksi lama |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Service server menghasilkan quote harga dan estimasi yang deterministik dari katalog cabang terkini. Save pada tiket berikutnya dapat membandingkan fingerprint quote dan meminta konfirmasi ulang bila katalog atau pengaturan berubah.

## 3. Non-Negotiable Technical Contract

1. `app/Services/PricingService.php`: validasi item/kuantitas/rentang, subtotal integer, snapshot nama/satuan/harga/minimum/durasi, total, dan fingerprint input+hasil kanonis termasuk setting DP saat quote; tidak memakai float.
2. `app/Services/EstimationService.php`: waktu masuk server WIB + durasi terlama; estimasi manual wajib ≥ waktu masuk.
3. `app/Http/Controllers/App/QuoteController.php`, `routes/web.php`: endpoint quote dalam konteks owner/admin dan cabang aktif; owner memilih cabang miliknya, admin memakai cabang assignment terbaru. Tidak menyimpan transaksi.
4. `tests/Feature/Operations/PricingQuoteTest.php`: angka, batas, isolasi dan perubahan katalog setelah preview.

## 4. Scope of Changes

1. Pakai skema `services`/`transactions`/`transaction_items` yang sudah ada; koreksi migrasi baru hanya bila ada selisih nyata terhadap kontrak skema, tanpa mengubah migrasi M1 yang telah dijalankan.
2. Quote menyertakan estimasi dan fingerprint; UI berikutnya menampilkan perubahan harga sebelum save.
3. Semua ID layanan diverifikasi tenant/cabang dan aktif di server; nilai nominal, durasi, dan snapshot dari client diabaikan.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Campuran | 3,5 kg × Rp7.000 + 2 item × Rp25.000 | Subtotal/total Rp74.500, snapshot dan estimasi durasi terlama | Lihat [audit M2](../audits/m2-verification.md) |
| Minimum | 2 kg, minimum 3 kg, Rp7.000/kg | Subtotal Rp21.000; berat aktual tetap 2 kg | Lihat [audit M2](../audits/m2-verification.md) |
| Estimasi | Layanan 48 jam dan 6 jam, masuk 08.00 WIB | Estimasi 48 jam sesudah waktu masuk | Lihat [audit M2](../audits/m2-verification.md) |
| Batas | 0,1 kg; 9.999,9 kg; 100 item baris; half-up Rp0,5 | Batas sah tepat, pembulatan integer; di luar batas/overflow ditolak | Lihat [audit M2](../audits/m2-verification.md) |
| Katalog berubah | Harga/aktif/cabang berubah sesudah quote | Fingerprint berbeda; save lanjutan harus 409 dan quote baru | Lihat [audit M2](../audits/m2-verification.md) |
| Isolasi | ID layanan bisnis/cabang lain atau aktor developer | 404/403 sesuai policy, tanpa harga bocor | Lihat [audit M2](../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=PricingQuoteTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

Jalankan pada database uji MySQL terpisah. Seluruh matriks harus lulus sebelum tiket DONE.

## 7. Out of Scope

1. Pembuatan transaksi/payment dan UI kasir lengkap.
2. Aktivasi promo/penukaran stempel M4; tes integrasi potongan lengkap dimiliki M4.

## 8. Completion Checklist

- [x] Otorisasi implementasi, kontrak, matriks dan perintah verifikasi selesai.
- [x] Fingerprint diuji kembali saat save transaksi nyata TICKET-013.
