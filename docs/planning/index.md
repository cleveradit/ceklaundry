# CekLaundry — Implementation Plan Index

Urutkan ticket aktif menurut dependensi. Kebutuhan awal ada di [user-stories.md](../initiate-file/user-stories.md); ticket dibuat ketika pekerjaan siap dikerjakan.

## Status Legend

- `DRAFT`: keputusan penting belum lengkap
- `REVIEW`: perlu keputusan atau persetujuan pengguna
- `READY`: pekerjaan dalam lingkup ticket sudah diotorisasi pengguna
- `DONE`: diimplementasikan dan diverifikasi

## Execution Order

TICKET-001–010 (persiapan dan M1) selesai di [arsip](Ticket-Implemented/index.md); bukti aktual ada di [audit M1](../audits/m1-verification.md). TICKET-011–019 diimplementasikan dan diverifikasi secara lokal serta pada [CI remote](https://github.com/cleveradit/ceklaundry/actions/runs/36462815134), dengan rincian di [audit M2](../audits/m2-verification.md). TICKET-020 menunggu bukti printer thermal fisik; kode, gate lokal/remote, browser, serta handoff M2 sudah tersedia. Tiket M2 belum diarsipkan sampai seluruh TICKET-011–020 DONE.

| Urutan | Tiket | Status | Dependensi |
|---|---|---|---|
| 1 | [TICKET-011 — Harga dan penawaran server](TICKET-011-harga-dan-penawaran.md) | DONE | TICKET-010 |
| 2 | [TICKET-012 — Direktori pelanggan](TICKET-012-direktori-pelanggan.md) | DONE | TICKET-010; independen dari TICKET-011 |
| 3 | [TICKET-013 — Pembuatan transaksi](TICKET-013-pembuatan-transaksi.md) | DONE | TICKET-011, 012 |
| 4 | [TICKET-014 — Pembayaran dan DP](TICKET-014-pembayaran-dan-dp.md) | DONE | TICKET-013 |
| 5 | [TICKET-015 — Status, edit, pembatalan](TICKET-015-status-edit-dan-pembatalan.md) | DONE | TICKET-014 |
| 6 | [TICKET-016 — Penggabungan pelanggan](TICKET-016-penggabungan-pelanggan.md) | DONE | TICKET-012, 015 |
| 7 | [TICKET-017 — Dashboard, pencarian, owner](TICKET-017-dashboard-pencarian-dan-owner.md) | DONE | TICKET-015, 016 |
| 8 | [TICKET-018 — Cek resi publik](TICKET-018-cek-resi-publik.md) | DONE | TICKET-015; dapat dikerjakan sebelum TICKET-016/017 |
| 9 | [TICKET-019 — Cetak dan WA manual](TICKET-019-resi-cetak-dan-wa-manual.md) | DONE | TICKET-017, 018 |
| 10 | [TICKET-020 — Verifikasi/handoff M2](TICKET-020-verifikasi-dan-handoff-m2.md) | READY | TICKET-011–019 |

Nomor berikutnya `TICKET-021`. Kebutuhan M3–M6 tetap berlaku. Batas uji M3/M4/M5/M6 dan integrasi M1 yang perlu diulang tercatat di tiket terkait.

## Global Agent Rules

1. Mulai dari ticket pertama yang dependensinya sudah terpenuhi.
2. Jangan eksekusi bagian yang masih `DRAFT` atau `REVIEW`; permintaan implementasi yang jelas dari pengguna mengotorisasi lingkup permintaan itu tanpa persetujuan berulang.
3. Baca `Business Decision Snapshot` sebelum mengubah kode.
4. Ikuti `Non-Negotiable Technical Contract`; catat dan selesaikan konflik dengan spesifikasi sebelum menyimpang.
5. Laporkan dependensi yang belum lengkap atau tidak konsisten.
