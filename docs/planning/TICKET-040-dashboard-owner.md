# Implementation Plan: TICKET-040 (Dashboard harian owner)

**Ticket:** `TICKET-040`  
**Status:** `REVIEW`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-038`, `TICKET-039`  
**Tahap:** M5 — kartu ringkasan owner

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M5](../plan.md#9-m5--laporan-owner), [PRD FR-O13/7.10](../initiate-file/prd.md), [US-504](../initiate-file/user-stories.md) |
| Keterlacakan | US-504 AC1–2; AND-24, ISO-01/02/05, SEC-05, KIN-03/04, LOK-01/03, UX-03/04 |
| Kartu | Transaksi dan kg aktual nonbatal yang masuk hari ini; pendapatan payment hari ini; jumlah menumpuk; total tagihan berjalan. Semua lintas cabang owner. |
| Menumpuk | `SIAP_DIAMBIL` dengan `waktu_siap_diambil <= now - reminder_first_days × 24 jam`; batas tetap berlaku ketika saklar pengingat mati. |
| Otorisasi | Tiket dibuat atas permintaan pengguna; implementasi masih `REVIEW`. |

## 2. Objective

Halaman `/owner` menjadi ringkasan operasional dan keuangan harian yang dapat dibaca sekali pandang. Semua kartu memakai definisi yang sama dengan laporan pendapatan dan tagihan.

## 3. Non-Negotiable Technical Contract

1. `app/Services/OwnerReportService.php`: tambahkan agregat dashboard dalam satu snapshot baca konsisten; transaksi/kg dari `waktu_masuk` hari WIB dan status nonbatal, pendapatan dari payment, tagihan dari TICKET-039, menumpuk dari status serta waktu siap.
2. `app/Http/Controllers/Owner/DashboardController.php`, `routes/web.php`: ganti closure `/owner` dengan controller `role:owner`; ambil waktu query sekali dan pakai pada semua cutoff.
3. `resources/js/Pages/Dashboard.tsx`: kartu angka besar lintas cabang, label satuan kg/rupiah, tautan ke laporan terperinci, tata letak HP.
4. `tests/Feature/Owner/OwnerDashboardReportTest.php`: uji seluruh kartu, batas WIB dan tepat N×24 jam, saklar off, banyak item/payment, minimum berat, dan dua tenant.

## 4. Scope of Changes

1. Ganti angka placeholder owner saat ini dengan data M5; pertahankan navigasi pengelolaan M1–M4.
2. Hitung kg dari `transaction_items.berat_kg` aktual untuk item `kg`, tanpa minimum tertagih dan tanpa mengalikan akibat payment join.
3. Ukur query count dan performa pada fixture kapasitas sesuai KIN-03/04; jangan menambah cache agregat lintas request.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Hari ini | Transaksi nonbatal 2 kg dengan minimum 3 kg, dua payment, transaksi batal | Jumlah dan kg menghitung transaksi nonbatal sekali; kg 2,0; pendapatan menurut payment sah | `[ ]` |
| Menumpuk | `reminder_first_days=2`, siap tepat 48 jam dan satu detik lebih muda | Hanya yang mencapai 48 jam masuk, meski reminder off | `[ ]` |
| Tagihan | DP Rp30.000 dari Rp74.500 | Kartu tagihan cocok Rp44.500 pada daftar TICKET-039 | `[ ]` |
| Batas tanggal | Aktivitas tepat 00.00 WIB | Masuk kartu hari yang benar | `[ ]` |
| Akses gagal | Admin/developer, owner bisnis lain | Route owner ditolak; data tenant lain tidak memengaruhi kartu | `[ ]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=OwnerDashboardReportTest`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk git diff --check`

Expected: semua kartu sesuai PRD 7.10 dan data laporan, tanpa penggandaan berat/payment.

## 7. Out of Scope

1. Dashboard admin operasional M2 dan statistik developer.
2. Grafik pendapatan TICKET-041.

## 8. Completion Checklist

- [ ] Otorisasi implementasi tercatat dan status menjadi `READY`.
- [ ] AC US-504, kinerja, dan isolasi lulus.
- [ ] Hasil verifikasi dicatat.
