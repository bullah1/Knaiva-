# Kaniva Bangladesh — Cloudflare ভার্সন

Kaniva পেট ফুড শপ, পুরোটা Cloudflare-এর **ফ্রি** প্ল্যানে চলে:

- **Cloudflare Workers**: সাইট আর admin panel (TypeScript + Hono, পেজ server-এই তৈরি হয়, তাই SEO-র জন্য ভালো)
- **Cloudflare D1**: ডাটাবেস (পণ্য, অর্ডার, কুপন, ব্লগ, সেটিংস)
- **Workers Static Assets**: CSS, JS আর পণ্যের ছবি Cloudflare-এর CDN থেকে আসে

ফ্রি প্ল্যানে দিনে ১,০০,০০০ রিকোয়েস্ট আর ৫ GB ডাটাবেস পাওয়া যায়, শুরুর দোকানের জন্য যথেষ্ট।

---

## একবারের সেটআপ (Windows)

**১. যা লাগবে**
- একটা ফ্রি Cloudflare অ্যাকাউন্ট: https://dash.cloudflare.com/sign-up
- **Node.js LTS**: https://nodejs.org থেকে নামিয়ে install করুন (Next, Next, Finish)

**২. কোড নামান**
zip extract করুন, অথবা `git clone https://github.com/bullah1/Knaiva- -b cloudflare kaniva-cf`

**৩. ফোল্ডারে PowerShell খুলুন**
ফোল্ডারের ভেতরে Shift + right-click করে "Open PowerShell window here" বেছে নিন।

**৪. দুটো কমান্ড চালান**

```powershell
npm install
node scripts/setup.mjs
```

`setup.mjs` নিজে থেকেই এই কাজগুলো করে:
1. Cloudflare লগইন (browser খুলবে, **Allow** চাপুন)
2. `kaniva-db` ডাটাবেস তৈরি করে তার id `wrangler.jsonc`-এ বসায়
3. পুরোনো সাইট থেকে ৪০০+ পণ্যের ছবি নামিয়ে প্রজেক্টে রাখে
4. ডাটাবেসে টেবিল বানায়
5. ৭টা ক্যাটাগরি, ৭০টা পণ্য, ব্লগ আর সেটিংস বসায়
6. সাইট deploy করে, শেষে `https://kaniva.<আপনার-নাম>.workers.dev` লিংক দেখায়
7. কুকির জন্য গোপন চাবি (SESSION_SECRET) সেট করে

**৫. Admin বানান**
সাইটের লিংকের শেষে **`/setup`** যোগ করে খুলুন, নিজের নাম, ইমেইল আর পাসওয়ার্ড দিন। এই পেজ শুধু একবারই কাজ করে, প্রথম admin তৈরি হলে বন্ধ হয়ে যায়।
পরে লগইন করবেন `/auth` থেকে।

**৬. Settings ঠিক করুন**
Admin → **Settings**-এ বসান: ফোন, WhatsApp, ইমেইল, bKash/Nagad নম্বর, ডেলিভারি চার্জ, আর আসল ডোমেইন (Canonical domain)।

---

## নিজের ডোমেইন যুক্ত করা

1. ডোমেইনটা Cloudflare-এ যোগ করুন (Websites → Add a site) আর nameserver বদলান
2. Workers & Pages → **kaniva** → Settings → **Domains & Routes** → Add → Custom domain → `www.আপনার-ডোমেইন.com`
3. Admin → Settings → Canonical domain-এ সেই ঠিকানা দিন

SSL, CDN আর DDoS সুরক্ষা নিজে থেকেই চালু হয়।

---

## পরে কোড বা ডিজাইন বদলালে

```powershell
npm run deploy
```

ডাটাবেসের ডাটা (অর্ডার, পণ্য, সেটিংস) এতে মোছে না।

## লোকালে চালানো (ঐচ্ছিক)

```powershell
npm run db:migrate:local
npm run db:seed:local
npm run dev
```
তারপর http://127.0.0.1:8787 খুলুন, admin বানাতে `/setup`।

## ব্যাকআপ

```powershell
npx wrangler d1 export kaniva-db --remote --output=backup.sql
```
এছাড়া Cloudflare নিজে D1-এর "Time Travel" ব্যাকআপ রাখে (ফ্রি প্ল্যানে ৭ দিন): `npx wrangler d1 time-travel info kaniva-db`

---

## যা যা আছে

**স্টোরফ্রন্ট:** হোম, শপ, ক্যাটাগরি পেজ (`/category/cat-dry-food`), প্রাসঙ্গিক ফিল্টার আর sort, প্রোডাক্ট পেজ (সাইজ বেছে নেওয়া, ছবির গ্যালারি, "এক নজরে" বাংলা বক্স), কার্ট, কুপন, চেকআউট (COD, bKash, Nagad, কার্ড), অর্ডার কনফার্মেশন, WhatsApp-এ অর্ডার শেয়ার, ব্লগ, FAQ, পলিসি পেজ, English/বাংলা।

**Admin:** ড্যাশবোর্ড (আয়, আজকের বিক্রি, লো-স্টক), অর্ডার (স্ট্যাটাস বদলানো যায়, cancel করলে স্টক ফেরত যায়), পণ্য (সাইজ, দাম, স্টক, ছবির লিংক, SEO), ক্যাটাগরি, কুপন, ব্লগ, রিভিউ, হোমপেজ ব্যানার, সেটিংস, পাসওয়ার্ড বদল।

**SEO:** প্রতিটা পেজ server-side রেন্ডার হয়। আলাদা title, description আর canonical। JSON-LD: Product, BreadcrumbList, ItemList, Article, FAQPage, Organization, WebSite, PetStore। `/sitemap.xml` (ছবিসহ) আর `/robots.txt` নিজে থেকেই তৈরি হয়। ফিল্টার করা পেজ noindex।

**নিরাপত্তা:** দাম, স্টক আর কুপন সবসময় সার্ভারে আবার হিসাব হয়। স্টক কমানো হয় শর্তসাপেক্ষ আপডেটে, তাই একই পণ্য দুবার বিক্রি হয় না। কুকি HMAC দিয়ে সাইন করা, প্রতিটা ফর্মে CSRF টোকেন আছে। পাসওয়ার্ড PBKDF2 দিয়ে হ্যাশ করা। অর্ডারের বিস্তারিত শুধু যে ব্রাউজার অর্ডার করেছে সে দেখতে পায়, অন্যদের ফোন নম্বর দিয়ে যাচাই করতে হয়।

## নতুন পণ্যের ছবি

Workers-এ সরাসরি ফাইল আপলোড হয় না। তাই admin-এ ছবির **লিংক** দিতে হয়, প্রতি লাইনে একটা। ছবি রাখার কয়েকটা উপায়:
- প্রজেক্টের `public/uploads/products/`-এ ছবি রেখে `npm run deploy` চালান, তারপর লিংক দিন `/uploads/products/ছবির-নাম.webp`
- অথবা Cloudflare **R2**-তে রাখুন (মাসে ১০ GB ফ্রি, তবে চালু করতে কার্ড লাগে) আর সেখানকার public লিংক দিন

## ফোল্ডার

- `src/index.tsx`: স্টোরফ্রন্টের সব রুট
- `src/admin.tsx`: admin panel
- `src/views/`: পেজের ডিজাইন (JSX)
- `src/lib/`: ডাটাবেস, কার্ট, সেশন, পাসওয়ার্ড
- `src/styles.css`: ডিজাইন আর ব্র্যান্ড কালার
- `migrations/`: ডাটাবেসের টেবিল
- `seed/`: পণ্যের CSV আর তা থেকে তৈরি `seed.sql` (`node scripts/build-seed.mjs`)
