# Implementation Plan: TICKET-029 (Verifikasi terpadu dan handoff M3)

**Ticket:** `TICKET-029`

**Status:** `READY`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-021`–`TICKET-028`

**Tahap:** M3 — kriteria selesai

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M3](../plan.md#7-m3--notifikasi), US-301–308, [audit M2](../audits/m2-verification.md), audit final |
| Keterlacakan | Seluruh AC US-301–308; handoff US-202 AC4–5, US-208 AC4, US-210 AC2, US-213 AC5–9, US-214 AC6–9; AND-03–05/10/11/20/21/25/27/28, ISO-01–06, SEC-03/06/09 |
| Bukti | Pisahkan contract test provider/mock, MySQL race, fault/restore dry run, browser, dan pengiriman nyata; jangan klaim acceptance penyedia eksternal tanpa bukti. |
| Lingkungan | MySQL8.4 QA terpisah; backend suite dan browser seed berurutan; credential uji tidak masuk repo/log. |
| Otorisasi | Penyusunan tiket diminta pengguna; implementasi M3 belum diminta. |

## 2. Objective

Membuktikan email, pengingat, WA opsional, log dan restore hold bekerja bersama alur M1/M2. Audit menandai setiap AC sebagai teruji, tertunda, atau gagal beserta batas bukti aktual sebelum M3 disebut selesai.

## 3. Non-Negotiable Technical Contract

1. `tests/Feature/Notification/`, `tests/Integration/`, `tests/browser/m3.cjs`: perjalanan dua bisnis/multi-cabang, konfigurasi, verifikasi email no-JS, ready/reminder, manual, kuota, recipient edit/merge, lifecycle dan restore.
2. `docs/audits/m3-verification.md`: matriks tiap AC US-301–308, bukti command/run, regresi M1/M2 terkait, batas provider/restore, dan handoff M4/M6.
3. `docs/features/m3-notifikasi.md`, `docs/features/index.md`: status Live hanya setelah implementasi dan verifikasi; dokumentasikan perilaku yang benar-benar ada.
4. `docs/development.md`, `docs/planning/current-session.md`, `docs/planning/index.md`, `docs/planning/Ticket-Implemented/index.md`: perintah nyata, runbook, hasil dan arsip tiket DONE.
5. `.github/workflows/ci.yml`: gate MySQL concurrency/fault, Pint, ESLint, TypeScript, Vite dan browser M3; catat run CI aktual.

## 4. Scope of Changes

1. Dari QA kosong, jalankan verifikasi email publik→ready email/WA→pengingat→manual; cek transaksi/payment tetap sah ketika provider gagal.
2. Uji dua koneksi/proses MySQL untuk log+job, worker ganda, lease/marker, limit 99/100, scheduler overlap, edit/merge recipient, off→on dan expired→aktif.
3. Simulasikan restore hold dengan send accepted yang hilang dari backup, cutoff, restart, auth/verification/manual guard; uji data publik/private dan log sanitized.
4. Jalankan regresi audit M2 yang ditandai tertunda untuk M3, dan verifikasi pemisahan kanal email/WA lintas tenant.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Perjalanan | Dua bisnis, email publik sah, siap, H+N reminder, manual | Log/isi/recipient/cursor benar, tenant/cabang terisolasi | `[ ]` |
| Failure | SMTP disconnect, WA 200 body error, worker mati di titik kritis | Status transaksi commit; outcome konservatif, recovery tanpa resend unknown | `[ ]` |
| Race | Kuota 99/100, worker ganda, nomor edit/merge, scheduler overlap | Satu key/slot, tidak ada kiriman ke recipient lama sesudah mutasi committed | `[ ]` |
| Restore | Backup sebelum accepted, restore+hold, cutoff dan restart | Nol call selama hold; event historis tidak replay; risiko window RPO dicatat | `[ ]` |
| Privasi | Publik GET/POST, owner/admin/developer, log/failed_jobs | Masking, policy, limit, secret sanitasi dan bahasa Indonesia benar | `[ ]` |
| Handoff | CI/browser/provider contract, integrasi demo M6 belum ada | Bukti aktual dipisah dari integrasi tertunda; tidak klaim deploy/real delivery | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --fail-on-warning`

`rtk proxy docker compose exec -T app ./vendor/bin/pint --test`

`rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .`

`rtk proxy docker run --rm ceklaundry-frontend npm run lint`

`rtk proxy docker run --rm ceklaundry-frontend npm run typecheck`

`rtk proxy docker run --rm ceklaundry-frontend npm run build`

`rtk proxy python3 docs/audits/validate-final-specs.py`

`rtk git diff --check`

Browser QA dan fault/restore drill mengikuti `docs/development.md`; catat hasil CI remote, provider contract dan keterbatasan tanpa credential nyata.

## 7. Out of Scope

1. Demo/PWA M6 dan kiriman nyata dari tenant demo; M6 menguji preview end to end.
2. Deploy produksi dan klaim keberhasilan provider di luar bukti uji.

## 8. Completion Checklist

- [ ] Status READY setelah pengguna meminta implementasi seluruh lingkup.
- [ ] Seluruh AC dan batas bukti tercatat pada audit M3.
- [ ] CI, browser, MySQL race, fault/restore dan regresi relevan lulus.
- [ ] Dokumen fitur serta handoff diperbarui; tiket DONE diarsipkan.
