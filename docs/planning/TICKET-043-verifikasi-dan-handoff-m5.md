# Implementation Plan: TICKET-043 (Verifikasi terpadu dan handoff M5)

**Ticket:** `TICKET-043`  
**Status:** `READY`
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-037`–`TICKET-042`  
**Tahap:** M5 — kriteria selesai

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M5](../plan.md#9-m5--laporan-owner), [PRD 7.5/7.10](../initiate-file/prd.md), [US-501–506](../initiate-file/user-stories.md), [audit M4](../audits/m4-verification.md) |
| Keterlacakan | Seluruh AC US-501–506 dan empat kriteria selesai M5; AND-24, ISO-01/02/03/05, SEC-04/05, KIN-03/04, LOK-01/03, UX-03/04 |
| Bukti | Pisahkan uji feature/integrasi MySQL, browser desktop/HP, CSV nyata, query/performa, dan CI remote. Catat batas bukti yang belum diperiksa. |
| Handoff | Fitur M5 baru dinyatakan Live sesudah matriks AC, regresi, dan hasil verifikasi aktual tercatat; M6 tetap pekerjaan terpisah. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-037–043 pada 1 Oktober 2026. |

## 2. Objective

Membuktikan riwayat, pendapatan, tagihan, dashboard, grafik, dan CSV menghasilkan angka yang saling cocok serta aman terhadap perubahan data dan isolasi tenant. Catat bukti dan batas aktual sebelum M5 dinyatakan selesai.

## 3. Non-Negotiable Technical Contract

1. `tests/Feature/Owner/`, `tests/Integration/OwnerReportSnapshotTest.php`, `tests/browser/m5.cjs`: perjalanan dua bisnis/multi-cabang, pembayaran lintas bulan, pembatalan retrospektif, batas WIB, banyak payment/item, formula CSV, dan UI HP.
2. `docs/audits/m5-verification.md`: matriks setiap AC US-501–506 dan empat kriteria selesai M5, perintah/hasil aktual, bukti CI/browser, serta batas yang tersisa.
3. `docs/features/m5-laporan-owner.md`, `docs/features/index.md`: status Live hanya untuk perilaku yang telah diimplementasikan dan diverifikasi.
4. `docs/development.md`, `docs/planning/current-session.md`, `docs/planning/index.md`, `docs/planning/Ticket-Implemented/index.md`: perintah QA nyata, hasil, handoff M6, dan pengarsipan TICKET-037–043 hanya setelah `DONE`.
5. `.github/workflows/ci.yml`: jalankan gate backend MySQL, Pint, frontend lint/typecheck/build, validator dokumentasi, dan browser M5; simpan URL/run CI aktual.

## 4. Scope of Changes

1. Dari QA bersih, buat DP Juli, pelunasan Agustus, lalu pembatalan September; cocokkan ulang laporan lama, grafik, dashboard, dan CSV sesuai definisi masing-masing.
2. Uji transaksi masuk pada batas hari WIB, tujuh hari inklusif, cabang nonaktif, status batal, tagihan positif, serta menumpuk tepat N×24 jam ketika reminder off.
3. Uji payment banyak pada transaksi berisi beberapa item kg/minimum, query count pada fixture kapasitas, dan perubahan payment/cancel/merge saat CSV berjalan.
4. Jalankan regresi M1–M4 yang tersentuh; verifikasi peran owner/admin/developer dan dua tenant pada setiap route laporan/ekspor.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Lintas bulan/batal | DP Juli, lunas Agustus, batal September | Juli/Agustus mengikuti payment; setelah batal keduanya mengecualikan transaksi; tidak ada refund September | `[ ]` |
| Batas/filter | WIB 00.00, tujuh hari, cabang nonaktif, status batal | Riwayat/CSV memakai waktu masuk; pendapatan/grafik memakai waktu payment; cabang/status tepat | `[ ]` |
| Agregat | Banyak item kg dengan minimum dan beberapa payment | Kg aktual, pendapatan, tagihan, bucket tidak berlipat | `[ ]` |
| Snapshot | Payment/cancel/merge beradu baca/export | Tiap respons memakai satu keadaan baca konsisten | `[ ]` |
| Keamanan | Nama formula, admin/developer, owner tenant lain | CSV aman, route owner saja, tidak ada data lintas tenant | `[ ]` |
| Antarmuka | Browser desktop/HP, rentang kosong, bucket nol | Filter, kartu, grafik/tabel, unduhan terbaca dan selaras | `[ ]` |
| Handoff | Suite, CI, audit, feature docs | Setiap klaim bertaut bukti; M6 tidak diklaim selesai | `[ ]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --fail-on-warning`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .`
4. `rtk proxy docker run --rm ceklaundry-frontend npm run lint`
5. `rtk proxy docker run --rm ceklaundry-frontend npm run typecheck`
6. `rtk proxy docker run --rm ceklaundry-frontend npm run build`
7. `rtk proxy python3 docs/audits/validate-final-specs.py`
8. `rtk git diff --check`

Expected: semua gate lulus; browser M5, snapshot MySQL, query/performa, CSV, dan CI dicatat dengan hasil aktual di audit.

## 7. Out of Scope

1. Demo/PWA M6, deploy produksi, dan verifikasi staging yang memerlukan layanan eksternal.
2. Fitur akuntansi, refund ledger, serta ekspor selain CSV riwayat.

## 8. Completion Checklist

- [ ] Otorisasi implementasi tercatat dan status menjadi `READY`.
- [ ] Seluruh AC/kriteria M5 memiliki bukti dan batas di audit.
- [ ] Gate lokal, browser, dan CI remote lulus; URL run dicatat.
- [ ] Dokumentasi fitur/handoff diperbarui dan tiket `DONE` diarsipkan.
