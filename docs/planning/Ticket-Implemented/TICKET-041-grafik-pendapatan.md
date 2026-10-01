# Implementation Plan: TICKET-041 (Grafik pendapatan harian dan bulanan)

**Ticket:** `TICKET-041`
**Status:** `DONE`
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-038`
**Tahap:** M5 — grafik pendapatan

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M5](../../plan.md#9-m5--laporan-owner), [PRD FR-O15/7.10](../../initiate-file/prd.md), [US-505](../../initiate-file/user-stories.md) |
| Keterlacakan | US-505 AC1–2; AND-24, ISO-01/02/05, SEC-05, KIN-04, LOK-01/03, UX-03/04 |
| Sumber angka | Payment nonbatal dari TICKET-038; bucket harian/bulanan WIB termasuk bucket nol; total bucket sama dengan total laporan untuk filter yang sama. |
| Konsistensi | Grafik dan total pada halaman yang sama dibaca dari satu snapshot `REPEATABLE READ`, tanpa cache agregat lintas request. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-037–043 pada 1 Oktober 2026. |

## 2. Objective

Owner dapat membaca pola pendapatan per hari atau bulan tanpa selisih dari angka laporan. Periode kosong tetap terlihat sebagai nilai nol.

## 3. Non-Negotiable Technical Contract

1. `app/Services/OwnerReportService.php`: agregasi bucket dari definisi pendapatan TICKET-038; buat deret hari/bulan WIB lengkap dari rentang yang telah divalidasi, termasuk nol.
2. `app/Http/Controllers/Owner/ReportController.php`: `GET /owner/reports/revenue` menyajikan bucket dan total dalam snapshot baca yang sama; cabang tetap terscope owner.
3. `resources/js/Components/Owner/RevenueChart.tsx`, `resources/js/Pages/Owner/RevenueReport.tsx`: grafik responsif dengan label tanggal/nilai yang dapat dibaca dan ringkasan tekstual/tabel agar angka tidak hanya tersedia secara visual.
4. `tests/Feature/Owner/RevenueChartTest.php`: bucket nol, lintas bulan/tahun, batas WIB, banyak payment, pembatalan retrospektif, serta kesamaan SUM(bucket)=total laporan.

## 4. Scope of Changes

1. Tambahkan pilihan granularity hari/bulan pada rentang laporan yang sama.
2. Gunakan agregasi berdasarkan waktu payment, bukan waktu transaksi atau tanggal pembatalan.
3. Pertahankan bucket nol dan urutan kronologis meski tidak ada payment pada rentang.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Harian | Payment hari pertama dan ketiga, hari kedua kosong | Tiga bucket, tengah Rp0, jumlah sama dengan total laporan | `[x]` |
| Bulanan | DP Juli, pelunasan Agustus | Bucket Juli Rp30.000, Agustus Rp44.500 | `[x]` |
| Retrospektif | Transaksi tersebut dibatalkan September | Bucket Juli/Agustus menjadi Rp0 saat dibuka ulang | `[x]` |
| Batas | Rentang melewati tahun baru dan payment tepat 00.00 WIB | Bucket tanggal/bulan tepat tanpa satu hari hilang | `[x]` |
| Gagal/isolasi | Granularity tidak dikenal atau cabang bisnis lain | Ditolak; tidak ada bucket tenant lain | `[x]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=RevenueChartTest`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .`
4. `rtk proxy docker run --rm ceklaundry-frontend npm run typecheck`
5. `rtk git diff --check`

Expected: bucket dan total selaras, tampilan dapat dibaca di HP, typecheck lulus.

## 7. Out of Scope

1. Prediksi pendapatan dan cache statistik materialisasi.
2. Grafik status operasional atau performa karyawan.

## 8. Completion Checklist

- [x] Otorisasi implementasi tercatat dan status menjadi `READY`.
- [x] AC US-505, kesamaan total, dan aksesibilitas diperiksa.
- [x] Hasil verifikasi dicatat.

## 9. Hasil verifikasi

Diimplementasikan pada branch `codex/m5-reports`, [PR #2](https://github.com/cleveradit/ceklaundry/pull/2). Matriks AC, kasus batas dan batas bukti ada di [audit M5](../../audits/m5-verification.md). Suite MySQL penuh lulus 126 tes/1095 assertion, Pint 207 file, frontend dan browser M1–M5 lulus. [CI `691737e`](https://github.com/cleveradit/ceklaundry/actions/runs/36869810998) lulus seluruh gate; P95 dashboard 1716 ms pada dataset 200 bisnis/50.000 transaksi. Belum merge/deploy produksi.
