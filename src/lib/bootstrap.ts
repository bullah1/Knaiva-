import type { MiddlewareHandler } from 'hono';
import type { AppEnv } from './env';
import { SCHEMA, SEED } from '../generated/bootstrap';

let ready = false;
let secret: string | null = null;

/**
 * First request on a fresh D1 database: creates the tables and loads the starter
 * catalogue, so no command-line setup is needed. Runs once per Worker instance.
 */
export const ensureDatabase: MiddlewareHandler<AppEnv> = async (c, next) => {
  if (!ready) {
    const db = c.env.DB;
    if (!db) return c.text('D1 database binding "DB" is missing. See README.', 500);
    const has = await db.prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'settings'").first();
    if (!has) {
      await db.batch(SCHEMA.map(s => db.prepare(s)));
      const count = await db.prepare('SELECT COUNT(*) AS n FROM products').first<{ n: number }>();
      if (!count?.n) await db.batch(SEED.map(s => db.prepare(s)));
    }
    ready = true;
  }
  await next();
};

/** Cookie-signing secret: SESSION_SECRET if set, otherwise a random one kept in the database. */
export async function sessionSecret(env: AppEnv['Bindings']): Promise<string> {
  if (env.SESSION_SECRET && env.SESSION_SECRET.length >= 16) return env.SESSION_SECRET;
  if (secret) return secret;
  const bytes = crypto.getRandomValues(new Uint8Array(32));
  const fresh = [...bytes].map(b => b.toString(16).padStart(2, '0')).join('');
  await env.DB.prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('app_secret', ?)").bind(JSON.stringify(fresh)).run();
  const row = await env.DB.prepare("SELECT value FROM settings WHERE key = 'app_secret'").first<{ value: string }>();
  secret = JSON.parse(row!.value) as string;
  return secret;
}
