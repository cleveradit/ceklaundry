# Feature Docs — Index

Fitur yang telah diimplementasikan dan diverifikasi dalam lingkungan lokal/CI; Live tidak berarti sudah dideploy produksi.

| Fitur | Status | File utama |
|---|---|---|
| [M1 — Fondasi dan tenant](m1-fondasi-dan-tenant.md) | Live | app/Services, app/Policies, resources/js/Pages, database/migrations |
| [M2 — Operasional inti](m2-operasional-inti.md) | Live; uji printer fisik dilewati sesuai instruksi pengguna | transaksi, pembayaran, pelanggan, status, resi, dashboard |
| [M3 — Notifikasi](m3-notifikasi.md) | Live pada runtime lokal/CI; lihat batas bukti audit M3 | email transaksi, ready/pengingat, WA opsional, log dan restore hold |
| [M4 — Loyalti dan promo](m4-loyalti-dan-promo.md) | Live pada runtime lokal/CI; lihat batas bukti audit M4 | pengaturan stempel, ledger, penukaran, promo, status publik dan resi |
| [M5 — Laporan owner](m5-laporan-owner.md) | Live pada runtime lokal/CI di branch `codex/m5-reports`; lihat audit M5 | riwayat, pendapatan payment, tagihan, dashboard, grafik, CSV dan snapshot baca |

Rancangan M6 ada di [user stories](../initiate-file/user-stories.md). Mulai orientasi dari [architecture](../architecture.md), [data model](../data-model.md), dan [audit M5](../audits/m5-verification.md).
