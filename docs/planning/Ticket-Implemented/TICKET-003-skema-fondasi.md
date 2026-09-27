# Implementation Plan: TICKET-003 (Skema fondasi dan tabel pendukung M1)

**Ticket:** `TICKET-003`
**Status:** `DONE`
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-002`
**Tahap:** Urutan 1 — M1, pekerjaan 3

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Skema database](../../initiate-file/database-schema.md) bagian 2–5; [arsitektur](../../initiate-file/architecture.md) bagian 3–5 |
| Keterlacakan | US-102 AC1–4, US-103 AC1/4, US-105 AC2/5, US-106 AC1/2/4, US-107 AC1/4, US-108 AC6/8, US-109 AC4; ISO-06, AND-01, AND-08, AND-19, DAT-01, SEC-03, PLH-03 |
| Database | Migrasi satu-satunya sumber skema; MySQL 8.4/InnoDB/utf8mb4; constraint, indeks, FK dan collation mengikuti spesifikasi |
| Loyalti awal | Satu row per bisnis, is_active=false, stempel_dibutuhkan=10, master_service_id=null, berat_maks_gratis=null; provisioning di TICKET-006 |
| Tabel lintas milestone | Hanya tabel pendukung yang diperlukan guard/statistik/snapshot M1; bukan implementasi fitur M2–M4 |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Membuat struktur data yang dapat menegakkan kepemilikan bisnis/cabang dan mendukung pengujian M1 dengan data nyata. Mencegah guard cabang atau angka developer menjadi stub karena tabel transaksi belum ada.

## 3. Non-Negotiable Technical Contract

1. `database/migrations/`: buat tabel fondasi `businesses`, `business_settings`, `users`, `branches`, `master_services`, `services`, `loyalty_settings`, `audit_logs` dengan seluruh kolom/constraint spesifikasi, bukan subset dummy.
2. `database/migrations/`: buat tabel pendukung `customers`, `promos`, `transactions`, `transaction_items`, `notification_logs` beserta seluruh kontrak skemanya. Urutkan parent lebih dahulu; tambahkan FK sesudah parent tersedia bila perlu. Tabel ini diperlukan untuk statistik, guard cabang, snapshot, dan invalidasi lifecycle; belum menyediakan endpoint domainnya.
3. `database/migrations/`: pertahankan satu migrasi efektif untuk masing-masing `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks` yang disiapkan scaffold. Jangan menggandakan tabel users/infrastruktur bawaan.
4. `app/Models/`: model fondasi dan model pendukung hanya relasi/cast/hidden yang dibutuhkan M1; encrypted TEXT untuk wa_token/wa_config/smtp_config. Scope/policy di TICKET-004 sebelum endpoint bisnis tersedia.
5. `database/factories/`, `tests/Feature/Foundation/SchemaContractTest.php`: fixtures valid dua bisnis, beberapa cabang dan actor; gunakan MySQL nyata untuk FK/CHECK/generated unique owner, bukan SQLite.
6. `docs/data-model.md`: catat tabel yang sudah dimigrasikan dan tabel yang masih rancangan. Nama file migrasi ditentukan timestamp scaffold; nama tabel/kontrak tetap mengikuti daftar di atas.

## 4. Scope of Changes

### A. Skema dan relasi

1. Terapkan unique email global, CHECK role/branch/business, generated `owner_business_id` unique, FK komposit lintas tenant, default setting, serta ON DELETE/UPDATE sesuai sumber.
2. Normalisasi/keunikan nama layanan memakai collation case-insensitive accent-sensitive; validasi lintas row tetap tanggung jawab service.
3. Audit tambah-saja tanpa updated_at; tidak ada cascade delete data bisnis atau kolom keuangan turunan baru.

### B. Fixtures dan batas milestone

1. Buat fixture lengkap transaksi/item/notifikasi yang memenuhi constraint untuk tes M1; bukan seeder bisnis nyata.
2. Tabel `payments`, `status_histories` dilanjutkan M2; `promo_branches`, `loyalty_histories` M4. Migrasi berikutnya memakai tabel existing, tanpa recreate/drop data.
3. Catat tabel pendukung sebagai skema tersedia, fitur operasional belum tersedia; jangan menandai story M2–M4 selesai.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| DB kosong | Migrasi semua tabel lingkup | Berhasil sesuai urutan FK; migrasi kedua no-op | `[x]` |
| Satu owner | Insert dua owner bisnis sama | Owner kedua ditolak oleh database | `[x]` |
| Batas role | Developer tanpa tenant; admin tanpa cabang | Developer sah, admin ditolak CHECK | `[x]` |
| Relasi silang | Service/user/transaksi menunjuk cabang bisnis lain | FK komposit menolak, tidak ada data parsial | `[x]` |
| Defaults | Row loyalty_settings baru tanpa hadiah | Nonaktif, N10, hadiah/maks null; N0 ditolak | `[x]` |
| Nilai katalog | Harga0, durasi0, minimum item terisi | Constraint menolak; nilai domain sah diterima | `[x]` |
| Histori | Hapus parent yang memiliki child | RESTRICT; snapshot FK opsional mengikuti SET NULL yang ditetapkan | `[x]` |
| Rahasia | Serialisasi model bisnis bercredential | Rahasia hidden; ciphertext tersimpan pada TEXT | `[x]` |
| Rollback migrasi | Rollback pada DB uji terpisah lalu migrate | Tidak ada kegagalan urutan FK; data lokal pengguna tidak disentuh | `[x]` |

## 6. Verification Commands

Target perintah setelah TICKET-002. Migrasi/rollback destruktif hanya pada database uji sekali pakai yang namanya diverifikasi, bukan DB pengembangan pengguna.

```bash
rtk proxy docker compose exec -T app php artisan migrate --force
rtk proxy docker compose exec -T app php artisan migrate:status
rtk proxy docker compose exec -T app php artisan test --filter=SchemaContractTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk proxy python3 docs/audits/validate-final-specs.py
rtk git diff --check
```

Ekspektasi: constraint diuji dengan insert gagal/sukses nyata; tidak menganggap FK membuktikan policy atau seluruh invarian service.

## 7. Out of Scope

1. CRUD customer/transaksi, pembayaran, status, kalkulasi harga, pengiriman notifikasi, promo dan ledger.
2. Aktivasi loyalti serta akun bootstrap developer (TICKET-005).
3. Perubahan kontrak skema produk untuk mempermudah scaffold.

## 8. Completion Checklist

- [x] Lingkup diotorisasi dan dependensi selesai.
- [x] Seluruh tabel lingkup dan constraint diverifikasi di MySQL 8.4.
- [x] Matriks penerimaan selesai; tidak ada skema dummy atau migrasi ganda.
- [x] Data model membedakan implementasi skema dan fitur yang belum ada.
- [x] Handoff, sesi dan arsip diperbarui sesuai workflow.

## Hasil implementasi dan verifikasi

Selesai pada 28 September 2026 sesuai otorisasi pengguna. Bukti rinci, matriks per AC dan batas integrasi M2–M6 ada di [audit M1](../../audits/m1-verification.md); perintah aktual di [development](../../development.md).

CI final: [36350950049](https://github.com/cleveradit/ceklaundry/actions/runs/36350950049), 52 tes/306 assertions, quality/build/MySQL/browser lulus. Status checklist berlaku untuk lingkup M1; consumer masa depan tidak diklaim lulus E2E. Kasus kegagalan diuji melalui fault/guard dan setup bersih.
