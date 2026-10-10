# Kaniva Bangladesh — Laravel

Kaniva পেট ফুড শপ, Laravel 12 + Blade + Tailwind + Alpine.js দিয়ে বানানো।
`vendor/` আর `public/build/` আগে থেকেই দেওয়া আছে, তাই সার্ভারে Composer বা Node লাগবে না।

**দরকার:** PHP 8.2 বা তার বেশি (pdo_mysql, mbstring, openssl, fileinfo, gd extension চালু), MySQL 5.7+ / MariaDB 10.3+

---

## cPanel-এ আপলোড (সবচেয়ে সহজ পদ্ধতি)

1. **ডাটাবেস বানান:** cPanel → *MySQL Databases* → নতুন database, user বানিয়ে user-কে database-এ *All Privileges* দিন।
2. **ফাইল আপলোড:** zip-টা `public_html`-এ আপলোড করে *Extract* করুন। (ভেতরের ফাইলগুলো সরাসরি `public_html`-এ থাকবে, যেমন `public_html/artisan`)
   - রুটের `.htaccess` সব রিকোয়েস্ট `public/` ফোল্ডারে পাঠাবে আর কোড ফাইল লুকিয়ে রাখবে।
   - **আরও ভালো:** যদি পারেন, *Domains* থেকে ডোমেইনের Document Root `public_html/public` করে দিন।
3. **.env বানান:** `.env.example` কপি করে নাম দিন `.env`। এতে `APP_URL` আর `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` বসান।
4. **PHP ভার্সন:** cPanel → *Select PHP Version* / *MultiPHP Manager* থেকে PHP 8.2 বা 8.3 বেছে নিন।
5. **cPanel → Terminal** খুলে (প্রজেক্ট ফোল্ডারে) চালান:

   ```bash
   cd ~/public_html
   php artisan key:generate --force
   php artisan migrate --seed --force
   php artisan kaniva:import database/data/kaniva_products.csv --download-images
   php artisan kaniva:admin your@email.com
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

   `migrate --seed` ৭টা ক্যাটাগরি আর ৭০টা পণ্য বসিয়ে দেয়। `kaniva:import ... --download-images` পুরোনো সাইট (kanivapetfoodbd.com) থেকে সব পণ্যের ছবি নামিয়ে `public/uploads/products`-এ রাখে, যাতে পুরোনো সাইট বন্ধ হলেও ছবি থাকে।
   `kaniva:admin` কমান্ড admin পাসওয়ার্ড চাইবে। তারপর `https://আপনার-ডোমেইন/auth` থেকে লগইন করুন।

6. ফোল্ডার পারমিশন: `storage/` আর `bootstrap/cache/` → 775, `public/uploads/` → 775।

> `.env` বদলালে আবার `php artisan config:cache` চালাবেন।

## পণ্য ও ক্যাটাগরি

- ক্যাটাগরি: Cat Dry Food, Cat Wet Food, Cat Treats, Cat Litter, Pet Wipes, Accessories, Dog Food
- ক্যাটাগরি পেজের লিংক: `/category/cat-dry-food` ইত্যাদি (পুরোনো `/shop?category=` লিংক নিজে থেকে নতুন লিংকে যায়)
- যে ক্যাটাগরিতে active পণ্য নেই সেটা সাইটে দেখায় না (যেমন Dog Food — দাম না বসানো পর্যন্ত)
- প্রতিটা ক্যাটাগরিতে শুধু সেই ক্যাটাগরির জন্য প্রাসঙ্গিক ফিল্টার দেখায় (Kitten, Indoor, Urinary Care, Jelly, Chicken…)
- **স্টক:** CSV-তে স্টকের সংখ্যা ছিল না, তাই প্রতিটা সাইজে ১০০ বসানো আছে। Admin → Products থেকে আসল স্টক দিন
- **Shopify CSV থেকে আবার আপডেট:** নতুন CSV `database/data/`-এ রেখে `php artisan kaniva:import database/data/ফাইল.csv` চালান। Handle মিলিয়ে আপডেট হয়, ডুপ্লিকেট হয় না, আর আগের স্টক বদলায় না

## লগইনের পরে প্রথম কাজ

Admin → **Settings**:
- ফোন, WhatsApp নম্বর (880 দিয়ে শুরু), ইমেইল, ঠিকানা
- bKash / Nagad নম্বর
- ডেলিভারি চার্জ আর ফ্রি ডেলিভারির সীমা
- **Canonical domain** = আপনার আসল ডোমেইন (SEO-র জন্য জরুরি)

তারপর Products-এ নিজের পণ্যের ছবি আর দাম দিন।

## Admin panel-এ যা আছে

| পেজ | কাজ |
|---|---|
| Dashboard | মোট অর্ডার, পেন্ডিং, আয়, আজকের বিক্রি, লো-স্টক অ্যালার্ট |
| Orders | স্ট্যাটাস ফিল্টার, সার্চ, বিস্তারিত, স্ট্যাটাস বদল, প্রিন্ট। Cancel করলে স্টক ফেরত যায় |
| Products | নতুন/এডিট, একাধিক ছবি, সাইজ ও দাম, স্টক, SEO, Active/Popular |
| Categories | ক্যাটাগরি, ছবি, ক্যাটাগরি পেজের SEO |
| Coupons | % বা ৳ ছাড়, মিনিমাম অর্ডার, মেয়াদ, ব্যবহারের সীমা |
| Blog | পোস্ট, কভার ছবি, SEO (`## হেডিং`, `- বুলেট` লেখা যায়) |
| Reviews | Approve / homepage-এ Feature, নতুন রিভিউ যোগ |
| Homepage | Hero ব্যানার, "Why choose us", সার্টিফিকেট |
| Settings | ডেলিভারি, পেমেন্ট, যোগাযোগ, SEO, পাসওয়ার্ড বদল |

## SEO যা করা আছে

- সব পেজ server-side রেন্ডার হয় (Google পুরো HTML পায়)
- প্রতি পেজে আলাদা title, description, canonical, Open Graph, Twitter card
- JSON-LD: Organization, WebSite (সার্চ বক্স), Product (দাম, স্টক, রেটিং, রিভিউ), BreadcrumbList, Article, FAQPage, PetStore
- `/sitemap.xml` (ছবিসহ) আর `/robots.txt` অটো তৈরি হয়
- ক্যাটাগরি পেজে নিজস্ব SEO title, description, ইন্ট্রো লেখা আর ItemList schema
- প্রতিটা পণ্যে বাংলা "এক নজরে" বক্স — কাস্টমার আর Google দুজনেই সহজে বোঝে
- ফিল্টার/সর্ট করা শপ পেজ `noindex`, তাই ডুপ্লিকেট কনটেন্ট হয় না
- কার্ট, চেকআউট, অর্ডার, admin পেজ `noindex`
- Google Search Console-এ `https://আপনার-ডোমেইন/sitemap.xml` সাবমিট করুন

## নিরাপত্তা

- দাম, স্টক, কুপন সবসময় সার্ভারে আবার হিসাব হয়; ব্রাউজার থেকে বদলানো যায় না
- অর্ডার ট্রানজ্যাকশনে হয়, একই স্টক দুইবার বিক্রি হয় না
- অর্ডার পেজ শুধু যে ব্রাউজার অর্ডার করেছে সে দেখতে পায়; অন্যদের ফোন নম্বর দিয়ে যাচাই করতে হয়
- লগইন আর চেকআউটে rate limit, সব ফর্মে CSRF

## লোকাল কম্পিউটারে চালাতে

```bash
cp .env.example .env      # DB_CONNECTION=sqlite করে দিন, APP_DEBUG=true
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan kaniva:admin admin@example.com
php artisan serve
```

ডিজাইন বদলালে: `npm install && npm run build`

## ফোল্ডার

- `app/Http/Controllers` — স্টোরফ্রন্ট; `Admin/` — admin panel
- `app/Models` — ডাটাবেস মডেল
- `app/Support/Cart.php` — কার্ট ও টোটাল হিসাব
- `resources/views` — Blade টেমপ্লেট; `admin/` — admin পেজ
- `resources/css/app.css` — ডিজাইন (ব্র্যান্ড কালার এখানে)
- `lang/en/ui.php`, `lang/bn/ui.php` — English/বাংলা লেখা
- `public/uploads` — admin থেকে আপলোড করা ছবি
