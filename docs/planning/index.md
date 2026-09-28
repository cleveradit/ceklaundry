# CekLaundry — Implementation Plan Index

Urutkan ticket aktif menurut dependensi. Kebutuhan awal ada di [user-stories.md](../initiate-file/user-stories.md); ticket dibuat ketika pekerjaan siap dikerjakan.

## Status Legend

- `DRAFT`: keputusan penting belum lengkap
- `REVIEW`: perlu keputusan atau persetujuan pengguna
- `READY`: pekerjaan dalam lingkup ticket sudah diotorisasi pengguna
- `DONE`: diimplementasikan dan diverifikasi

## Execution Order

TICKET-001–020 (persiapan, M1 dan M2) selesai di [arsip](Ticket-Implemented/index.md). Bukti dan batas implementasi ada di [audit M1](../audits/m1-verification.md) serta [audit M2](../audits/m2-verification.md). Uji printer thermal fisik M2 dilewati atas instruksi pengguna; hasil perangkat tidak diklaim.

Belum ada tiket implementasi aktif. Nomor berikutnya `TICKET-021`; kebutuhan M3–M6 tetap berlaku. Batas uji lanjutan dan integrasi M1/M2 yang perlu diulang tercatat dalam audit terkait.

## Global Agent Rules

1. Mulai dari ticket pertama yang dependensinya sudah terpenuhi.
2. Jangan eksekusi bagian yang masih `DRAFT` atau `REVIEW`; permintaan implementasi yang jelas dari pengguna mengotorisasi lingkup permintaan itu tanpa persetujuan berulang.
3. Baca `Business Decision Snapshot` sebelum mengubah kode.
4. Ikuti `Non-Negotiable Technical Contract`; catat dan selesaikan konflik dengan spesifikasi sebelum menyimpang.
5. Laporkan dependensi yang belum lengkap atau tidak konsisten.
