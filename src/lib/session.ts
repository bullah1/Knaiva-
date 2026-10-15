import type { Context, MiddlewareHandler } from 'hono';
import { getCookie, setCookie } from 'hono/cookie';
import type { AppEnv, Session } from './env';
import { sessionSecret } from './bootstrap';

const COOKIE = 'kv_s';
const enc = new TextEncoder();

const b64url = (buf: ArrayBuffer | Uint8Array) =>
  btoa(String.fromCharCode(...new Uint8Array(buf))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
const fromB64url = (s: string) => Uint8Array.from(atob(s.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));

async function hmacKey(secret: string) {
  return crypto.subtle.importKey('raw', enc.encode(secret), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign', 'verify']);
}

export async function sign(value: string, secret: string) {
  const sig = await crypto.subtle.sign('HMAC', await hmacKey(secret), enc.encode(value));
  return `${value}.${b64url(sig)}`;
}

export async function unsign(signed: string, secret: string): Promise<string | null> {
  const i = signed.lastIndexOf('.');
  if (i < 1) return null;
  const value = signed.slice(0, i);
  try {
    const ok = await crypto.subtle.verify('HMAC', await hmacKey(secret), fromB64url(signed.slice(i + 1)), enc.encode(value));
    return ok ? value : null;
  } catch {
    return null;
  }
}

const fresh = (): Session => ({ cart: {}, orders: [], lang: 'en', csrf: crypto.randomUUID() });

/** Loads the signed cookie session; saves it back after the handler when it changed. */
export const sessionMiddleware: MiddlewareHandler<AppEnv> = async (c, next) => {
  const secret = await sessionSecret(c.env);
  let session = fresh();
  const raw = getCookie(c, COOKIE);
  if (raw) {
    const value = await unsign(raw, secret);
    if (value) {
      try {
        session = { ...fresh(), ...JSON.parse(new TextDecoder().decode(fromB64url(value))) };
      } catch { /* corrupt cookie: start fresh */ }
    }
  }
  const before = JSON.stringify(session);
  c.set('session', session);
  await next();
  const s = c.get('session');
  const after = JSON.stringify(s);
  if (after !== before || !raw) {
    const value = b64url(enc.encode(after));
    setCookie(c, COOKIE, await sign(value, secret), {
      path: '/', httpOnly: true, sameSite: 'Lax', secure: new URL(c.req.url).protocol === 'https:', maxAge: 60 * 60 * 24 * 30,
    });
  }
};

export function flash(c: Context<AppEnv>, type: 'success' | 'error', text: string, cart = false) {
  c.get('session').flash = { type, text, cart };
}

/** Reads and clears one-time values (flash, old input, errors) for the current render. */
export function takeOnce(c: Context<AppEnv>) {
  const s = c.get('session');
  const out = { flash: s.flash, old: s.old ?? {}, errors: s.errors ?? [] };
  delete s.flash; delete s.old; delete s.errors;
  return out;
}

/** CSRF check for every POST: the form must echo the session token. */
export const csrfMiddleware: MiddlewareHandler<AppEnv> = async (c, next) => {
  if (c.req.method === 'POST') {
    const form = await c.req.parseBody({ all: true });
    if (form['_token'] !== c.get('session').csrf) {
      return c.html('<h1>Page expired</h1><p>Please go back, refresh the page and try again.</p>', 403);
    }
  }
  await next();
};
