# Implementation Plan: TICKET-015 (Status, kunci edit, dan pembatalan)

**Ticket:** `TICKET-015`
**Status:** `DONE`
**Hasil:** [Audit M2](../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-014`
**Tahap:** M2 — lifecycle transaksi

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD 6/6.1/7.3/7.6](../initiate-file/prd.md), [US-204/205/207/208](../initiate-file/user-stories.md), [arsitektur 4/5](../initiate-file/architecture.md) |
| Keterlacakan | US-204 AC2/3, US-205 AC1–5, US-207 AC1/2, US-208 AC1–10; AND-01/08/09/13/14/18/23, SEC-07, ISO-02 |
| Status | Tiga edge maju, tiga edge batal dari state aktif; same-state retry no-op, dua terminal final. Pickup mensyaratkan LUNAS. |
| Kunci | Edit harga hanya DITERIMA, belum LUNAS, tanpa payment/ledger; edit nonfinansial hanya DITERIMA; tidak ada buka-kunci. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Riwayat status, perubahan yang masih sah, dan pembatalan berjalan atomik serta terjejak. Pembayaran historis tetap ada saat batal, sementara pembatalan menandai transaksi keluar dari laporan pada M5.

## 3. Non-Negotiable Technical Contract

1. `app/Services/TransactionStateMachine.php`: seluruh 5×5 pasangan status; expected_version stale409; timestamps/history server; pickup memeriksa SUM/status bayar setelah lock.
2. `app/Services/TransactionService.php`: edit finansial memakai quote terkini+fingerprint dan ganti semua snapshot atomik; edit catatan/estimasi/perkiraan baju tanpa reprice, hanya DITERIMA.
3. `app/Services/CancellationService.php`: alasan wajib, audit dan history satu commit; payment tetap; kompensasi hanya untuk ledger asal yang benar-benar ada, satu kali; pending notification yang belum diotorisasi ditutup sesuai kontrak.
4. `app/Http/Controllers/App/TransactionController.php`, `routes/web.php`, `resources/js/Pages/App/TransactionDetail.tsx`: aksi status/edit/batal dengan konfirmasi dan pesan Indonesia.
5. `tests/Feature/Operations/TransactionLifecycleTest.php`, `tests/Integration/TransactionLifecycleConcurrencyTest.php`: matriks state, fault, stale tab, payment/edit/cancel race.

## 4. Scope of Changes

1. Isi `waktu_siap_diambil`/`waktu_diambil` pada edge sah; history DITERIMA awal dari TICKET-013 tidak digandakan.
2. Total Rp0 terkunci sejak LUNAS meski tidak ada payment/ledger. Edit nonfinansial tidak mengubah total/snapshot.
3. Pembatalan menampilkan label dan alasan aman; pengembalian dana di luar aplikasi. Kompensasi ledger M4 diimplementasikan pada peristiwa yang ada, dan diuji ulang saat M4 aktif.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| State | Semua 25 pasangan, versi benar | Hanya 3 maju+3 batal; sama no-op; lainnya ditolak | Lihat [audit M2](../audits/m2-verification.md) |
| Pickup | SIAP_DIAMBIL DP lalu LUNAS | DP ditolak dengan sisa; setelah lunas berhasil, waktu diambil terisi | Lihat [audit M2](../audits/m2-verification.md) |
| Edit | DITERIMA belum bayar vs ada payment/ledger vs total0 | Hanya pertama boleh reprice; lainnya terkunci | Lihat [audit M2](../audits/m2-verification.md) |
| Nonfinansial | Catatan/estimasi saat DITERIMA lalu DIPROSES | Pertama sah tanpa reprice; kedua ditolak | Lihat [audit M2](../audits/m2-verification.md) |
| Batal/race | Alasan kosong, dua cancel, payment vs cancel, fault audit | Alasan wajib; satu audit/kompensasi; serial/rollback, payment tak dihapus | Lihat [audit M2](../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=TransactionLifecycleTest
rtk proxy docker compose exec -T app php artisan test --filter=TransactionLifecycleConcurrencyTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

## 7. Out of Scope

1. Pengiriman otomatis saat siap M3; uji lagi status nyata dengan reservasi job M3.
2. Pembentukan earning/redemption stempel M4 dan laporan pendapatan M5.

## 8. Completion Checklist

- [x] Otorisasi implementasi, matriks dan verifikasi selesai.
- [x] Integrasi M3/M4/M5 yang belum nyata dicatat tanpa status lulus palsu.
