import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
export function cn(...inputs: ClassValue[]) { return twMerge(clsx(inputs)); }
export function rupiah(value: number | string) { return `Rp${Number(value).toLocaleString('id-ID')}`; }
export function tanggal(value: string | null) { return value ? new Date(`${value}T12:00:00+07:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' }) : '—'; }
