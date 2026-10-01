export type Branch = { id: number; nama: string; is_active: boolean };
export type Filters = { branch_id: number | null; start: string | null; end: string | null; status: string | null; status_bayar: string | null; period: string; granularity: string; page: number };
export type ReportRow = { id: number; kode_resi: string; branch_name: string; customer_name: string; no_hp: string; status: string; status_bayar: string; total_akhir: number; paid: number; remaining: number; waktu_masuk: string };
export type ReportPage = { data: ReportRow[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null };
