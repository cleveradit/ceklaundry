# CekLaundry — Implementation Plan Index

Urutkan ticket aktif menurut dependensi. Kebutuhan awal ada di [user-stories.md](../initiate-file/user-stories.md); ticket dibuat ketika pekerjaan siap dikerjakan.

## Status Legend

- `DRAFT`: keputusan penting belum lengkap
- `REVIEW`: perlu keputusan atau persetujuan pengguna
- `READY`: pekerjaan dalam lingkup ticket sudah diotorisasi pengguna
- `DONE`: diimplementasikan dan diverifikasi

## Execution Order

TICKET-001–029 (persiapan, M1–M3) selesai di [arsip](Ticket-Implemented/index.md). Bukti dan batas implementasi ada di [audit M1](../audits/m1-verification.md), [audit M2](../audits/m2-verification.md), dan [audit M3](../audits/m3-verification.md). Uji printer thermal fisik M2 dilewati atas instruksi pengguna; provider notifikasi nyata dan restore backup fisik M3 memerlukan verifikasi staging.

M4 — loyalti dan promo direncanakan dalam urutan berikut. Semua tiket berstatus `REVIEW`: keputusan bisnis telah diisi dari spesifikasi, sedangkan permintaan saat ini hanya untuk menyusun tiket, belum untuk mengimplementasikannya. Ticket berikutnya setelah M4 adalah `TICKET-037`. Kebutuhan M5–M6 belum dibuat tiket implementasinya.

| Urutan | Ticket | Fokus | Depends On | Status |
|---|---|---|---|---|
| 1 | [TICKET-030](TICKET-030-pengaturan-loyalti.md) | Pengaturan program stempel dan guard master hadiah | 029 | `REVIEW` |
| 2 | [TICKET-031](TICKET-031-kelola-promo.md) | Promo owner, periode dan cakupan cabang | 030 | `REVIEW` |
| 3 | [TICKET-032](TICKET-032-ledger-dan-perolehan-stempel.md) | Ledger, earning pertama saat LUNAS dan kompensasi | 030 | `REVIEW` |
| 4 | [TICKET-033](TICKET-033-penukaran-stempel.md) | Penukaran, harga hadiah, snapshot dan race saldo | 030, 032 | `REVIEW` |
| 5 | [TICKET-034](TICKET-034-promo-pada-transaksi.md) | Promo setelah hadiah dalam quote/create/edit | 031, 033 | `REVIEW` |
| 6 | [TICKET-035](TICKET-035-tampilan-loyalti-dan-promo.md) | Ledger panel, status publik, resi dan notifikasi | 032–034 | `REVIEW` |
| 7 | [TICKET-036](TICKET-036-verifikasi-dan-handoff-m4.md) | Integrasi, regresi, audit dan handoff M4 | 030–035 | `REVIEW` |

## Global Agent Rules

1. Mulai dari ticket pertama yang dependensinya sudah terpenuhi.
2. Jangan eksekusi bagian yang masih `DRAFT` atau `REVIEW`; permintaan implementasi yang jelas dari pengguna mengotorisasi lingkup permintaan itu tanpa persetujuan berulang.
3. Baca `Business Decision Snapshot` sebelum mengubah kode.
4. Ikuti `Non-Negotiable Technical Contract`; catat dan selesaikan konflik dengan spesifikasi sebelum menyimpang.
5. Laporkan dependensi yang belum lengkap atau tidak konsisten.
