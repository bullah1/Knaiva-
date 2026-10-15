import { raw } from 'hono/html';
import type { Child } from 'hono/jsx';
import type { V } from '../ctx';
import { Toasts } from '../layout';
import { Csrf, Icon } from '../ui';

const LINKS: [string, string, string][] = [
  ['/admin', 'Dashboard', 'layout-dashboard'], ['/admin/orders', 'Orders', 'receipt'], ['/admin/products', 'Products', 'package'],
  ['/admin/categories', 'Categories', 'folder'], ['/admin/coupons', 'Coupons', 'ticket'], ['/admin/blog', 'Blog', 'newspaper'],
  ['/admin/reviews', 'Reviews', 'message-square'], ['/admin/homepage', 'Homepage', 'image'], ['/admin/settings', 'Settings', 'settings'],
];

export const AdminLayout = ({ v, title, actions, children }: { v: V; title: string; actions?: Child; children?: Child }) => {
  const active = (href: string) => (href === '/admin' ? v.path === '/admin' || v.path === '/admin/' : v.path.startsWith(href));
  return (
    <>
      {raw('<!DOCTYPE html>')}
      <html lang="en">
        <head>
          <meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1" /><meta name="robots" content="noindex, nofollow" />
          <title>{title} — Kaniva Admin</title>
          <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
          <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Nunito:wght@400;600;700;800&display=swap" />
          <link rel="stylesheet" href="/assets/app.css" />
          <script src="/assets/app.js" defer></script>
        </head>
        <body class="bg-cream/60">
          <div class="lg:flex">
            <aside class="hidden w-60 shrink-0 border-r border-border bg-background lg:sticky lg:top-0 lg:block lg:h-screen">
              <a href="/admin" class="flex h-16 items-center gap-2 px-5 text-primary"><Icon name="paw-print" class="size-6" /><span class="font-extrabold">KANIVA ADMIN</span></a>
              <nav class="space-y-1 px-3" aria-label="Admin">
                {LINKS.map(([href, label, ic]) => <a href={href} class={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold ${active(href) ? 'bg-primary text-primary-foreground' : 'text-foreground hover:bg-cream'}`}><Icon name={ic} class="size-4" /> {label}</a>)}
              </nav>
              <div class="absolute inset-x-3 bottom-4 space-y-1">
                <a href="/" target="_blank" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-cream"><Icon name="external-link" class="size-4" /> View site</a>
                <form method="post" action="/logout"><Csrf v={v} /><button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold hover:bg-cream"><Icon name="log-out" class="size-4" /> Sign out</button></form>
              </div>
            </aside>
            <div class="min-w-0 flex-1">
              <div class="sticky top-0 z-30 border-b border-border bg-background lg:hidden">
                <div class="flex h-14 items-center justify-between px-4">
                  <span class="flex items-center gap-2 font-extrabold text-primary"><Icon name="paw-print" class="size-5" /> KANIVA ADMIN</span>
                  <div class="flex items-center gap-1">
                    <a href="/" class="btn-icon" aria-label="View site"><Icon name="external-link" class="size-4" /></a>
                    <form method="post" action="/logout"><Csrf v={v} /><button class="btn-icon" aria-label="Sign out"><Icon name="log-out" class="size-4" /></button></form>
                  </div>
                </div>
                <nav class="no-scrollbar flex gap-2 overflow-x-auto px-4 pb-3">{LINKS.map(([href, label]) => <a href={href} class={`chip h-9 ${active(href) ? 'chip-active' : ''}`}>{label}</a>)}</nav>
              </div>
              <main class="mx-auto max-w-6xl p-4 md:p-8">
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3"><h1 class="text-xl font-extrabold md:text-2xl">{title}</h1><div class="flex flex-wrap gap-2">{actions}</div></div>
                {v.errors.length ? <div class="mb-6 rounded-xl border border-destructive/30 bg-background p-4 text-sm text-destructive" role="alert"><ul class="list-disc pl-5">{v.errors.map(e => <li>{e}</li>)}</ul></div> : null}
                {children}
              </main>
            </div>
          </div>
          <Toasts v={v} />
        </body>
      </html>
    </>
  );
};

const STATUS_COLORS: Record<string, string> = { pending: 'bg-amber-100 text-amber-800', confirmed: 'bg-sky-100 text-sky-800', processing: 'bg-indigo-100 text-indigo-800', shipped: 'bg-violet-100 text-violet-800', delivered: 'bg-emerald-100 text-emerald-800', cancelled: 'bg-zinc-200 text-zinc-700' };
export const StatusBadge = ({ status }: { status: string }) => <span class={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold capitalize ${STATUS_COLORS[status] ?? 'bg-zinc-100'}`}>{status}</span>;

export const Toggle = ({ v, action, on, label, field }: { v: V; action: string; on: boolean | number; label: string; field?: string }) => (
  <form method="post" action={action} class="inline"><Csrf v={v} />{field ? <input type="hidden" name="field" value={field} /> : null}
    <button type="submit" class={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold ${on ? 'border-success/40 bg-success/10 text-success' : 'border-border text-muted-foreground'}`}>
      <span class={`size-2 rounded-full ${on ? 'bg-success' : 'bg-border'}`}></span>{label}
    </button>
  </form>
);

export const Field = ({ label, name, value, type = 'text', required, ph, hint, span, area, rows = 3 }: { label: string; name: string; value?: string | number | null; type?: string; required?: boolean; ph?: string; hint?: string; span?: boolean; area?: boolean; rows?: number }) => (
  <div class={span ? 'md:col-span-2' : ''}>
    <label class="label">{label}{required ? ' *' : ''}</label>
    {area ? <textarea name={name} rows={rows} class="textarea" placeholder={ph}>{value ?? ''}</textarea>
      : <input name={name} type={type} value={value === null || value === undefined ? '' : String(value)} required={required} placeholder={ph} step={type === 'number' ? 'any' : undefined} class="input" />}
    {hint ? <p class="mt-1 text-xs text-muted-foreground">{hint}</p> : null}
  </div>
);

export const Check = ({ name, checked, label }: { name: string; checked: boolean | number; label: string }) => (
  <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name={name} value="1" checked={Boolean(checked)} class="size-4 accent-[var(--color-primary)]" /> {label}</label>
);

export const DeleteButton = ({ v, action, label = 'Delete', confirm = 'Delete this?' }: { v: V; action: string; label?: string; confirm?: string }) => (
  <form method="post" action={action} onsubmit={`return confirm(${JSON.stringify(confirm)})`}><Csrf v={v} /><button class="btn btn-danger h-10"><Icon name="trash-2" /> {label}</button></form>
);
