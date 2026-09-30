# Implementation Plan: TICKET-031 (Kelola promo owner)

**Ticket:** `TICKET-031`  
**Status:** `DONE`

**Hasil:** Implementasi dan tingkat bukti aktual dicatat pada [audit M4](../../audits/m4-verification.md). Matriks di bawah adalah rencana penerimaan; audit memisahkan tes langsung dari pemeriksaan parsial.

**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-030`  
**Tahap:** M4 — katalog promo

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M4](../../plan.md#8-m4--loyalti-dan-promo), [PRD 7.8](../../initiate-file/prd.md#78-harga-snapshot-dan-loyalti-yang-deterministik), US-406 AC1–2, skema 2.11–2.12 |
| Keterlacakan | US-406; FR-P01/FR-O08; ISO-01/05/06, AND-08/19/22/23, UX-01/04, LOK-01 |
| Promo | Owner membuat, mengubah dan menonaktifkan promo. Tipe persen 1–100 atau nominal rupiah positif; minimum null/0 berarti tanpa minimum; `mulai <= selesai` inklusif tanggal WIB. |
| Cakupan | Semua cabang, atau sedikitnya satu cabang dari bisnis sendiri. Edit/nonaktif hanya memengaruhi quote/save finansial selanjutnya; snapshot transaksi lama tetap. Tidak ada hapus promo lewat UI. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-030–036 pada 30 September 2026. |

## 2. Objective

Owner dapat memelihara promo bernama dengan periode dan cakupan cabang yang valid. Data promo menjadi sumber pilihan transaksi pada tiket berikutnya tanpa memberi admin hak mengubahnya.

## 3. Non-Negotiable Technical Contract

1. `app/Http/Controllers/Owner/PromoController.php`, `resources/js/Pages/Owner/Promos.tsx`, `routes/web.php`: daftar, buat, edit, nonaktif; hanya owner tenant sendiri.
2. `app/Services/PromoService.php`: validasi dan simpan `promos` serta `promo_branches` dalam satu `BusinessTransaction`, dengan business root lock dan FK/cabang satu tenant.
3. `database/migrations/2026_09_28_000002_create_m1_supporting_tables.php`: gunakan skema promo/pivot yang sudah ada; migrasi tambahan hanya untuk selisih kontrak yang dibuktikan.
4. `tests/Feature/Promo/PromoManagementTest.php`: nilai batas, periode WIB, cakupan, tenant asing, peran, lifecycle dan transaksi lama yang telah memiliki snapshot.

## 4. Scope of Changes

1. Form owner menampilkan promo aktif/nonaktif, tanggal, tipe, nilai, minimum, dan cakupan cabang.
2. Validasi server menolak kombinasi sebagian cabang kosong, cabang asing, persen >100, nominal 0 dan periode terbalik. Simpan pivot atomik ketika cakupan berubah.
3. Menonaktifkan promo melalui flag, bukan menghapus transaksi atau snapshot yang pernah memakai promo.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Promo sah | Owner A membuat persen 10, 1–30 September WIB, cabang A1 | Promo dan pivot tersimpan; hanya owner A dapat mengubah | [audit M4](../../audits/m4-verification.md) |
| Batas | Persen 1/100, nominal 1, minimum null/0, tanggal awal=akhir | Sah dan konsisten dalam tanggal WIB | [audit M4](../../audits/m4-verification.md) |
| Invalid | Persen 101, nominal 0, tanggal akhir sebelum awal, cakupan kosong/asing | Ditolak atomik tanpa pivot yatim | [audit M4](../../audits/m4-verification.md) |
| Edit | Nama/nilai/cakupan diubah atau promo dinonaktifkan | Konfigurasi baru tersimpan; snapshot transaksi lama tidak berubah | [audit M4](../../audits/m4-verification.md) |
| Isolasi | Admin, developer atau owner B mencoba ID promo A; bisnis read-only | Tidak dapat mengubah; ID tenant lain 404 | [audit M4](../../audits/m4-verification.md) |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=PromoManagementTest`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk git diff --check`

Expected: seluruh kasus lulus dengan MySQL QA; perubahan promo tidak menyentuh transaksi historis.

## 7. Out of Scope

1. Kalkulasi potongan, pilihan promo di transaksi, dan tampilan resi.
2. Hapus promo secara fisik atau promo bertumpuk dalam satu transaksi.

## 8. Completion Checklist

- [x] Implementasi, pengujian lokal/QA, serta batas bukti dicatat pada [audit M4](../../audits/m4-verification.md).
