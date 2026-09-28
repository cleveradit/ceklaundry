# Implementation Plan: TICKET-016 (Penggabungan pelanggan)

**Ticket:** `TICKET-016`
**Status:** `DONE`
**Hasil:** [Audit M2](../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-012`, `TICKET-015`
**Tahap:** M2 — identitas bersama

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD 5.B/7.9/9.2](../initiate-file/prd.md), [US-214](../initiate-file/user-stories.md), [arsitektur 4/5](../initiate-file/architecture.md) |
| Keterlacakan | US-214 AC1–5/9; AND-01/15/23, ISO-01/02/04, SEC-07 |
| Hak admin | Merge hanya jika seluruh transaksi source dan target, termasuk historis/batal, ada di cabangnya; customer tanpa transaksi boleh. Owner lintas cabang bisnis sendiri. |
| Mutasi | Identitas target tetap; pindah semua FK transaksi/ledger, hitung cache saldo dari ledger, audit snapshot; source dihapus terakhir. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Duplikat customer dapat digabung tanpa kehilangan riwayat, payment atau snapshot. Pembatasan admin berlaku sampai saat commit dan tidak membocorkan cabang lain.

## 3. Non-Negotiable Technical Contract

1. `app/Services/CustomerMergeService.php`: business root→dua customer ID menaik→transactions ID menaik→logs ID menaik; validasi ulang hak cabang setelah lock; FK transaksi/ledger, cache SUM, audit dan delete source satu commit.
2. `app/Http/Controllers/App/CustomerMergeController.php`, `routes/web.php`: preview dan POST konfirmasi eksplisit; cross-tenant404, admin histori cabang lain403 generik.
3. `resources/js/Pages/App/Customers.tsx`: dialog identitas source/target dan dampak merge, tombol konfirmasi jelas.
4. `tests/Feature/Operations/CustomerMergeTest.php`, `tests/Integration/CustomerMergeConcurrencyTest.php`: FK, audit, saldo negatif, dua arah merge, race dengan create/payment/cancel.

## 4. Scope of Changes

1. Jangan memindahkan nilai/delta ledger ke jenis lain; `notification_email` dan snapshot uang transaksi tetap.
2. Batalkan pending verifikasi email source bila ada; jangan mengubah pending target.
3. Ketika dispatcher WA M3 tersedia, perluas unit yang sama untuk US-214 AC6–9: pending belum attempted retarget, attempted review, token lama dicabut. M2 tidak mengklaim kiriman WA nyata teruji.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Merge owner | Dua customer, aktif/historis, saldo termasuk negatif | Identitas target tetap, FK pindah, cache=SUM ledger, audit aman | Lihat [audit M2](../audits/m2-verification.md) |
| Hak admin | Salah satu transaksi historis cabang lain | 403 tanpa rincian; tidak ada mutasi | Lihat [audit M2](../audits/m2-verification.md) |
| Invalid | Source=target, tenant lain, source hilang | Ditolak tanpa orphan/bocoran | Lihat [audit M2](../audits/m2-verification.md) |
| Race | Merge A→B vs B→A, create/payment/cancel | Salah satu urutan sah, tanpa orphan/double delta | Lihat [audit M2](../audits/m2-verification.md) |
| Fault | Gagal sesudah pemindahan FK sebelum delete/audit | Seluruh unit rollback; nomor source tetap terpakai | Lihat [audit M2](../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=CustomerMergeTest
rtk proxy docker compose exec -T app php artisan test --filter=CustomerMergeConcurrencyTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

## 7. Out of Scope

1. UI/dispatch log WA M3 dan saldo/perolehan stempel aktif M4; integrasi fixture tidak menggantikan uji M3/M4 nyata.
2. Penggabungan lintas bisnis atau pembatalan payment.

## 8. Completion Checklist

- [x] Otorisasi implementasi, matriks dan verifikasi selesai.
- [x] Kewajiban uji ulang recipient WA dan ledger aktif dicatat untuk M3/M4.
