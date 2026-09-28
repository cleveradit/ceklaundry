# Implementation Plan: TICKET-013 (Pembuatan transaksi dan snapshot)

**Ticket:** `TICKET-013`
**Status:** `DONE`
**Hasil:** [Audit M2](../../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-011`, `TICKET-012`
**Tahap:** M2 — transaksi masuk

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD 4.2/6.1/7.3/7.7–7.9](../../initiate-file/prd.md), [US-203/204/215](../../initiate-file/user-stories.md), [arsitektur 4/5](../../initiate-file/architecture.md) |
| Keterlacakan | US-203 AC1–10, US-204 AC1/3, US-215 AC1/2; AND-01/08/18/19/22, ISO-01/02/06 |
| Identitas | Resi CSPRNG alfabet `ABCDEFGHJKMNPQRSTUVWXYZ23456789`, 6 karakter, UNIQUE global; UUID+hash create tetap tersimpan. |
| Pembayaran awal | TICKET-014 menambahkan pembayaran awal dan mengulang seluruh save atomik; sampai itu selesai, create tanpa pembayaran adalah bagian yang dapat diuji, belum alur M2 lengkap. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Admin atau owner cabang pilihan dapat menyimpan satu transaksi beserta item dan riwayat awal secara atomik. Harga, estimasi, identitas pembuat, waktu, dan resi berasal dari server; retry tidak membuat duplikat.

## 3. Non-Negotiable Technical Contract

1. `app/Services/ReceiptCodeGenerator.php`: CSPRNG, unique DB global, maksimal 10 collision sebelum galat aman.
2. `app/Services/TransactionService.php`: business root→customer→transaction lock, validasi aktor/cabang/customer/service terbaru, hitung ulang quote, simpan transaksi+items+history DITERIMA dalam satu commit; customer inline baru ikut commit/rollback.
3. `app/Http/Controllers/App/TransactionController.php`, `routes/web.php`: quote→konfirmasi→save; UUID key/hash sama mengembalikan transaksi yang sama sesudah policy, hash lain409; fingerprint usang409 dengan quote baru.
4. `resources/js/Pages/App/TransactionCreate.tsx`: form HP, satu aksi simpan, UUID tetap ketika respons hilang, petunjuk resi terpisah express/reguler.
5. `tests/Feature/Operations/TransactionCreateTest.php`, `tests/Integration/TransactionCreateConcurrencyTest.php`: fault, retry, branch deactivate/sync race MySQL.

## 4. Scope of Changes

1. Gunakan tabel M1 yang ada; snapshot seluruh atribut item, email master saat create, `status_bayar` dari total (Rp0=LUNAS).
2. Jumlah, estimasi dan timestamp berasal dari server; item minimal satu, maksimal seratus.
3. Ulangi bukti M1 untuk statistik developer, snapshot katalog/sync, guard nonaktif cabang dan sesi admin pindah dengan transaksi nyata.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Campuran | Customer lama, 3,5 kg + 2 item | Total Rp74.500, dua snapshot item, history DITERIMA, resi unik | Lihat [audit M2](../../audits/m2-verification.md) |
| Retry | UUID/hash sama dua kali/paralel; hash berbeda | Satu transaksi; replay sama; beda409 | Lihat [audit M2](../../audits/m2-verification.md) |
| Quote usang | Harga/cabang berubah setelah preview | 409, tidak ada transaksi parsial | Lihat [audit M2](../../audits/m2-verification.md) |
| Inline gagal | Nomor customer duplikat/fault sesudah item | Seluruh create rollback; pilih customer existing | Lihat [audit M2](../../audits/m2-verification.md) |
| Keamanan | Admin pindah cabang, customer/service luar tenant, branch mati | 403/404 atau validasi sesuai kontrak, tanpa data baru | Lihat [audit M2](../../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=TransactionCreateTest
rtk proxy docker compose exec -T app php artisan test --filter=TransactionCreateConcurrencyTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

Tes concurrency wajib dua koneksi MySQL terpisah, tanpa enclosing transaction test.

## 7. Out of Scope

1. Payment awal/lanjutan dan DP UI sampai TICKET-014.
2. Status lanjut, cetak, notifikasi, promo/stempel aktif.

## 8. Completion Checklist

- [x] Otorisasi implementasi, matriks dan verifikasi selesai.
- [x] Integrasi payment awal TICKET-014 dan perjalanan E2E TICKET-020 masih tercatat.
