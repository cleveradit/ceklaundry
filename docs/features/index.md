# Feature Docs — Index

Fitur yang telah diimplementasikan dan diverifikasi dalam lingkungan lokal/CI; Live tidak berarti sudah dideploy produksi.

| Fitur | Status | File utama |
|---|---|---|
| [M1 — Fondasi dan tenant](m1-fondasi-dan-tenant.md) | Live | app/Services, app/Policies, resources/js/Pages, database/migrations |
| [M2 — Operasional inti](m2-operasional-inti.md) | Live; uji printer fisik dilewati sesuai instruksi pengguna | transaksi, pembayaran, pelanggan, status, resi, dashboard |
| [M3 — Notifikasi](m3-notifikasi.md) | Live pada runtime lokal/CI; lihat batas bukti audit M3 | email transaksi, ready/pengingat, WA opsional, log dan restore hold |

Rancangan M4–M6 ada di [user stories](../initiate-file/user-stories.md). Mulai orientasi dari [architecture](../architecture.md), [data model](../data-model.md), [verifikasi M1](../audits/m1-verification.md), [verifikasi M2](../audits/m2-verification.md), dan [verifikasi M3](../audits/m3-verification.md).
