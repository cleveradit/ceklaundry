# Implementation Plan: TICKET-017 (Dashboard, pencarian, dan operasi owner)

**Ticket:** `TICKET-017`
**Status:** `DONE`
**Hasil:** [Audit M2](../../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-015`, `TICKET-016`
**Tahap:** M2 — panel operasional

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD 5.B/7.10](../../initiate-file/prd.md), [US-211/212/215](../../initiate-file/user-stories.md) |
| Keterlacakan | US-211 AC1–3, US-212 AC1/2, US-215 AC1–3 (M3/M4 nanti); AND-24, ISO-01/02/03/05, UX-03/04, LOK-01/03 |
| Hari/umur | WIB; daftar hari ini semua status; late hanya DITERIMA/DIPROSES; siap urut waktu lalu ID, umur floor detik/86400. |
| Peran | Owner memilih cabang miliknya dan memakai semua operasi admin M2; tidak punya hak buka-kunci. Admin tetap satu cabang; developer tanpa detail. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Kasir dapat menemukan pekerjaan masuk, terlambat, dan siap diambil dari HP. Owner dapat turun tangan di tiap cabangnya dengan policy dan validasi service yang sama.

## 3. Non-Negotiable Technical Contract

1. `app/Services/OperationsDashboardService.php`: DTO hari ini, status aktif, siap, terlambat; agregat tanpa join payment yang menggandakan item; cabang terscope.
2. `app/Http/Controllers/App/DashboardController.php`, `app/Http/Controllers/App/TransactionSearchController.php`, `routes/web.php`: search kode/nama/HP terparameterisasi; owner branch selector; admin assignment server.
3. `resources/js/Pages/AdminHome.tsx`, `resources/js/Pages/App/Transactions.tsx`, `resources/js/Layouts/AppLayout.tsx`: alur mobile dan menu owner operasional.
4. `tests/Feature/Operations/OperationsDashboardTest.php`, `tests/Feature/Operations/OwnerOperationsTest.php`: tanggal WIB, search, dua bisnis/cabang, semua aksi owner M2.

## 4. Scope of Changes

1. Dashboard admin mengganti placeholder M1 dengan data transaksi nyata; kartu jumlah/kg mengecualikan batal, kg aktual.
2. Pencarian hanya mengembalikan transaksi cabang yang diizinkan; direktori customer bersama tidak memperluas hasil transaksi.
3. Owner menggunakan route/service operasi yang sama setelah memilih cabang aktif miliknya; histori cabang nonaktif hanya dibaca sesuai policy.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Hari ini | Transaksi batas 00.00 WIB, batal/diambil | Daftar semua status; kartu tidak menghitung batal | Lihat [audit M2](../../audits/m2-verification.md) |
| Terlambat/siap | Estimasi lewat; SIAP lama; terminal | Late hanya dua state; siap urut waktu/ID, umur floor24jam | Lihat [audit M2](../../audits/m2-verification.md) |
| Pencarian | Kode/nama/no HP serta payload injeksi | Hasil terikat cabang; query aman | Lihat [audit M2](../../audits/m2-verification.md) |
| Owner | Pilih dua cabang sendiri, create/payment/status/merge | Aksi sah tercatat owner; policy sama, tanpa buka-kunci | Lihat [audit M2](../../audits/m2-verification.md) |
| Isolasi | Admin cabang lain, developer, bisnis lain | 403/404/daftar kosong; tidak ada ID tersembunyi | Lihat [audit M2](../../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=OperationsDashboardTest
rtk proxy docker compose exec -T app php artisan test --filter=OwnerOperationsTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

## 7. Out of Scope

1. Laporan pendapatan/ekspor owner M5; dashboard M2 adalah antrean operasional.
2. Aksi notifikasi M3 dan promo/stempel M4; US-215 AC3 diuji ulang saat tersedia.

## 8. Completion Checklist

- [x] Otorisasi implementasi, matriks dan verifikasi selesai.
- [x] Browser HP dan isolasi role dicakup pada TICKET-020.
