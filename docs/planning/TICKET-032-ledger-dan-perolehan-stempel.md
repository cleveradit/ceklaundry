# Implementation Plan: TICKET-032 (Ledger dan perolehan stempel)

**Ticket:** `TICKET-032`  
**Status:** `REVIEW`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-030`  
**Tahap:** M4 — integritas saldo

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M4](../plan.md#8-m4--loyalti-dan-promo), [PRD 7.4/7.6/7.8](../initiate-file/prd.md#7-aturan-bisnis--perhitungan), US-402 AC1–7, skema 2.14 |
| Keterlacakan | US-402; FR-L02/L04; ISO-01/05/06, AND-02/04/07/09/13/15/16/23, DAT-01 |
| Perolehan | Tepat +1 saat pertama LUNAS dengan program aktif pada peristiwa itu, termasuk total Rp0; transaksi dengan penukaran tidak memperoleh +1. Tidak ada perolehan saat perpindahan status laundry atau aktivasi program belakangan. |
| Kompensasi | Pembatalan menambah `pencabutan_perolehan=-1` sekali; penukaran yang kelak ada akan dikembalikan sebesar delta asal, meski N berubah/program mati. Saldo boleh negatif karena pembatalan asal yang sudah dibelanjakan. |
| Sumber saldo | `SUM(loyalty_histories.jumlah)` adalah otoritas; `customers.stamp_count` cache yang wajib sama. Ledger append-only; satu jenis per transaksi dijaga unique/check yang sudah ada. |
| Otorisasi | Pengguna meminta pembuatan tiket M4; implementasi tiket belum diminta. |

## 2. Objective

Transaksi lunas memperoleh stempel tepat sekali dan pembatalan mencabut perolehan secara auditabel. Saldo tetap benar pada retry, perubahan pengaturan, pembayaran paralel, transaksi Rp0, dan merge pelanggan.

## 3. Non-Negotiable Technical Contract

1. `app/Services/LoyaltyLedgerService.php`: helper penulisan perolehan/kompensasi di dalam transaksi DB pemanggil; customer dan transaction terkunci setelah business root; cek asal sebelum kompensasi, cache disesuaikan dengan delta aktual.
2. `app/Services/PaymentService.php`: panggil perolehan pada transisi pertama menuju LUNAS setelah payment berhasil; replay request key tidak menambah ledger.
3. `app/Services/TransactionService.php`: create/edit finansial sah dengan total Rp0 memicu perolehan dalam commit yang sama; transaksi lama LUNAS tidak diproses ulang.
4. `app/Services/CancellationService.php`, `app/Services/CustomerMergeService.php`: gunakan kontrak ledger tunggal, kompensasi satu kali, saldo merge dihitung ulang dari ledger dan ownership ledger tetap cocok dengan customer transaksi.
5. `tests/Feature/Loyalty/LoyaltyLedgerTest.php`, `tests/Integration/LoyaltyLedgerConcurrencyTest.php`: MySQL nyata untuk payment/cancel/merge berulang serta dua koneksi; periksa `SUM` terhadap cache.

## 4. Scope of Changes

1. Pertahankan constraint sign/unique/FK skema yang sudah ada; tambah migrasi hanya jika tes membuktikan selisih.
2. Integrasikan event LUNAS ke create total nol, edit menjadi nol dan payment terakhir tanpa memberi stempel pada transaksi hadiah.
3. Jalankan kompensasi cancel meskipun program sedang nonaktif; jangan menghitung ulang dari N terkini atau menutup saldo negatif.
4. Uji merge pelanggan setelah ledger ada, termasuk dua cabang dan source/target bersaldo negatif.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Perolehan | Program aktif, transaksi Rp100.000 lunas setelah DP; Rp0 saat create | Masing-masing +1 tepat sekali; retry payment tidak menggandakan | `[ ]` |
| Nonaktif | Program mati saat LUNAS, lalu aktif lagi | Tidak ada perolehan retroaktif | `[ ]` |
| Batal | Earning +1 telah dibelanjakan lalu transaksi asal dibatalkan saat program mati | Entry −1 sekali; saldo negatif sah, cache=SUM | `[ ]` |
| Batas/retry | Create/edit Rp0, payment/cancel diulang atau bersaing | Tidak ada payment Rp0, ledger/audit ganda, atau status tak sah | `[ ]` |
| Merge | Source/target satu bisnis, dua cabang dan saldo campuran | Ownership, total ledger/cache dan isolasi cabang tetap sah | `[ ]` |
| Data asing | FK customer/transaksi beda bisnis atau kompensasi tanpa asal | Ditolak tanpa ledger parsial | `[ ]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=LoyaltyLedger`
2. `rtk proxy docker compose exec -T app php artisan test --filter=PaymentConcurrencyTest`
3. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
4. `rtk git diff --check`

Expected: saldo cache selalu sama dengan `SUM` pada MySQL QA dan tidak ada ledger duplikat.

## 7. Out of Scope

1. Penukaran baru dan potongan harga hadiah.
2. UI riwayat, saldo publik, dan laporan owner M5.

## 8. Completion Checklist

- [ ] Kontrak teknis dan matriks penerimaan lulus.
- [ ] Hasil uji serta batas bukti dicatat sebelum status `DONE`.
