# Implementation Plan: TICKET-033 (Penukaran stempel pada transaksi)

**Ticket:** `TICKET-033`  
**Status:** `REVIEW`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-030`, `TICKET-032`  
**Tahap:** M4 — hadiah dan penukaran

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M4](../plan.md#8-m4--loyalti-dan-promo), [PRD 7.2/7.6/7.8](../initiate-file/prd.md#7-aturan-bisnis--perhitungan), US-403 AC1–7 |
| Keterlacakan | US-403; FR-L03/FR-A18; ISO-01/05/06, AND-07/08/09/15/16/18/19/22/23, UX-03/04 |
| Hadiah | Satu baris kg dipilih secara eksplisit bila beberapa cocok. Nama layanan cabang harus sama setelah normalisasi dengan master hadiah aktif dan satuan kg; hadiah tidak ditawarkan bila tidak cocok. |
| Potongan | Harga snapshot baris × min(berat aktual, batas hadiah), dibulatkan half-up ke rupiah dan dibatasi subtotal baris. Berat minimum tetap ditagih bila melebihi berat aktual; potongan nol ditolak. |
| Ledger | Satu `penukaran=-N` per transaksi, N dan berat maksimal disnapshot saat save. Lock business→customer→transaction; saldo ledger diperiksa ulang setelah lock dan tidak boleh menjadi negatif akibat penukaran. Tidak ada pengubahan/pengembalian redemption tanpa pembatalan. |
| Otorisasi | Pengguna meminta pembuatan tiket M4; implementasi tiket belum diminta. |

## 2. Objective

Admin/owner dapat menawarkan satu hadiah dan menyimpan penukaran yang benar secara harga, snapshot dan saldo. Dua transaksi paralel untuk customer yang sama tidak dapat memakai stempel yang sama.

## 3. Non-Negotiable Technical Contract

1. `app/Services/PricingService.php`, `app/Http/Controllers/App/QuoteController.php`: quote server menerima pilihan baris hadiah dan customer, memvalidasi layanan/config/saldo, menghitung `potongan_stempel` integer half-up serta memasukkan pilihan/N/setting/harga ke fingerprint.
2. `app/Services/TransactionService.php`: create dan edit finansial sah memverifikasi ulang quote setelah lock, mengisi `transactions.stamp_reward_max_kg_snapshot`, `potongan_stempel`, `transaction_items.is_stamp_reward` dan ledger −N dalam commit yang sama. Edit finansial yang sudah memiliki ledger tetap terkunci; penukaran baru hanya pada edit yang masih sah.
3. `app/Services/LoyaltyLedgerService.php`: tulis satu penukaran dengan delta asli dan cache atomik; unique/check DB menjadi pertahanan kedua.
4. `app/Services/CancellationService.php`: pengembalian memakai negatif delta `penukaran` asal, termasuk ketika N/config berubah atau program mati; retry tidak menambah baris.
5. `app/Http/Controllers/App/TransactionController.php`, `resources/js/Pages/App/TransactionCreate.tsx`, `resources/js/Pages/App/TransactionEdit.tsx`: tampilkan kelayakan hadiah dan pilihan baris, nilai diskon, alasan minimum berat; konfirmasi ulang saat quote 409.
6. `tests/Feature/Loyalty/RedemptionTest.php`, `tests/Integration/RedemptionConcurrencyTest.php`: harga, stale quote, dua koneksi MySQL, cancel/merge dan tenant/cabang.

## 4. Scope of Changes

1. Tentukan kecocokan hadiah dari master dan layanan cabang aktif menurut normalisasi PRD; saldo negatif/tidak cukup tidak mendapat tawaran.
2. Hitung potongan dari berat aktual satu baris, bukan berat minimum; tulis snapshot hadiah dan ledger dalam transaksi yang sama dengan create/edit.
3. Tolak penukaran ganda, input harga/delta client, quote usang, potongan nol dan resource tenant/cabang asing.
4. Pertahankan pengecualian earning pada transaksi yang menukar stempel, termasuk total Rp0.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Hadiah 5 kg | N=10, 5 kg @Rp7.000, maks 3 kg | Potongan Rp21.000, sisa 2 kg Rp14.000, ledger −10 | `[ ]` |
| Batas minimum | Aktual 2 kg, minimum 3 kg, harga Rp7.000, maks 3 kg | Subtotal Rp21.000, potongan Rp14.000, sisa Rp7.000 | `[ ]` |
| Nol/kelayakan | Baris item, layanan tak cocok/nonaktif, saldo 9 atau negatif, potongan nol | Tidak ditawarkan/ditolak tanpa ledger | `[ ]` |
| Race | Saldo 10, dua create menukar 10 bersamaan | Tepat satu berhasil; cache=SUM dan saldo tidak negatif karena penukaran | `[ ]` |
| Stale | N, harga, master atau saldo berubah setelah preview | Save 409 dengan quote baru; tidak ada ledger/transaction parsial | `[ ]` |
| Batal | Tukar N=10, N berubah ke 5/program off, cancel dua kali | Satu pengembalian +10; earning tidak muncul | `[ ]` |
| Edit | Edit finansial sah menambah hadiah; edit lagi sesudah ledger | Pertama menulis −N sekali; berikutnya ditolak kecuali pembatalan | `[ ]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=Redemption`
2. `rtk proxy docker compose exec -T app php artisan test --filter=PricingQuoteTest`
3. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
4. `rtk git diff --check`

Expected: dua koneksi MySQL membuktikan satu penukaran berhasil; seluruh nominal/snapshot sesuai PRD.

## 7. Out of Scope

1. Potongan promo dan promo gabungan pada quote.
2. Edit atau refund stempel di luar alur pembatalan transaksi.

## 8. Completion Checklist

- [ ] Kontrak teknis dan matriks penerimaan lulus.
- [ ] Hasil uji serta batas bukti dicatat sebelum status `DONE`.
