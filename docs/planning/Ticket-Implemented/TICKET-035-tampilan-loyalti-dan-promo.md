# Implementation Plan: TICKET-035 (Riwayat, status publik, resi dan notifikasi M4)

**Ticket:** `TICKET-035`  
**Status:** `DONE`

**Hasil:** Implementasi dan tingkat bukti aktual dicatat pada [audit M4](../../audits/m4-verification.md). Matriks di bawah adalah rencana penerimaan; audit memisahkan tes langsung dari pemeriksaan parsial.

**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-032`, `TICKET-033`, `TICKET-034`  
**Tahap:** M4 — penyajian hasil

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M4](../../plan.md#8-m4--loyalti-dan-promo), US-404 AC1–2, US-405 AC1–2, [PRD 7.9](../../initiate-file/prd.md#79-identitas-cabang-dan-merge) |
| Keterlacakan | US-404/405, US-403 AC1/6, US-407 AC1/4; FR-L04/FR-C07/FR-R03; ISO-01/02/05, AND-08/15/16/23, SEC-06, UX-04/05/06, LOK-01 |
| Hak lihat | Owner melihat seluruh ledger bisnis. Admin melihat saldo global customer, tetapi hanya baris ledger dari cabangnya dan tidak mendapat tautan/ID operasi cabang lain. Publik hanya melihat saldo/target ketika program aktif. |
| Saldo negatif | Tampilkan angka bertanda sebenarnya dan pesan bahwa kekurangan harus diperoleh kembali; jangan clamp nol. |
| Historis | Nominal/item/promo/hadiah dari snapshot transaksi; identitas customer/cabang dari data master terkini sesuai PRD. Resi dan pemberitahuan memakai angka total/sisa yang benar. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-030–036 pada 30 September 2026. |

## 2. Objective

Owner/admin dapat mengaudit ledger sesuai hak cabang, sedangkan pelanggan dapat melihat saldo aktif dan potongan transaksi tanpa membuka data pribadi. Resi cetak dan isi notifikasi konsisten dengan harga tersimpan.

## 3. Non-Negotiable Technical Contract

1. `app/Http/Controllers/App/CustomerController.php`, `resources/js/Pages/App/Customers.tsx`: endpoint/detail riwayat customer berfilter business dan cabang; saldo global dihitung/ditampilkan terpisah dari baris yang boleh dilihat admin.
2. `app/Http/Controllers/App/TransactionController.php`, `resources/js/Pages/App/TransactionDetail.tsx`: tampilkan snapshot hadiah/promo, dua potongan, ledger terkait yang diizinkan, dan total/sisa tanpa membuka operasi cabang lain.
3. `app/Services/PublicReceiptService.php`, `resources/views/public/status.blade.php`: saldo dan target hanya saat program aktif, termasuk saldo negatif dengan penjelasan; data pribadi tetap disamarkan dan halaman tanpa JS tetap berfungsi.
4. `resources/views/receipts/thermal.blade.php`, `app/Services/ManualReceiptLinkService.php`, `app/Jobs/SendNotification.php`, `resources/views/emails/ready.blade.php`, `resources/views/emails/reminder.blade.php`: nilai snapshot, total dan sisa sesuai transaksi pada resi/tautan/isi notifikasi yang relevan; tidak mengubah kontrak pengiriman M3.
5. `tests/Feature/Loyalty/LoyaltyPresentationTest.php`, `tests/Feature/Public/ReceiptLookupTest.php`, `tests/Feature/Operations/ReceiptPrintTest.php`: hak cabang, masking, saldo negatif, program off, snapshot lama dan layout 58 mm.

## 4. Scope of Changes

1. Sajikan empat jenis ledger, delta bertanda, transaksi, waktu, dan total saldo; cek cache terhadap SUM pada pembacaan/uji.
2. Perlihatkan saldo publik ketika aktif dan sembunyikan ketika nonaktif, tanpa membuka identitas lengkap atau histori ledger pelanggan lain.
3. Perbarui detail, status, resi thermal dan konten komunikasi transaksi agar nominal hadiah/promo/terbayar/sisa konsisten dengan snapshot yang tersimpan.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Ledger | Owner membuka customer dengan empat jenis entry dari dua cabang | Seluruh delta/transaksi/waktu tampil; saldo=SUM=cache | [audit M4](../../audits/m4-verification.md) |
| Cabang | Admin cabang 1 membuka customer global yang punya entry cabang 2 | Saldo global terlihat; baris/link/ID cabang 2 tidak terpapar | [audit M4](../../audits/m4-verification.md) |
| Publik aktif | Saldo −1, target 10, cek `/t/{kode}` | `−1/10` dan penjelasan kekurangan; identitas tetap masked | [audit M4](../../audits/m4-verification.md) |
| Publik nonaktif | Program dimatikan setelah ledger ada | Bagian stempel hilang; potongan historis tetap terlihat | [audit M4](../../audits/m4-verification.md) |
| Snapshot | Harga/promo/master berubah sesudah transaksi tersimpan | Detail, struk dan status memakai nominal/item/promo/hadiah lama | [audit M4](../../audits/m4-verification.md) |
| Komunikasi | Transaksi hadiah+promo, DP, email/tautan manual | Total dan sisa sama dengan transaksi; kegagalan provider tidak memengaruhi transaksi | [audit M4](../../audits/m4-verification.md) |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=LoyaltyPresentationTest`
2. `rtk proxy docker compose exec -T app php artisan test --filter=ReceiptLookupTest`
3. `rtk proxy docker compose exec -T app php artisan test --filter=ReceiptPrintTest`
4. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
5. `rtk git diff --check`

Expected: hak cabang dan masking lulus; nominal semua permukaan cocok snapshot tersimpan.

## 7. Out of Scope

1. Fitur laporan/CSV M5 dan demo/PWA M6.
2. Klaim kiriman provider eksternal benar-benar diterima pelanggan tanpa bukti staging.

## 8. Completion Checklist

- [x] Implementasi, pengujian lokal/QA, serta batas bukti dicatat pada [audit M4](../../audits/m4-verification.md).
