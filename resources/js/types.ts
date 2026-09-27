export type Shared = {
  auth: { id: number; nama: string; email: string; role: 'developer' | 'owner' | 'admin'; must_change_password: boolean } | null;
  business: { nama: string; status: string; writable: boolean; warning: boolean; active_until: string | null } | null;
  flash: { success?: string };
  [key: string]: unknown;
};
