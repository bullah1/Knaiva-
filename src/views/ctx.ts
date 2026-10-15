import type { Context } from 'hono';
import type { AppEnv } from '../lib/env';
import { getSettings, siteOrigin, type Settings } from '../lib/db';
import { makeT, type T } from '../lib/i18n';
import { takeOnce } from '../lib/session';

export type Seo = {
  title?: string; description?: string; keywords?: string | null; canonical?: string | null; robots?: string;
  ogType?: string; ogImage?: string | null; jsonld?: unknown[]; preload?: string;
};

export type V = T & {
  settings: Settings; origin: string; path: string; csrf: string; cartCount: number;
  flash?: { type: 'success' | 'error'; text: string; cart?: boolean };
  old: Record<string, string>; errors: string[]; user: AppEnv['Variables']['user'];
  query: (k: string) => string | undefined;
};

export async function viewCtx(c: Context<AppEnv>): Promise<V> {
  const s = c.get('session');
  const settings = await getSettings(c.env.DB);
  const once = takeOnce(c);
  return {
    ...makeT(s.lang),
    settings,
    origin: siteOrigin(settings, c.req.url),
    path: new URL(c.req.url).pathname,
    csrf: s.csrf,
    cartCount: Object.values(s.cart).reduce((a, b) => a + b, 0),
    flash: once.flash,
    old: once.old,
    errors: once.errors,
    user: c.get('user'),
    query: k => c.req.query(k),
  };
}
