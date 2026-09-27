import type { ComponentProps } from 'react';
export function Field({ label, error, ...props }: ComponentProps<'input'> & { label: string; error?: string }) {
  return <label className="field"><span>{label}</span><input {...props} aria-invalid={!!error} />{error && <small className="field-error">{error}</small>}</label>;
}
export function Errors({ errors }: { errors: Record<string, string> }) {
  return Object.keys(errors).length > 0 ? <div className="notice notice-danger" role="alert">{Object.entries(errors).map(([key, value]) => <p key={key}>{value}</p>)}</div> : null;
}
