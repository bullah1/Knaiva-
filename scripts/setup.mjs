// One-command Cloudflare setup for Kaniva:
//   node scripts/setup.mjs
// 1) logs in to Cloudflare  2) creates the D1 database  3) copies product photos
// 4) creates tables + loads 70 products  5) sets the cookie secret  6) deploys.
// Safe to run again: finished steps are skipped.
import { execSync, spawnSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { randomBytes } from 'node:crypto';

const run = (cmd, opts = {}) => execSync(cmd, { stdio: 'inherit', ...opts });
const capture = cmd => execSync(cmd, { encoding: 'utf8', stdio: ['inherit', 'pipe', 'pipe'] });
const step = (n, t) => console.log(`\n\x1b[1m[${n}/7] ${t}\x1b[0m`);
const cfgPath = new URL('../wrangler.jsonc', import.meta.url);

step(1, 'Cloudflare login');
try { capture('npx wrangler whoami'); console.log('  already logged in'); } catch { run('npx wrangler login'); }

step(2, 'D1 database');
let cfg = readFileSync(cfgPath, 'utf8');
if (cfg.includes('REPLACE_WITH_YOUR_D1_DATABASE_ID')) {
  let out = '';
  try { out = capture('npx wrangler d1 create kaniva-db'); } catch (e) { out = String(e.stdout) + String(e.stderr); }
  let id = out.match(/"?database_id"?\s*[:=]\s*"([0-9a-f-]{36})"/)?.[1];
  if (!id) {
    const list = capture('npx wrangler d1 list --json');
    id = JSON.parse(list).find(d => d.name === 'kaniva-db')?.uuid;
  }
  if (!id) { console.error(out); throw new Error('Could not create or find the kaniva-db database.'); }
  cfg = cfg.replace('REPLACE_WITH_YOUR_D1_DATABASE_ID', id);
  writeFileSync(cfgPath, cfg);
  console.log(`  database id ${id} saved to wrangler.jsonc`);
} else console.log('  already configured');

step(3, 'Copy product photos to this project (served by Cloudflare)');
run('node scripts/download-images.mjs');
run('node scripts/build-seed.mjs');

step(4, 'Create tables');
run('npx wrangler d1 migrations apply kaniva-db --remote');

step(5, 'Load categories, 70 products and starter content');
const count = capture('npx wrangler d1 execute kaniva-db --remote --json --command "SELECT COUNT(*) AS n FROM products"');
if (/"n":\s*0\b/.test(count)) run('npx wrangler d1 execute kaniva-db --remote --file=seed/seed.sql');
else console.log('  products already loaded — skipped (your admin changes are kept)');

step(6, 'Build and deploy');
run('npm run deploy');

step(7, 'Cookie secret');
const secrets = (() => { try { return capture('npx wrangler secret list'); } catch { return ''; } })();
if (secrets.includes('SESSION_SECRET')) console.log('  already set');
else {
  const r = spawnSync('npx wrangler secret put SESSION_SECRET', { input: randomBytes(32).toString('hex'), shell: true, stdio: ['pipe', 'inherit', 'inherit'] });
  if (r.status !== 0) throw new Error('Could not set SESSION_SECRET');
}

console.log('\n\x1b[32mAll done!\x1b[0m Open the workers.dev address printed above, then go to /setup to create your admin account.');
