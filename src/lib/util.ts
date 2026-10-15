import { ICONS } from './icons';

export const bdt = (n: number | string | null | undefined) => '৳' + Math.round(Number(n) || 0).toLocaleString('en-US');

export function icon(name: string, cls = 'size-5'): string {
  const alias: Record<string, string> = { paw: 'paw-print', 'check-circle': 'circle-check' };
  const paths = ICONS[alias[name] ?? name] ?? ICONS['paw-print'];
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="${cls}" aria-hidden="true">${paths}</svg>`;
}

export function escapeHtml(s: string): string {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

export function slugify(s: string): string {
  return s.toLowerCase().normalize('NFKD').replace(/[^\w\s-]/g, '').trim().replace(/[\s_]+/g, '-').replace(/-+/g, '-').slice(0, 80) || 'item';
}

export function whatsappLink(num: string | undefined | null, msg?: string): string | null {
  const d = (num ?? '').replace(/\D/g, '');
  return d ? `https://wa.me/${d}${msg ? `?text=${encodeURIComponent(msg)}` : ''}` : null;
}

/** Tiny Markdown subset for blog posts: ## / ### headings, "- " bullets, paragraphs. Always escaped. */
export function renderMarkdown(src: string | null): string {
  let html = '', list = false;
  for (const raw of (src ?? '').split(/\r?\n/)) {
    const t = raw.trim();
    const item = t.startsWith('- ') || t.startsWith('* ');
    if (list && !item) { html += '</ul>'; list = false; }
    if (!t) continue;
    if (t.startsWith('### ')) html += `<h3>${escapeHtml(t.slice(4))}</h3>`;
    else if (t.startsWith('## ')) html += `<h2>${escapeHtml(t.slice(3))}</h2>`;
    else if (item) { if (!list) { html += '<ul>'; list = true; } html += `<li>${escapeHtml(t.slice(2))}</li>`; }
    else html += `<p>${escapeHtml(t)}</p>`;
  }
  return html + (list ? '</ul>' : '');
}

export function formatDate(s: string | null): string {
  if (!s) return '';
  const d = new Date(s.replace(' ', 'T') + (s.includes('Z') || s.includes('+') ? '' : 'Z'));
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Dhaka' });
}

export function isoDate(s: string | null): string {
  return s ? s.slice(0, 10) : '';
}

export const parseJson = <T,>(s: unknown, fallback: T): T => {
  if (typeof s !== 'string') return fallback;
  try { return JSON.parse(s) as T; } catch { return fallback; }
};
