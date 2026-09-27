# CekLaundry — Implementation Plan Index

Urutkan ticket aktif menurut dependensi. Kebutuhan awal ada di [user-stories.md](../initiate-file/user-stories.md); ticket dibuat ketika pekerjaan siap dikerjakan.

## Status Legend

- `DRAFT`: keputusan penting belum lengkap
- `REVIEW`: perlu keputusan atau persetujuan pengguna
- `READY`: pekerjaan dalam lingkup ticket sudah diotorisasi pengguna
- `DONE`: diimplementasikan dan diverifikasi

## Execution Order

Seluruh TICKET-001–010 telah diotorisasi pengguna pada 28 September 2026. Kerjakan sesuai dependensi; status DONE hanya sesudah implementasi dan verifikasi.

| Urutan | Tahap | Ticket | Hasil utama | Depends On | Status |
|---|---|---|---|---|---|
| 1 | 0 — Persiapan | [TICKET-001](TICKET-001-persiapan-implementasi.md) | Lingkungan, jalur runtime/build dan rahasia lokal terverifikasi | — | READY |
| 2 | 1 — M1 | [TICKET-002](TICKET-002-scaffold-runtime-ci.md) | Scaffold, Docker, frontend, tooling dan CI dasar | 001 | READY |
| 3 | 1 — M1 | [TICKET-003](TICKET-003-skema-fondasi.md) | Migrasi fondasi dan tabel pendukung guard/statistik M1 | 002 | READY |
| 4 | 1 — M1 | [TICKET-004](TICKET-004-isolasi-tenant-dan-lock.md) | Tenant, policy cabang, evaluator lifecycle dan root lock | 003 | READY |
| 5 | 1 — M1 | [TICKET-005](TICKET-005-autentikasi-dan-bootstrap.md) | Bootstrap developer, auth, sesi dan email keamanan | 004 | READY |
| 6 | 1 — M1 | [TICKET-006](TICKET-006-bisnis-dan-lifecycle.md) | Provisioning bisnis, panel developer dan lifecycle | 005 | READY |
| 7 | 1 — M1 | [TICKET-007](TICKET-007-cabang-dan-admin.md) | Cabang dan admin, revocation serta guard transaksi aktif | 006 | READY |
| 8 | 1 — M1 | [TICKET-008](TICKET-008-katalog-layanan.md) | Master/lokal, validasi dan perlindungan snapshot | 007 | READY |
| 9 | 1 — M1 | [TICKET-009](TICKET-009-sinkronisasi-layanan.md) | Preview, stale409 dan sync multi-cabang atomik | 008 | READY |
| 10 | 1 — M1 | [TICKET-010](TICKET-010-verifikasi-dan-handoff-m1.md) | Setup bersih, CI/QA terpadu dan handoff M2 | 002–009 | READY |

Nomor berikutnya: `TICKET-011`; tetap periksa indeks dan arsip sebelum memakainya. Ticket selesai dipindah ke `Ticket-Implemented/` dan dihapus dari daftar aktif setelah bukti implementasi/verifikasi tersedia.

## Cakupan kebutuhan M1

| User story | Ticket utama | Dukungan / verifikasi |
|---|---|---|
| US-101 — Login dan password | 005 | 002–004, 010 |
| US-102 — Pendaftaran bisnis | 006 | 003–005, 010 |
| US-103 — Administrasi dan statistik developer | 006 | 003–005, 010 |
| US-104 — Lifecycle bisnis | 006 | Evaluator004, auth005, verifikasi010 |
| US-105 — Cabang | 007 | 003–006, 010 |
| US-106 — Admin | 007 | AccountService005, isolasi004, 010 |
| US-107 — Layanan master | 008 | 003–004, 010 |
| US-108 — Sinkronisasi dan layanan cabang | 009; edit langsung pada008 | 003–004, 008, 010 |
| US-109 — Isolasi tenant/cabang | 004 | Seluruh endpoint M1 pada005–009, 010 |

TICKET-010 mencatat pengujian integrasi lanjutan saat consumer M2–M6 tersedia. Tabel pendukung di TICKET-003 tidak memajukan implementasi fitur transaksi/notifikasi/loyalti ke M1. Penyelesaian tiket dokumentasi ini bukan penyelesaian tahap0 atau M1.

## Global Agent Rules

1. Mulai dari ticket pertama yang dependensinya sudah terpenuhi.
2. Jangan eksekusi bagian yang masih `DRAFT` atau `REVIEW`; permintaan implementasi yang jelas dari pengguna mengotorisasi lingkup permintaan itu tanpa persetujuan berulang.
3. Baca `Business Decision Snapshot` sebelum mengubah kode.
4. Ikuti `Non-Negotiable Technical Contract`; catat dan selesaikan konflik dengan spesifikasi sebelum menyimpang.
5. Laporkan dependensi yang belum lengkap atau tidak konsisten.
