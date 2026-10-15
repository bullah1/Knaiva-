// Downloads every product photo from the CSV into public/uploads/products,
// so the shop's images are served by Cloudflare instead of the old website.
// Usage: node scripts/download-images.mjs   (then: node scripts/build-seed.mjs)
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { localName, parseCsv } from './build-seed.mjs';

const dir = new URL('../public/uploads/products/', import.meta.url);
mkdirSync(dir, { recursive: true });
const rows = parseCsv(readFileSync(new URL('../seed/kaniva_products.csv', import.meta.url), 'utf8').replace(/^﻿/, ''));
const jobs = rows.filter(r => r.Handle && r['Image Src']).map(r => ({ url: r['Image Src'], name: localName(r['Image Src'], r.Handle) }));

let ok = 0, skip = 0, fail = 0;
const queue = [...jobs];
async function worker() {
  for (let job = queue.shift(); job; job = queue.shift()) {
    const file = new URL(job.name, dir);
    if (existsSync(file)) { skip++; continue; }
    try {
      const res = await fetch(job.url, { headers: { 'User-Agent': 'Mozilla/5.0 KanivaImageCopy' } });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const buf = Buffer.from(await res.arrayBuffer());
      if (buf.length > 24 * 1024 * 1024) throw new Error('larger than 24 MB');
      writeFileSync(file, buf);
      ok++;
      process.stdout.write(`\r  downloaded ${ok}, skipped ${skip}, failed ${fail} of ${jobs.length}`);
    } catch (e) {
      fail++;
      console.log(`\n  ! ${job.url} — ${e.message}`);
    }
  }
}
await Promise.all(Array.from({ length: 6 }, worker));
console.log(`\nDone: ${ok} downloaded, ${skip} already there, ${fail} failed.`);
