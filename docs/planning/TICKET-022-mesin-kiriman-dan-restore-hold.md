# Implementation Plan: TICKET-022 (Mesin kiriman, recovery, dan restore hold)

**Ticket:** `TICKET-022`

**Status:** `READY`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-021`

**Tahap:** M3 — keandalan outbound

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | US-308 AC2–4/6–13, PRD 9.2–9.2.1, arsitektur 6.1–6.2/9, skema notification_logs 2.16 |
| Keterlacakan | US-101 AC9, US-301 AC5, US-307 AC10, US-308 AC2–4/6–13; AND-01/03–05/10/21/27, ISO-05, SEC-03 |
| Atomisitas | Domain state, log unik non-null dan job database queue disimpan pada satu koneksi/transaksi MySQL sebelum commit; network call selalu di luar lock. |
| Outcome | Accepted→berhasil; definite rejection retry 60/300 detik maksimal tiga panggilan; unknown/crash setelah marker→perlu_pemeriksaan tanpa retry otomatis. |
| Restore | `OUTBOUND_RESTORE_HOLD` dan `outbound_resume_at` berada di konfigurasi deployment luar backup DB; hold fail closed mencakup SMTP/WA, manual, verifikasi, dan keamanan akun. Tidak ada klaim deduplikasi efek eksternal yang hilang saat restore. |
| Otorisasi | Penyusunan tiket diminta pengguna; implementasi M3 belum diminta. |

## 2. Objective

Seluruh kanal memakai satu mesin reservasi dan pengiriman yang dapat dipulihkan setelah worker/job hilang. Operasi laundry tetap dapat commit ketika provider gagal atau outbound ditahan saat restore.

## 3. Non-Negotiable Technical Contract

1. `app/Services/NotificationDispatcher.php`, `app/Models/NotificationLog.php`, `app/Jobs/SendNotification.php`: key `tx:{id}:ready:{channel}:0`, `tx:{id}:reminder:{channel}:{n}`, `tx:{id}:verify:email:{version}`, dan manual UUID; claim lease 300 detik, token CAS, marker sebelum network, max attempt 3.
2. `app/Services/NotificationRecoveryService.php`, `routes/console.php`: scanner tiap menit untuk job due hilang >5 menit, lease expired, dan marker unknown; pekerjaan eligible kembali diantre ≤6 menit pada scheduler sehat.
3. `app/Services/OutboundGuard.php`, `app/Jobs/SendPasswordReset.php`, `config/outbound.php`: guard sebelum reservasi, marker, transport dan finalisasi; hold/cutoff memakai `created_at` log, waktu siap untuk ready/reminder dan waktu request auth; konfigurasi invalid fail closed.
4. `docs/development.md`: runbook restore: fence proses lama, aktifkan hold di luar DB backup, rekonsiliasi/review, buang job dan token lama, cutover seragam, simpan cutoff lintas restart.
5. `tests/Integration/NotificationRecoveryTest.php`, `tests/Feature/Notification/RestoreHoldTest.php`: dua proses MySQL, fault injection, marker/callback/token stale, restore cutoff.

## 4. Scope of Changes

1. Pakai tabel `notification_logs` dan `jobs` existing; pastikan queue database memakai koneksi/PDO transaksi yang sama dan `after_commit=false` untuk reservasi. Job membawa ID, metadata `business_id` top-level untuk operasi M6.
2. Terapkan tenant context worker dengan cleanup `finally`, payload/provider snapshot nonrahasia dan error sanitized. Tidak ada automatic fallback antarprovider setelah unknown.
3. Selama hold, status/payment tetap commit tanpa backlog outbound baru; log nonterminal asal backup masuk review dengan token dicabut dan slot WA existing tetap. Cutoff `>` konservatif termasuk timestamp yang sama.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Atomic commit | Fault sebelum/ setelah insert job, lalu rollback/commit | Log+job keduanya batal atau keduanya committed | `[ ]` |
| Worker ganda | Dua job/key dan dua proses claim | Hanya satu token sah dan satu provider call | `[ ]` |
| Crash | Mati sebelum marker, sesudah marker, sesudah accepted sebelum finalize | Sebelum marker reclaim; sesudah marker review tanpa send ulang | `[ ]` |
| Provider | Definitely rejected 3×; SMTP disconnect sesudah DATA | Backoff 60/300 lalu gagal; disconnect unknown, bukan sukses/retry | `[ ]` |
| Restore | Backup sebelum send, restore, hold, cutoff dan restart | Nol call/cursor/slot baru selama hold; token lama tak finalize; event historis tidak replay | `[ ]` |
| Isolasi | Job bisnis A lalu B pada worker sama, payload error | Transport/context tidak terbawa; error aman tanpa PII/credential | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --filter=NotificationRecoveryTest`

`rtk proxy docker compose exec -T app php artisan test --filter=RestoreHoldTest`

`rtk proxy python3 docs/audits/validate-final-specs.py`

`rtk git diff --check`

Uji crash/race memakai minimal dua proses/koneksi MySQL QA tanpa enclosing transaction tunggal; catat hasil fault injection dan runbook dry run.

## 7. Out of Scope

1. Implementasi konten ready, reminder, verifikasi dan adapter WA pada tiket berikutnya.
2. Klaim exactly-once eksternal atau penggantian backup/deploy produksi.

## 8. Completion Checklist

- [ ] Status READY setelah pengguna meminta implementasi.
- [ ] Fault, recovery, restore, cutoff, auth guard dan sanitasi lulus.
- [ ] Tidak ada send provider dari dalam transaksi domain.
