# Implementation Plan: TICKET-037 (Riwayat transaksi owner dan filter)

**Ticket:** `TICKET-037`
**Status:** `DONE`
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-036`
**Tahap:** M5 — riwayat dan dasar filter laporan

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M5](../../plan.md#9-m5--laporan-owner), [PRD FR-O10/7.10](../../initiate-file/prd.md), [US-501](../../initiate-file/user-stories.md) |
| Keterlacakan | US-501 AC1–2; AND-24, ISO-01/02/03/05, SEC-04/05, KIN-04, LOK-01/03 |
| Rentang | Tanggal WIB `[awal 00.00, sehari setelah akhir 00.00)` diterapkan pada `transactions.waktu_masuk`; input tanggal terbalik atau tidak valid ditolak. |
| Cakupan | Semua cabang bisnis owner termasuk cabang nonaktif; status `DIBATALKAN` tetap tersedia dan berlabel. Filter cabang tidak boleh memilih cabang bisnis lain. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-037–043 pada 1 Oktober 2026. |

## 2. Objective

Owner dapat menelusuri transaksi seluruh cabangnya dengan filter cabang, rentang tanggal, status transaksi, dan status bayar. Riwayat tetap benar setelah cabang dinonaktifkan atau transaksi dibatalkan, tanpa membuka data bisnis lain.

## 3. Non-Negotiable Technical Contract

1. `app/Services/OwnerReportService.php`: sediakan kueri riwayat terscope `business_id` dari konteks autentikasi, filter tanggal WIB half-open pada `waktu_masuk`, cabang, status, dan status bayar; buat filter yang dapat dipakai ulang oleh ekspor TICKET-042.
2. `app/Http/Controllers/Owner/ReportController.php`, `routes/web.php`: route `GET /owner/reports/history` hanya dalam middleware `role:owner`; validasi seluruh filter dan tolak `branch_id` asing tanpa mengungkap datanya.
3. `resources/js/Pages/Owner/TransactionHistory.tsx`, `resources/js/Layouts/AppLayout.tsx`: tabel terpaginasikan, kontrol filter, label batal, dan tautan detail transaksi melalui akses owner yang sudah ada.
4. `tests/Feature/Owner/TransactionHistoryReportTest.php`: uji filter gabungan, batas WIB, cabang nonaktif, batal, pagination, dan isolasi peran/tenant.

## 4. Scope of Changes

1. Tambahkan riwayat owner yang terpisah dari daftar operasional `/app/transactions` agar filter dan izin laporan tidak mengubah perilaku admin M2.
2. Gunakan satu definisi filter server yang kelak dipakai CSV; query tidak melakukan join payment/item yang dapat menggandakan baris transaksi.
3. Tampilkan data secara responsif dan pertahankan filter saat berpindah halaman.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Filter gabungan | Dua cabang, rentang tanggal, status `DIPROSES`, status bayar `DP` | Hanya transaksi cocok dalam bisnis owner; satu baris per transaksi | `[x]` |
| Batas WIB | Transaksi tepat sebelum, pada, dan sesudah tengah malam batas akhir | Hanya waktu dalam rentang half-open yang muncul | `[x]` |
| Historis | Cabang nonaktif dan transaksi `DIBATALKAN` | Keduanya tetap dapat ditemukan dengan label benar | `[x]` |
| Input gagal | Tanggal terbalik, status tidak dikenal, `branch_id` bisnis lain | Filter ditolak; tidak ada data lintas tenant | `[x]` |
| Hak akses | Admin, developer, owner bisnis lain | Route owner ditolak; ID/detail bisnis lain tidak terungkap | `[x]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=TransactionHistoryReportTest`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk git diff --check`

Expected: seluruh AC US-501 lulus dengan fixture MySQL dan riwayat tidak menampilkan transaksi bisnis lain.

## 7. Out of Scope

1. Agregat pendapatan, tagihan, dashboard, grafik, dan CSV pada TICKET-038–042.
2. Perubahan catatan payment atau aturan pembatalan M2.

## 8. Completion Checklist

- [x] Otorisasi implementasi tercatat dan status diperbarui ke `READY`.
- [x] Kontrak teknis dan matriks penerimaan selesai.
- [x] Perintah verifikasi lulus dan hasil dicatat.

## 9. Hasil verifikasi

Diimplementasikan pada branch `codex/m5-reports`, [PR #2](https://github.com/cleveradit/ceklaundry/pull/2). Matriks AC, kasus batas dan batas bukti ada di [audit M5](../../audits/m5-verification.md). Suite MySQL penuh lulus 126 tes/1095 assertion, Pint 207 file, frontend dan browser M1–M5 lulus. [CI `691737e`](https://github.com/cleveradit/ceklaundry/actions/runs/36869810998) lulus seluruh gate; P95 dashboard 1716 ms pada dataset 200 bisnis/50.000 transaksi. Belum merge/deploy produksi.
