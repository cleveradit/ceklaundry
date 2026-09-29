# Implementation Plan: TICKET-026 (Pengamanan penerima WA saat edit dan merge)

**Ticket:** `TICKET-026`

**Status:** `READY`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-022`, `TICKET-025`

**Tahap:** M3 — integrasi identitas pelanggan

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | US-213 AC5–9, US-214 AC6–9, US-307 AC9, PRD 9.2, arsitektur 6.2.1 |
| Keterlacakan | US-213 AC5–9, US-214 AC6–9, US-307 AC9, US-308 AC7–9; AND-01/08/15/21/28, ISO-02/05 |
| Belum attempted | Hanya `delivery_started_at IS NULL AND attempt_count=0`; nomor baru meretarget log WA API nonterminal dengan key, nomor pengingat dan slot sama. |
| Pernah attempted | Marker ada atau attempt_count>0 dan nomor berbeda → `perlu_pemeriksaan/recipient_berubah`, token dicabut, snapshot/bucket tetap; tidak retry/retarget. |
| Batas akses | Edit shared customer menyesuaikan log seluruh cabang internal tanpa membuka data cabang lain ke admin; policy merge seluruh histori tetap berlaku. |
| Otorisasi | Penyusunan tiket diminta pengguna; implementasi M3 belum diminta. |

## 2. Objective

Perubahan nomor dan merge tidak membuat pekerjaan WA tertunda menghubungi nomor lama yang dapat dipakai orang lain. Pekerjaan yang sudah mungkin keluar dipertahankan sebagai hasil tak pasti untuk pemeriksaan.

## 3. Non-Negotiable Technical Contract

1. `app/Services/WaRecipientReconciler.php`: protokol root→customers→transactions→notification_logs ID menaik; banding nomor normalized terkini dengan `notification_logs.tujuan` hanya untuk kanal `whatsapp`.
2. `app/Services/CustomerService.php`, `app/Services/CustomerMergeService.php`: panggil reconciler dalam transaksi edit/merge yang sama; merge memindah FK ke target, menyesuaikan source log, lalu menghapus source terakhir.
3. `app/Services/NotificationDispatcher.php`, `app/Services/NotificationRecoveryService.php`: preflight, retry, callback dan recovery memeriksa recipient terkini; worker stale gagal CAS setelah token dicabut.
4. `tests/Integration/WaRecipientConcurrencyTest.php`, `tests/Feature/Notification/WaRecipientReconciliationTest.php`: A→B, A dipakai customer lain, merge, callback terlambat, rollback dan batas cabang.

## 4. Scope of Changes

1. Never-attempted yang tetap eligible diubah tujuan ke B, claim lama dicabut dan key sama diantre lagi. Jika tak eligible, terminalkan dengan alasan yang ada.
2. Pernah attempted mempertahankan tujuan/payload/provider/attempt/bucket; callback accepted atau rejected lama tidak boleh menimpa review atau melepas slot.
3. Email snapshot dan histori `whatsapp_manual` tidak diretarget. Log terminal tidak dihidupkan kembali oleh A→B→A.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Edit sebelum attempt | WA pending A, edit ke B, A dipakai pelanggan baru | Satu key/slot, hanya B eligible, worker lama gagal CAS | `[ ]` |
| Merge | Source A pending, target B, source dihapus | FK/log mengikuti target; tujuan B sebelum source hilang | `[ ]` |
| Setelah attempt | Marker ada atau retry marker null tetapi count>0, edit B | Review recipient_berubah, bucket/token sesuai kontrak, nol retry | `[ ]` |
| Callback/race | Accepted terlambat, edit vs marker, A→B→A | Hanya hasil sesuai urutan commit; review tidak revive/ditimpa | `[ ]` |
| Isolasi/fault | Shared customer lintas cabang, admin satu cabang; fault tengah merge | Internal seluruh log tersesuaikan tanpa bocor; rollback seluruh unit | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --filter=WaRecipientReconciliationTest`

`rtk proxy docker compose exec -T app php artisan test --filter=WaRecipientConcurrencyTest`

`rtk git diff --check`

## 7. Out of Scope

1. Provider WA sungguhan dan aktivasi otomatis; TICKET-027.
2. Retarget email transaksi dan perubahan master lewat publik.

## 8. Completion Checklist

- [ ] Status READY setelah pengguna meminta implementasi.
- [ ] Race, fault rollback dan cabang lulus dengan MySQL nyata.
- [ ] Key/slot tidak digandakan atau dibuka ulang.
