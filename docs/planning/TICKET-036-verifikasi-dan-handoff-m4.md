# Implementation Plan: TICKET-036 (Verifikasi terpadu dan handoff M4)

**Ticket:** `TICKET-036`  
**Status:** `READY`

**Hasil sementara:** Implementasi dan pengujian lokal/QA dicatat pada [audit M4](../audits/m4-verification.md). CI remote menunggu autentikasi GitHub untuk push branch.

**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-030`–`TICKET-035`  
**Tahap:** M4 — kriteria selesai

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M4](../plan.md#8-m4--loyalti-dan-promo), US-401–407, [audit M3](../audits/m3-verification.md) |
| Keterlacakan | Seluruh AC US-401–407; AND-02/07/08/09/13/15/16/18/19/22/23, ISO-01–06, DAT-01, UX-03/04/05/06 |
| Bukti | Pisahkan uji unit/feature, dua koneksi MySQL nyata, browser/QA, struk render, CI remote dan batas staging; jangan menyebut integrasi provider nyata atau deploy sudah terbukti. |
| Handoff | Audit setiap AC dan kriteria selesai M4; dokumentasi fitur hanya setelah implementasi diverifikasi. M5/M6 tetap pekerjaan terpisah. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-030–036 pada 30 September 2026. |

## 2. Objective

Membuktikan pengaturan, perolehan, penukaran, promo, pembatalan, merge dan penyajian hasil bekerja bersama alur M1–M3. Catat bukti aktual serta batas verifikasi sebelum M4 dinyatakan selesai.

## 3. Non-Negotiable Technical Contract

1. `tests/Feature/Loyalty/`, `tests/Feature/Promo/`, `tests/Integration/`, `tests/browser/m4.cjs`: alur dua bisnis/multi-cabang, seluruh AC M4, race saldo dan perubahan config/quote, regresi payment/cancel/merge serta UI HP.
2. `docs/audits/m4-verification.md`: matriks AC US-401–407 dan lima kriteria selesai plan M4, hasil run nyata, batas browser/thermal/provider/staging dan temuan.
3. `docs/features/m4-loyalti-dan-promo.md`, `docs/features/index.md`: status Live hanya untuk perilaku yang telah diimplementasikan dan diuji.
4. `docs/development.md`, `docs/planning/current-session.md`, `docs/planning/index.md`, `docs/planning/Ticket-Implemented/index.md`: perintah QA, hasil, handoff M5 dan arsip ticket DONE.
5. `.github/workflows/ci.yml`: gate MySQL concurrency, Pint, frontend lint/typecheck/build, validator dokumentasi dan browser M4; catat URL/run CI aktual.

## 4. Scope of Changes

1. Dari QA bersih, jalankan owner aktifkan stempel→customer mendapat +1 dari LUNAS→tukar hadiah→pakai promo→bayar→batal→lihat ledger/status/struk.
2. Paksa interleaving MySQL untuk dua penukaran, payment vs cancel, config/promo/master vs save, merge vs create/payment/redemption/cancel.
3. Uji N berubah/program off/on, saldo negatif sah, transaksi historis sebelum M4, Rp0, minimum berat, overflow/half-up, quote 409, dua tenant dan cabang berbeda.
4. Jalankan regresi M1–M3 yang tersentuh dan catat perbedaan bukti simulasi provider dari verifikasi eksternal.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Perjalanan | +1 saat lunas, hadiah 2 kg dengan minimum 3 kg, promo 10%, pembatalan | Harga/ledger/cache/snapshot konsisten di panel, publik, resi | [audit M4](../audits/m4-verification.md) |
| Race | Dua redemption saldo 10, payment/cancel/merge bersamaan | Satu redemption berhasil, tidak ada double delta atau data lintas tenant | [audit M4](../audits/m4-verification.md) |
| Pengaturan | N10→N5, off→on, master/promo berubah, transaksi lama | Tidak retroaktif, refund delta asal, snapshot lama tetap | [audit M4](../audits/m4-verification.md) |
| Batas/gagal | Saldo negatif, promo minimum, total0, quote stale, diskon overflow, cabang asing | Penolakan atomik atau nilai bertanda sesuai kontrak | [audit M4](../audits/m4-verification.md) |
| Permukaan | HP admin/owner, no-JS publik, struk 58 mm, email/WA fake | Tampilan dan nominal konsisten; hak cabang/privasi terjaga | [audit M4](../audits/m4-verification.md) |
| Handoff | CI, audit, feature docs, staging gap M3/M4 | Setiap klaim bertaut ke bukti; M5/M6 tidak diklaim selesai | [audit M4](../audits/m4-verification.md) |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --fail-on-warning`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .`
4. `rtk proxy docker run --rm ceklaundry-frontend npm run lint`
5. `rtk proxy docker run --rm ceklaundry-frontend npm run typecheck`
6. `rtk proxy docker run --rm ceklaundry-frontend npm run build`
7. `rtk proxy python3 docs/audits/validate-final-specs.py`
8. `rtk git diff --check`

Expected: semua gate lulus; uji browser M4 dan race MySQL dicatat dengan hasil aktual dalam audit.

## 7. Out of Scope

1. Laporan/CSV M5, demo/PWA M6, dan deploy produksi.
2. Verifikasi perangkat printer fisik serta delivery provider nyata tanpa lingkungan/perangkat terkait.

## 8. Completion Checklist

- [x] Implementasi, pengujian lokal/QA, serta batas bukti dicatat pada [audit M4](../audits/m4-verification.md).
- [ ] CI remote pada revisi akhir lulus atau keputusan eksplisit untuk melewati gate dicatat.
