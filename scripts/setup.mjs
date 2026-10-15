// One-command deploy from your own computer:
//   node scripts/setup.mjs
// 1) logs in to Cloudflare  2) copies product photos into the project (optional, recommended)
// 3) builds and deploys. The D1 database, tables and 70 products are created
// automatically on the first deploy / first visit.
import { execSync } from 'node:child_process';

const run = cmd => execSync(cmd, { stdio: 'inherit' });
const quiet = cmd => execSync(cmd, { encoding: 'utf8', stdio: ['inherit', 'pipe', 'pipe'] });
const step = (n, t) => console.log(`\n\x1b[1m[${n}/3] ${t}\x1b[0m`);

step(1, 'Cloudflare login');
try { quiet('npx wrangler whoami'); console.log('  already logged in'); } catch { run('npx wrangler login'); }

step(2, 'Copy product photos (served by Cloudflare)');
try { run('node scripts/download-images.mjs'); run('node scripts/build-seed.mjs'); }
catch { console.log('  photos skipped — the shop will use the old website links'); }

step(3, 'Build and deploy');
run('npm run deploy');

console.log('\n\x1b[32mDone!\x1b[0m Open the workers.dev address above (first visit sets up the database), then go to /setup to create your admin account.');
