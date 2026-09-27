# CekLaundry — Implementation Plan Index

Urutkan ticket aktif menurut dependensi. Kebutuhan awal ada di [user-stories.md](../initiate-file/user-stories.md); ticket dibuat ketika pekerjaan siap dikerjakan.

## Status Legend

- `DRAFT`: keputusan penting belum lengkap
- `REVIEW`: perlu keputusan atau persetujuan pengguna
- `READY`: pekerjaan dalam lingkup ticket sudah diotorisasi pengguna
- `DONE`: diimplementasikan dan diverifikasi

## Execution Order

Tidak ada tiket aktif. TICKET-001–010 (urutan0 persiapan dan urutan1/M1) selesai serta dipindahkan ke [Ticket-Implemented](Ticket-Implemented/index.md). Bukti aktual ada di [audit M1](../audits/m1-verification.md).

Nomor berikutnya `TICKET-011`. Langkah berikutnya adalah merencanakan M2 dari [plan](../plan.md); belum diimplementasikan pada sesi ini. Periksa arsip sebelum menetapkan nomor. Kebutuhan M2–M6 tetap berlaku dan bukan bagian dari klaim selesai M1.

## Global Agent Rules

1. Mulai dari ticket pertama yang dependensinya sudah terpenuhi.
2. Jangan eksekusi bagian yang masih `DRAFT` atau `REVIEW`; permintaan implementasi yang jelas dari pengguna mengotorisasi lingkup permintaan itu tanpa persetujuan berulang.
3. Baca `Business Decision Snapshot` sebelum mengubah kode.
4. Ikuti `Non-Negotiable Technical Contract`; catat dan selesaikan konflik dengan spesifikasi sebelum menyimpang.
5. Laporkan dependensi yang belum lengkap atau tidak konsisten.
