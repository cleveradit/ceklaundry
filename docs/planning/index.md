# CekLaundry — Implementation Plan Index

Urutkan ticket aktif menurut dependensi. Kebutuhan awal ada di [user-stories.md](../initiate-file/user-stories.md); ticket dibuat ketika pekerjaan siap dikerjakan.

## Status Legend

- `DRAFT`: keputusan penting belum lengkap
- `REVIEW`: perlu keputusan atau persetujuan pengguna
- `READY`: pekerjaan dalam lingkup ticket sudah diotorisasi pengguna
- `DONE`: diimplementasikan dan diverifikasi

## Execution Order

TICKET-001–029 (persiapan, M1–M3) selesai di [arsip](Ticket-Implemented/index.md). Bukti dan batas implementasi ada di [audit M1](../audits/m1-verification.md), [audit M2](../audits/m2-verification.md), dan [audit M3](../audits/m3-verification.md). Uji printer thermal fisik M2 dilewati atas instruksi pengguna; provider notifikasi nyata dan restore backup fisik M3 memerlukan verifikasi staging.

M4 — loyalti dan promo diimplementasikan setelah otorisasi pengguna pada 30 September 2026. TICKET-030–036 selesai dan dicatat pada [arsip](Ticket-Implemented/index.md); bukti dan batasnya ada di [audit M4](../audits/m4-verification.md). [CI run #24](https://github.com/cleveradit/ceklaundry/actions/runs/36725111623) lulus pada commit kode M4 di `main`.

TICKET-037–043 mengimplementasikan seluruh M5 berdasarkan [plan M5](../plan.md#9-m5--laporan-owner), diotorisasi pengguna pada 1 Oktober 2026. Seluruhnya DONE dan dicatat pada [arsip](Ticket-Implemented/index.md). [Audit M5](../audits/m5-verification.md) memuat AC, snapshot, browser dan kapasitas/performa. [CI commit kode akhir `691737e`](https://github.com/cleveradit/ceklaundry/actions/runs/36869810998) lulus pada branch `codex/m5-reports` ([PR #2](https://github.com/cleveradit/ceklaundry/pull/2)); kode sudah di `main`, belum deploy produksi.

Tidak ada ticket implementasi aktif. Ticket berikutnya adalah `TICKET-044` untuk M6; M6 belum dibuat tiket implementasinya.

## Global Agent Rules

1. Mulai dari ticket pertama yang dependensinya sudah terpenuhi.
2. Jangan eksekusi bagian yang masih `DRAFT` atau `REVIEW`; permintaan implementasi yang jelas dari pengguna mengotorisasi lingkup permintaan itu tanpa persetujuan berulang.
3. Baca `Business Decision Snapshot` sebelum mengubah kode.
4. Ikuti `Non-Negotiable Technical Contract`; catat dan selesaikan konflik dengan spesifikasi sebelum menyimpang.
5. Laporkan dependensi yang belum lengkap atau tidak konsisten.
