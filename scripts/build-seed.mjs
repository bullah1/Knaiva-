// Builds seed/seed.sql (categories, 70 products, content, settings) from the Shopify CSV.
// Usage: node scripts/build-seed.mjs
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { createHash } from 'node:crypto';

// If scripts/download-images.mjs has saved a photo locally, use the local copy (served by Cloudflare's CDN).
export const localName = (url, handle) => {
  const ext = (new URL(url).pathname.split('.').pop() || 'jpg').toLowerCase().replace(/[^a-z]/g, '') || 'jpg';
  return `${handle}-${createHash('md5').update(url).digest('hex').slice(0, 8)}.${ext}`;
};
const localize = (url, handle) => {
  const name = localName(url, handle);
  return existsSync(new URL(`../public/uploads/products/${name}`, import.meta.url)) ? `/uploads/products/${name}` : url;
};

const csv = readFileSync(new URL('../seed/kaniva_products.csv', import.meta.url), 'utf8').replace(/^﻿/, '');

export function parseCsv(text) {
  const rows = []; let row = []; let field = ''; let q = false;
  for (let i = 0; i < text.length; i++) {
    const ch = text[i];
    if (q) {
      if (ch === '"') { if (text[i + 1] === '"') { field += '"'; i++; } else q = false; }
      else field += ch;
    } else if (ch === '"') q = true;
    else if (ch === ',') { row.push(field); field = ''; }
    else if (ch === '\n' || ch === '\r') {
      if (ch === '\r' && text[i + 1] === '\n') i++;
      row.push(field); rows.push(row); row = []; field = '';
    } else field += ch;
  }
  if (field || row.length) { row.push(field); rows.push(row); }
  const [header, ...data] = rows;
  return data.filter(r => r.length === header.length).map(r => Object.fromEntries(header.map((h, i) => [h, r[i]])));
}

const decode = s => s.replace(/<[^>]+>/g, '').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#039;|&#39;/g, "'").trim();
const cleanSize = s => s.replace(/(\d[\d.]*)\s*(gm|g|kg|ml|l)\b/gi, (_, n, u) => n + (u.toLowerCase() === 'gm' ? 'g' : u.toLowerCase() === 'l' ? 'L' : u.toLowerCase()));
const sql = v => v === null || v === undefined ? 'NULL' : typeof v === 'number' ? String(v) : `'${String(v).replace(/'/g, "''")}'`;

function parseBody(html) {
  const out = { intro: '', benefits: [], bestFor: [], how: '', quick: [] };
  const box = html.match(/<div[^>]*kaniva-quick-info[^>]*>([\s\S]*?)<\/div>/);
  if (box) {
    for (const m of box[1].matchAll(/<li><strong>(.*?):<\/strong>\s*(.*?)<\/li>/g)) out.quick.push([decode(m[1]), cleanSize(decode(m[2]))]);
    html = html.replace(box[0], '');
  }
  const p = html.match(/<p>([\s\S]*?)<\/p>/); if (p) out.intro = decode(p[1]);
  const section = h => { const m = html.match(new RegExp(`<h3>${h}</h3>\\s*<ul>([\\s\\S]*?)</ul>`)); return m ? [...m[1].matchAll(/<li>([\s\S]*?)<\/li>/g)].map(x => decode(x[1])) : []; };
  out.benefits = section('Key Benefits'); out.bestFor = section('Best For');
  const how = html.match(/<h3>How to Use<\/h3>\s*<p>([\s\S]*?)<\/p>/); if (how) out.how = decode(how[1]);
  return out;
}

const CATEGORIES = [
  ['cat-dry-food', 'Cat Dry Food', 'বিড়ালের ড্রাই ফুড', 'Cat Dry Food', "Complete dry food for kittens, adult and senior cats — indoor, urinary care, skin & coat, grain free and more. Pick by your cat's age and need.", 'Cat Dry Food Price in Bangladesh — Kaniva Cat Food', 'Buy Kaniva dry cat food in Bangladesh: kitten, adult, senior, indoor, urinary and grain free formulas. Cash on delivery and fast home delivery.'],
  ['cat-wet-food', 'Cat Wet Food', 'বিড়ালের ওয়েট ফুড', 'Cat Wet Food', 'Tasty pouches in jelly, gravy and mousse — high moisture meals your cat will love. Serve alone or mix with dry food.', 'Cat Wet Food & Pouch Price in BD — Kaniva & Kandy', 'Kaniva and Kandy wet cat food pouches in jelly, gravy and mousse. Kitten and adult recipes with home delivery across Bangladesh.'],
  ['cat-treats', 'Cat Treats', 'ক্যাট ট্রিট', 'Cat Treats', 'Creamy lickable treats for rewards, training, bonding and hiding medicine.', 'Cat Creamy Treats Price in BD — Kaniva Lickable Treats', 'Kaniva creamy lickable cat treats in tuna, chicken, lamb and seafood flavours. Order online in Bangladesh.'],
  ['cat-litter', 'Cat Litter', 'ক্যাট লিটার', 'Cat Litter', 'Plant-based tofu cat litter that clumps fast, controls odour and is low in dust.', 'Tofu Cat Litter Price in BD — Kasty Pop 6L', 'Kasty Pop tofu cat litter 6L in six scents. Fast clumping, odour control and low dust. Delivered across Bangladesh.'],
  ['pet-wipes', 'Pet Wipes', 'পেট ওয়াইপস', 'Pet Wipes', 'Soft pet wipes for quick cleaning of paws, face, ears and coat between baths.', 'Pet Wipes Price in BD — Kanimal 100 Sheets', 'Kanimal pet wipes, 100 soft sheets in six scents for cats and dogs. Order online in Bangladesh.'],
  ['pet-accessories', 'Accessories', 'অ্যাক্সেসরিজ', 'Pet Accessories', 'Everyday essentials like feeding bowls for cats and dogs.', 'Pet Accessories in Bangladesh — Kaniva', 'Kaniva pet accessories including feeding bowls. Order online with home delivery in Bangladesh.'],
  ['dog-food', 'Dog Food', 'কুকুরের খাবার', 'Dog Dry Food', 'Complete dry dog food with tuna and rice in salmon, lamb and chicken recipes.', 'Dog Food Price in Bangladesh — Kaniva Dry Dog Food', 'Kaniva dry dog food with tuna and rice. Salmon, lamb and chicken recipes with home delivery across Bangladesh.'],
];
const POPULAR = ['kaniva-fusion-salmon-sweet-potato-cat-food', 'kaniva-grain-free-chicken-tuna-cat-food', 'kaniva-urinary-care', 'kaniva-mother-cat-kitten-cat-food', 'kandy-student-pouch-mousse-tuna-and-salmon', 'kaniva-tuna-chicken-in-jelly-pouch', 'kaniva-creamy-chicken', 'kasty-pop-yuzu-black-tea-cat-litter-6l'];

const out = ['-- Generated by scripts/build-seed.mjs — safe to re-run on an empty database', 'PRAGMA foreign_keys = ON;'];
CATEGORIES.forEach(([slug, name, bn, , desc, st, sd], i) => {
  out.push(`INSERT INTO categories (id, slug, name, name_bn, description, sort_order, seo_title, seo_description) VALUES (${i + 1}, ${sql(slug)}, ${sql(name)}, ${sql(bn)}, ${sql(desc)}, ${i + 1}, ${sql(st)}, ${sql(sd)});`);
});
const catByType = Object.fromEntries(CATEGORIES.map(([, , , type], i) => [type, i + 1]));

const groups = new Map();
for (const r of parseCsv(csv)) { if (!r.Handle) continue; if (!groups.has(r.Handle)) groups.set(r.Handle, []); groups.get(r.Handle).push(r); }

let pid = 0, vid = 0;
for (const [handle, rows] of groups) {
  const f = rows[0]; if (!f.Title) continue; pid++;
  const b = parseBody(f['Body (HTML)'] || '');
  const tags = (f.Tags || '').split(',').map(t => t.trim()).filter(Boolean);
  const low = tags.map(t => t.toLowerCase());
  const images = rows.filter(r => r['Image Src']).sort((a, z) => (+a['Image Position'] || 0) - (+z['Image Position'] || 0)).map(r => localize(r['Image Src'], handle));
  const variants = rows.filter(r => r['Variant Price'] !== '' || r['Option1 Value'] !== '').map(r => {
    let label = (r['Option1 Value'] || '').trim();
    if (!label || label === 'Default Title') {
      const m = f.Title.match(/(\d+(?:\.\d+)?\s?(?:L|kg|g|ml))\b/i), s = f.Title.match(/(\d+)\s*Sheets/i);
      label = m ? m[1].replace(' ', '') : s ? `${s[1]} Sheets` : f.Type === 'Cat Wet Food' ? '1 Pouch' : '1 Pc';
    }
    return { label: cleanSize(label), sku: r['Variant SKU'] || null, price: +r['Variant Price'] || 0, mrp: r['Variant Compare At Price'] ? +r['Variant Compare At Price'] : null };
  });
  const active = (f.Status || 'active').toLowerCase() === 'active' && variants.some(v => v.price > 0) ? 1 : 0;
  const stage = ['Kitten', 'Senior', 'Adult', 'All Life Stages'].find(s => low.includes(s.toLowerCase())) || null;
  const flavor = ['Chicken', 'Tuna', 'Salmon', 'Lamb', 'Crab', 'Scallop', 'Shrimp', 'Lobster', 'Squid', 'Cod', 'Fish'].filter(x => low.includes(x.toLowerCase())).join(', ') || null;
  const foodType = /Dry/.test(f.Type) ? 'dry' : /Wet/.test(f.Type) ? 'wet' : /Treat/.test(f.Type) ? 'treat' : f.Type.toLowerCase().replace(/\s+/g, '-');
  const brand = f.Title.split(' ')[0];
  const desc = b.intro + (b.bestFor.length ? `\n\nBest for: ${b.bestFor.join(', ')}.` : '');
  out.push(`INSERT INTO products (id, slug, name, short_description, description, feeding_guide, quick_info, features, images, tags, brand, sku, category_id, life_stage, food_type, flavor, is_popular, is_active, seo_title, seo_description, seo_keywords, created_at) VALUES (${pid}, ${sql(handle)}, ${sql(f.Title)}, ${sql(b.intro.slice(0, 300))}, ${sql(desc)}, ${sql(b.how || null)}, ${sql(JSON.stringify(b.quick))}, ${sql(JSON.stringify(b.benefits))}, ${sql(JSON.stringify(images))}, ${sql(JSON.stringify(low))}, ${sql(brand)}, ${sql(variants[0]?.sku?.replace(/-\d+$/, '') ?? null)}, ${catByType[f.Type] ?? 'NULL'}, ${sql(stage)}, ${sql(foodType)}, ${sql(flavor)}, ${POPULAR.includes(handle) ? 1 : 0}, ${active}, ${sql(f['SEO Title'] || null)}, ${sql(f['SEO Description'] || null)}, ${sql(tags.slice(0, 8).join(', '))}, datetime('now', '-${200 - pid} minutes'));`);
  variants.forEach((v, i) => { vid++; out.push(`INSERT INTO product_variants (id, product_id, weight_label, sku, mrp, price, stock, sort_order) VALUES (${vid}, ${pid}, ${sql(v.label)}, ${sql(v.sku)}, ${sql(v.mrp)}, ${v.price}, 100, ${i + 1});`); });
}

[['wheat-off', 'Grain Free', 'গ্রেইন ফ্রি', 'Gentle on sensitive tummies.'], ['flask-conical', 'Lab Tested', 'ল্যাব টেস্টেড', 'Every batch quality checked.'], ['sprout', 'Non-GMO', 'নন-জিএমও', 'Clean, honest ingredients.'], ['shield-check', 'Quality Ingredients', 'মানসম্মত উপাদান', 'Real meat as the first ingredient.']]
  .forEach(([icon, t, bn, d], i) => out.push(`INSERT INTO trust_points (icon, title, title_bn, description, sort_order) VALUES (${sql(icon)}, ${sql(t)}, ${sql(bn)}, ${sql(d)}, ${i + 1});`));
['ISO 22000', 'HACCP', 'GMP Certified', 'BSTI Approved'].forEach((t, i) => out.push(`INSERT INTO certificates (title, sort_order) VALUES (${sql(t)}, ${i + 1});`));
out.push(`INSERT INTO hero_banners (title, title_bn, subtitle, subtitle_bn, cta_label, cta_link, sort_order) VALUES ('PREMIUM NUTRITION FOR BANGLADESHI PETS', 'বাংলাদেশের পোষা প্রাণীর জন্য প্রিমিয়াম পুষ্টি', 'Grain free, lab tested Kaniva food delivered all over Bangladesh.', 'সারা বাংলাদেশে ডেলিভারি সহ গ্রেইন ফ্রি, ল্যাব টেস্টেড কানিভা খাবার।', 'Shop Now', '/shop', 1);`);

const posts = [
  ['best-cat-food-in-bangladesh', 'Best Cat Food in Bangladesh: A Simple Buying Guide', "How to pick the right cat food for your cat's age, taste and your budget in Bangladesh.", "Choosing cat food in Bangladesh can feel confusing with so many imported brands on the shelf. Start with your cat's life stage, then look at protein source and moisture.\n\n## Match the life stage\nKittens need higher protein and DHA. Adult cats do well on a balanced grain free recipe.\n\n## Read the first ingredient\nReal meat should be first on the list, not corn or by-product meal.\n\n## Mix wet and dry\nBangladesh's heat means hydration matters. A wet pouch a day helps urinary health.", 'Buying Guide', 'Best Cat Food in Bangladesh — Buying Guide 2026', 'A practical guide to choosing the best cat food in Bangladesh by life stage, ingredients and budget.', 'best cat food in bangladesh, cat food bangladesh', 3],
  ['kitten-feeding-schedule', 'Kitten Feeding Schedule: Month by Month', 'A clear feeding chart for kittens from two months to one year.', "Kittens grow fast and need frequent small meals.\n\n## 2-4 months\nThree to four meals a day of high protein kitten food.\n\n## 4-9 months\nThree meals a day, slowly increasing portion size.\n\n## 9-12 months\nMove to two meals a day and start transitioning to adult food.", 'Pet Tips', 'Kitten Feeding Schedule Month by Month — Kaniva Bangladesh', 'How much and how often to feed your kitten from two months to one year.', 'kitten food, kitten feeding schedule', 2],
  ['dry-vs-wet-cat-food', 'Dry vs Wet Cat Food: Which One Does Your Cat Need?', 'Both have a place. Here is how to balance them for a healthy cat.', "Dry food is convenient and good for dental health. Wet food adds moisture that supports the urinary tract.\n\n## The simple answer\nUse dry food as the base and add a wet pouch several times a week.", 'Pet Tips', 'Dry vs Wet Cat Food — Kaniva Bangladesh', 'Compare dry and wet cat food and learn how to balance both for your cat.', 'dry cat food, wet cat food', 1],
];
for (const [slug, title, excerpt, content, cat, st, sd, sk, days] of posts)
  out.push(`INSERT INTO blog_posts (slug, title, excerpt, content, category, seo_title, seo_description, seo_keywords, published_at) VALUES (${sql(slug)}, ${sql(title)}, ${sql(excerpt)}, ${sql(content)}, ${sql(cat)}, ${sql(st)}, ${sql(sd)}, ${sql(sk)}, datetime('now', '-${days} days'));`);

const settings = {
  delivery: { inside_dhaka: 60, outside_dhaka: 120, free_delivery_threshold: 2000 },
  payments: { cod: true, bkash: true, nagad: true, card: false, bkash_number: '01XXXXXXXXX', nagad_number: '01XXXXXXXXX' },
  contact: { phone: '+8801XXXXXXXXX', whatsapp: '8801XXXXXXXXX', email: 'hello@example.com', address: 'Dhaka, Bangladesh', facebook: '', instagram: '', youtube: '' },
  seo: { site_name: 'Kaniva Bangladesh', default_title: 'Kaniva Bangladesh — Premium Cat & Dog Food', default_description: 'Buy Kaniva cat food and dog food online in Bangladesh. Grain free, lab tested, nationwide delivery with cash on delivery, bKash and Nagad.', canonical_base: '' },
};
for (const [k, v] of Object.entries(settings)) out.push(`INSERT INTO settings (key, value) VALUES (${sql(k)}, ${sql(JSON.stringify(v))});`);

if (process.argv[1] && import.meta.url.endsWith(process.argv[1].split(/[\\/]/).pop())) {
  writeFileSync(new URL('../seed/seed.sql', import.meta.url), out.join('\n') + '\n');
  const local = out.filter(l => l.includes('/uploads/products/')).length;
  console.log(`seed.sql: ${pid} products, ${vid} variants${local ? `, ${local} products use local photos` : ''}`);
}
