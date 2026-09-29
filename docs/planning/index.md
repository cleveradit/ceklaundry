# CekLaundry — Implementation Plan Index

Urutkan ticket aktif menurut dependensi. Kebutuhan awal ada di [user-stories.md](../initiate-file/user-stories.md); ticket dibuat ketika pekerjaan siap dikerjakan.

## Status Legend

- `DRAFT`: keputusan penting belum lengkap
- `REVIEW`: perlu keputusan atau persetujuan pengguna
- `READY`: pekerjaan dalam lingkup ticket sudah diotorisasi pengguna
- `DONE`: diimplementasikan dan diverifikasi

## Execution Order

TICKET-001–020 (persiapan, M1 dan M2) selesai di [arsip](Ticket-Implemented/index.md). Bukti dan batas implementasi ada di [audit M1](../audits/m1-verification.md) serta [audit M2](../audits/m2-verification.md). Uji printer thermal fisik M2 dilewati atas instruksi pengguna; hasil perangkat tidak diklaim.

Tiket M3 berikut disusun dari [plan M3](../plan.md#7-m3--notifikasi) dan spesifikasi final. Pengguna telah mengotorisasi implementasi seluruh TICKET-021–029; semuanya berstatus `READY` selama implementasi dan verifikasi berlangsung. Nomor berikutnya setelah M3 `TICKET-030`.

| Urutan | Ticket | Hasil | Dependensi |
|---|---|---|---|
| 1 | [TICKET-021 — Konfigurasi notifikasi](TICKET-021-konfigurasi-notifikasi.md) | Pengaturan teknis developer, perilaku owner, credential aman | TICKET-020 |
| 2 | [TICKET-022 — Mesin kiriman dan restore hold](TICKET-022-mesin-kiriman-dan-restore-hold.md) | Log+job atomik, worker/recovery, guard semua outbound | TICKET-021 |
| 3 | [TICKET-023 — Verifikasi email publik](TICKET-023-verifikasi-email-publik.md) | Email transaksi tervalidasi tanpa mengubah master | TICKET-022 |
| 4 | [TICKET-024 — Email siap diambil](TICKET-024-email-siap-diambil.md) | Email ready idempoten dan tidak menghalangi status | TICKET-022–023 |
| 5 | [TICKET-025 — Pengingat otomatis](TICKET-025-pengingat-otomatis.md) | Scheduler/cursor email; WA aktif setelah TICKET-027 | TICKET-021–022, 024 |
| 6 | [TICKET-026 — Pengamanan penerima WA](TICKET-026-perubahan-penerima-wa.md) | Edit/merge recipient dan token stale aman | TICKET-022, 025 |
| 7 | [TICKET-027 — WA otomatis dan kuota](TICKET-027-wa-otomatis-dan-kuota.md) | Adapter, saklar event dan slot bulanan | TICKET-021–022, 024–026 |
| 8 | [TICKET-028 — Kiriman manual dan log](TICKET-028-kiriman-manual-dan-log.md) | Email/wa.me manual, batas dan riwayat scoped | TICKET-022, 024–025, 027 |
| 9 | [TICKET-029 — Verifikasi dan handoff M3](TICKET-029-verifikasi-dan-handoff-m3.md) | Audit AC, CI/browser, fault/restore dan regresi M2 | TICKET-021–028 |

Batas uji lanjutan dan integrasi M1/M2 yang perlu diulang tercatat dalam audit terkait. Kebutuhan M4–M6 tetap berlaku dan belum dibuat tiket implementasinya.

## Global Agent Rules

1. Mulai dari ticket pertama yang dependensinya sudah terpenuhi.
2. Jangan eksekusi bagian yang masih `DRAFT` atau `REVIEW`; permintaan implementasi yang jelas dari pengguna mengotorisasi lingkup permintaan itu tanpa persetujuan berulang.
3. Baca `Business Decision Snapshot` sebelum mengubah kode.
4. Ikuti `Non-Negotiable Technical Contract`; catat dan selesaikan konflik dengan spesifikasi sebelum menyimpang.
5. Laporkan dependensi yang belum lengkap atau tidak konsisten.
