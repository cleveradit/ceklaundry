# Implementation Plan: TICKET-030 (Pengaturan program stempel)

**Ticket:** `TICKET-030`  
**Status:** `REVIEW`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-029`  
**Tahap:** M4 — fondasi loyalti

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M4](../plan.md#8-m4--loyalti-dan-promo), [PRD 7.8](../initiate-file/prd.md#78-harga-snapshot-dan-loyalti-yang-deterministik), US-401 AC1–5 |
| Keterlacakan | US-401; FR-L01/FR-O09; ISO-01/05/06, AND-08/16/19, UX-01/04, LOK-01 |
| Konfigurasi | Satu program per bisnis, berlaku lintas cabang. Awalnya nonaktif; N 1–255 selalu valid. Hadiah adalah master service aktif bersatuan kg milik bisnis, berat maksimal >0 saat aktif. Saat nonaktif, hadiah dan berat boleh kosong. |
| Perubahan | Mematikan program menghentikan penawaran dan perolehan baru; ledger lama tetap. Aktivasi ulang tidak memberi stempel retroaktif. Rename master tidak menebak layanan cabang; master hadiah aktif tidak dapat dinonaktifkan/diubah satuan. |
| Otorisasi | Pengguna meminta pembuatan tiket M4; implementasi tiket belum diminta. |

## 2. Objective

Owner dapat mengatur syarat hadiah stempel untuk seluruh cabang bisnisnya. Penyimpanan dan pengubahan master service menjaga konfigurasi tetap sah tanpa mengubah hadiah transaksi lama.

## 3. Non-Negotiable Technical Contract

1. `app/Http/Controllers/Owner/LoyaltySettingController.php`, `resources/js/Pages/Owner/LoyaltySettings.tsx`, `routes/web.php`: baca/ubah hanya milik owner tenant; form menjelaskan N, master hadiah kg, berat maksimal dan saklar program.
2. `app/Services/LoyaltySettingsService.php`, `app/Services/BusinessTransaction.php`: perubahan memakai business root lock, validasi master satu bisnis, aktif, kg; lifecycle baca-saja menolak write.
3. `app/Http/Controllers/Owner/MasterServiceController.php` dan service pengubahan master yang dipakainya: blokir nonaktif atau perubahan satuan master yang sedang menjadi hadiah program aktif; rename tidak mengubah layanan cabang.
4. `database/migrations/2026_09_28_000001_create_m1_foundation.php`: pertahankan kontrak `loyalty_settings` yang sudah tersedia; tambah migrasi hanya bila uji menemukan selisih nyata, tanpa mengubah migrasi lama yang telah berjalan.
5. `tests/Feature/Loyalty/LoyaltySettingsTest.php`: validasi, owner/admin/developer, tenant asing 404, read-only, pergantian master dan siklus aktif/nonaktif.

## 4. Scope of Changes

1. Sediakan halaman pengaturan owner dan pilihan master kg aktif tenant sendiri, termasuk kondisi hadiah tidak tersedia di cabang.
2. Simpan saklar/N/master/berat secara atomik; validasi nilai kosong saat nonaktif dan kelengkapan saat aktif.
3. Tegakkan guard pengubahan master hadiah; beri pesan Indonesia agar owner menyinkronkan cabang bila nama/satuan cabang tidak cocok.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Aktivasi | Owner A memilih master kg aktif A, N=10, maks=3,0 kg | Konfigurasi berlaku untuk seluruh cabang A, tidak terlihat pada B | `[ ]` |
| Batas nonaktif | Program mati, master/berat kosong, N=1 atau 255 | Sah; saldo/ledger lama tidak berubah | `[ ]` |
| Aktivasi gagal | N=0/256, berat=0, master item/nonaktif/tenant B | Ditolak tanpa perubahan parsial | `[ ]` |
| Master hadiah | Program aktif lalu master diubah ke item/dinonaktifkan | Ditolak; rename tidak memetakan otomatis layanan cabang | `[ ]` |
| Hak akses | Admin/developer atau owner B mencoba ubah milik A; tenant read-only | Ditolak sesuai policy/lifecycle, resource asing 404 | `[ ]` |
| Siklus saklar | Program off→on setelah transaksi lama LUNAS | Tidak ada perolehan retroaktif | `[ ]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=LoyaltySettingsTest`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk git diff --check`

Expected: seluruh kasus lulus pada MySQL QA; tidak ada perubahan migrasi lama atau whitespace error.

## 7. Out of Scope

1. Penulisan ledger, perolehan, penukaran, promo, dan tampilan saldo publik.
2. Sinkronisasi otomatis nama layanan cabang ketika master hadiah di-rename.

## 8. Completion Checklist

- [ ] Kontrak teknis dan matriks penerimaan lulus.
- [ ] Hasil uji serta batas bukti dicatat sebelum status `DONE`.
