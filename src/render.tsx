import type { Context } from 'hono';
import type { Child } from 'hono/jsx';
import type { AppEnv } from './lib/env';
import { activeCategories } from './lib/db';
import { viewCtx, type Seo, type V } from './views/ctx';
import { Layout } from './views/layout';

/** Renders a storefront page inside the main layout. */
export async function render(c: Context<AppEnv>, seo: Seo, body: (v: V) => Child | Promise<Child>, status = 200) {
  const [v, categories] = await Promise.all([viewCtx(c), activeCategories(c.env.DB)]);
  const content = await body(v);
  return c.html(<Layout v={v} seo={seo} categories={categories}>{content}</Layout>, status as 200);
}

/** Body as a plain object (single values; arrays become the last value). */
export async function form(c: Context<AppEnv>): Promise<Record<string, string>> {
  const raw = await c.req.parseBody({ all: true });
  const out: Record<string, string> = {};
  for (const [k, val] of Object.entries(raw)) {
    const one = Array.isArray(val) ? val[val.length - 1] : val;
    if (typeof one === 'string') out[k] = one.trim();
  }
  return out;
}

export function back(c: Context<AppEnv>, fallback = '/') {
  const ref = c.req.header('referer');
  try {
    if (ref && new URL(ref).origin === new URL(c.req.url).origin) return c.redirect(ref, 303);
  } catch { /* ignore */ }
  return c.redirect(fallback, 303);
}
