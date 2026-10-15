import { Hono } from 'hono';
import { raw } from 'hono/html';
import type { AppEnv, Category, User } from './lib/env';
import { csrfMiddleware, flash, sessionMiddleware } from './lib/session';
import {
  activeCategories, breadcrumbSchema, cartLines, couponProblem, findCoupon, getSettings, loadProducts, mainImage, minPrice,
  nextOrderNumber, PAYMENT_LABELS, siteOrigin, totals,
} from './lib/db';
import { hashPassword, verifyPassword } from './lib/auth';
import { back, form, render } from './render';
import {
  BlogIndex, BlogShow, CartPage, CheckoutPage, ContactPage, FAQS, FaqPage, FILTERS, HomePage, LoginPage, NotFound, OrderPage,
  OrderVerifyPage, ProductPage, ShopPage, Simple, SORTS, STATIC, type Banner, type Cert, type Order, type OrderItem, type Post, type Review, type Trust,
} from './views/pages';
import { admin } from './admin';

const app = new Hono<AppEnv>();

app.use('*', sessionMiddleware);
app.use('*', async (c, next) => {
  const uid = c.get('session').uid;
  c.set('user', uid ? await c.env.DB.prepare('SELECT id, name, email, role FROM users WHERE id = ?').bind(uid).first<User>() : null);
  await next();
});
app.use('*', csrfMiddleware);

const origin = async (c: Parameters<typeof render>[0]) => siteOrigin(await getSettings(c.env.DB), c.req.url);

// ---------------- Home ----------------
app.get('/', async c => {
  const db = c.env.DB;
  const [banner, categories, popular, trust, certs, reviews, posts, o] = await Promise.all([
    db.prepare('SELECT * FROM hero_banners WHERE is_active = 1 ORDER BY sort_order LIMIT 1').first<Banner>(),
    activeCategories(db),
    loadProducts(db, 'p.is_active = 1 AND p.is_popular = 1', [], 'ORDER BY p.created_at DESC LIMIT 8'),
    db.prepare('SELECT * FROM trust_points WHERE is_active = 1 ORDER BY sort_order').all<Trust>(),
    db.prepare('SELECT * FROM certificates WHERE is_active = 1 ORDER BY sort_order').all<Cert>(),
    db.prepare('SELECT * FROM reviews WHERE is_approved = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT 6').all<Review>(),
    db.prepare('SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC LIMIT 3').all<Post>(),
    origin(c),
  ]);
  const description = 'Grain free, lab tested Kaniva cat food, dog food and supplements delivered across Bangladesh. Cash on delivery, bKash and Nagad accepted.';
  return render(c, {
    title: 'Kaniva Bangladesh — Premium Cat & Dog Food Online', description, canonical: `${o}/`,
    preload: banner?.image_url ? '' : '<link rel="preload" as="image" href="/images/hero-kaniva-1600.webp" imagesrcset="/images/hero-kaniva-800.webp 800w, /images/hero-kaniva-1600.webp 1600w" imagesizes="(min-width: 768px) 50vw, 100vw" fetchpriority="high">',
    jsonld: [
      { '@context': 'https://schema.org', '@type': 'Organization', name: 'Kaniva Bangladesh', url: o, logo: `${o}/images/logo.png`, description, areaServed: 'BD' },
      { '@context': 'https://schema.org', '@type': 'WebSite', name: 'Kaniva Bangladesh', url: o, potentialAction: { '@type': 'SearchAction', target: `${o}/shop?q={search_term_string}`, 'query-input': 'required name=search_term_string' } },
    ],
  }, v => <HomePage v={v} banner={banner} categories={categories} popular={popular} trust={trust.results} certs={certs.results} reviews={reviews.results} posts={posts.results} />);
});

// ---------------- Shop & categories ----------------
const PAGE_SIZE = 12;
async function shop(c: Parameters<typeof render>[0], slug?: string) {
  if (!slug && c.req.query('category')) {
    const url = new URL(c.req.url);
    const cat = url.searchParams.get('category')!;
    url.searchParams.delete('category');
    return c.redirect(`/category/${encodeURIComponent(cat)}${url.search}`, 301);
  }
  const db = c.env.DB;
  const categories = await activeCategories(db);
  let active: Category | null = null;
  if (slug) {
    active = categories.find(x => x.slug === slug) ?? null;
    if (!active) return render(c, { title: 'Category not found — Kaniva', robots: 'noindex' }, () => <NotFound message="Category not found" />, 404);
  }
  const q = (c.req.query('q') ?? '').trim();
  const tag = c.req.query('tag') && FILTERS[c.req.query('tag')!] ? c.req.query('tag')! : null;
  const sort = c.req.query('sort') && SORTS[c.req.query('sort')!] ? c.req.query('sort')! : 'popular';
  const page = Math.max(1, parseInt(c.req.query('page') ?? '1', 10) || 1);

  let products = await loadProducts(db, active ? 'p.is_active = 1 AND p.category_id = ?' : 'p.is_active = 1', active ? [active.id] : []);
  let filters = Object.keys(FILTERS).filter(k => products.some(p => p.tags.includes(FILTERS[k][0])));
  if (filters.length < 2) filters = [];
  if (q) {
    const needle = q.toLowerCase();
    products = products.filter(p => [p.name, p.name_bn, p.sku, p.brand, p.short_description, p.tags.join(' ')].join(' ').toLowerCase().includes(needle));
  }
  if (tag) products = products.filter(p => p.tags.includes(FILTERS[tag][0]));
  const by = {
    'price-asc': (a: typeof products[0], b: typeof products[0]) => minPrice(a) - minPrice(b),
    'price-desc': (a: typeof products[0], b: typeof products[0]) => minPrice(b) - minPrice(a),
    newest: (a: typeof products[0], b: typeof products[0]) => b.created_at.localeCompare(a.created_at),
    'best-selling': (a: typeof products[0], b: typeof products[0]) => b.sold_count - a.sold_count,
    popular: (a: typeof products[0], b: typeof products[0]) => b.is_popular - a.is_popular || b.sold_count - a.sold_count || a.id - b.id,
  }[sort as 'popular'];
  products.sort(by);

  const o = await origin(c);
  const total = products.length;
  const visible = products.slice(0, page * PAGE_SIZE);
  const crumbs: [string, string | null][] = [['Home', '/'], ['Shop', active ? '/shop' : null]];
  if (active) crumbs.push([active.name, null]);
  const listUrl = active ? `/category/${active.slug}` : '/shop';
  const filtered = Boolean(q || tag || c.req.query('sort') || page > 1);
  return render(c, {
    title: active ? active.seo_title || `${active.name} Price in Bangladesh — Kaniva` : 'Shop Cat Food, Dog Food & Pet Supplies — Kaniva Bangladesh',
    description: active ? active.seo_description || active.description || '' : 'Browse Kaniva dry and wet cat food, treats, tofu litter, pet wipes and dog food. Filter by age and need, with prices in BDT and home delivery.',
    keywords: active?.seo_keywords, canonical: `${o}${listUrl}`, robots: filtered ? 'noindex, follow' : 'index, follow', ogImage: active?.image,
    jsonld: [breadcrumbSchema(crumbs, o), { '@context': 'https://schema.org', '@type': 'ItemList', name: active?.name ?? 'All products', numberOfItems: total, itemListElement: products.slice(0, 20).map((p, i) => ({ '@type': 'ListItem', position: i + 1, url: `${o}/products/${p.slug}`, name: p.name })) }],
  }, v => <ShopPage v={v} products={visible} total={total} hasMore={visible.length < total} nextPage={page + 1} categories={categories} active={active} filters={filters} listUrl={listUrl} crumbs={crumbs} q={q} tag={tag} sort={sort} />);
}
app.get('/shop', c => shop(c));
app.get('/category/:slug', c => shop(c, c.req.param('slug')));

// ---------------- Product ----------------
app.get('/products/:slug', async c => {
  const db = c.env.DB;
  const [p] = await loadProducts(db, 'p.is_active = 1 AND p.slug = ?', [c.req.param('slug')], '');
  if (!p) return render(c, { title: 'Product not found — Kaniva', robots: 'noindex' }, () => <NotFound message="Product not found" />, 404);
  const [reviews, related, cat, o] = await Promise.all([
    db.prepare('SELECT * FROM reviews WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC').bind(p.id).all<Review>(),
    loadProducts(db, 'p.is_active = 1 AND p.id != ? AND (p.category_id = ? OR ? IS NULL)', [p.id, p.category_id, p.category_id], 'ORDER BY p.is_popular DESC, p.id LIMIT 4'),
    p.category_id ? db.prepare('SELECT slug, name FROM categories WHERE id = ?').bind(p.category_id).first<{ slug: string; name: string }>() : null,
    origin(c),
  ]);
  const url = `${o}/products/${p.slug}`;
  const crumbs: [string, string | null][] = [['Home', '/'], ['Shop', '/shop']];
  if (cat) crumbs.push([cat.name, `/category/${cat.slug}`]);
  crumbs.push([p.name, null]);
  const images = p.images.length ? p.images : [mainImage(p)];
  const schema: Record<string, unknown> = {
    '@context': 'https://schema.org', '@type': 'Product', name: p.name, sku: p.sku, brand: { '@type': 'Brand', name: p.brand },
    description: p.short_description || p.description, image: images,
    offers: p.variants.map(x => ({ '@type': 'Offer', name: x.weight_label, price: x.price.toFixed(2), priceCurrency: 'BDT', sku: x.sku, availability: x.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', itemCondition: 'https://schema.org/NewCondition', url })),
  };
  if (p.review_count > 0) schema.aggregateRating = { '@type': 'AggregateRating', ratingValue: p.rating, reviewCount: p.review_count };
  if (reviews.results.length) schema.review = reviews.results.slice(0, 5).map(r => ({ '@type': 'Review', author: { '@type': 'Person', name: r.customer_name }, reviewRating: { '@type': 'Rating', ratingValue: r.rating, bestRating: 5 }, reviewBody: r.body }));
  return render(c, {
    title: p.seo_title || `${p.name} — Kaniva Bangladesh`, description: p.seo_description || p.short_description || `Buy ${p.name} online in Bangladesh.`,
    keywords: p.seo_keywords, canonical: p.canonical_url || url, ogType: 'product', ogImage: images[0], jsonld: [breadcrumbSchema(crumbs, o), schema],
  }, v => <ProductPage v={v} p={p} reviews={reviews.results} related={related} crumbs={crumbs} />);
});

// ---------------- Blog ----------------
app.get('/blog', async c => {
  const [{ results }, o] = await Promise.all([c.env.DB.prepare('SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY published_at DESC').all<Post>(), origin(c)]);
  return render(c, { title: 'Pet Tips & Cat Care Blog — Kaniva Bangladesh', description: 'Practical pet care advice for Bangladeshi cat and dog parents: feeding schedules, food comparisons and buying guides.', canonical: `${o}/blog` }, v => <BlogIndex v={v} posts={results} />);
});
app.get('/blog/:slug', async c => {
  const db = c.env.DB;
  const post = await db.prepare('SELECT * FROM blog_posts WHERE slug = ? AND is_published = 1').bind(c.req.param('slug')).first<Post>();
  if (!post) return render(c, { title: 'Article not found — Kaniva', robots: 'noindex' }, () => <NotFound message="Article not found" />, 404);
  const [related, o] = await Promise.all([db.prepare('SELECT * FROM blog_posts WHERE is_published = 1 AND id != ? ORDER BY published_at DESC LIMIT 3').bind(post.id).all<Post>(), origin(c)]);
  const crumbs: [string, string | null][] = [['Home', '/'], ['Pet Tips', '/blog'], [post.title, null]];
  const url = `${o}/blog/${post.slug}`;
  return render(c, {
    title: post.seo_title || `${post.title} — Kaniva Bangladesh`, description: post.seo_description || post.excerpt || 'Pet care advice from Kaniva Bangladesh.',
    keywords: post.seo_keywords, canonical: url, ogType: 'article', ogImage: post.cover_url,
    jsonld: [breadcrumbSchema(crumbs, o), { '@context': 'https://schema.org', '@type': 'Article', headline: post.title, description: post.excerpt, ...(post.cover_url ? { image: post.cover_url } : {}), author: { '@type': 'Organization', name: post.author }, publisher: { '@type': 'Organization', name: 'Kaniva Bangladesh', logo: { '@type': 'ImageObject', url: `${o}/images/logo.png` } }, datePublished: post.published_at.replace(' ', 'T') + 'Z', dateModified: post.updated_at.replace(' ', 'T') + 'Z', mainEntityOfPage: url }],
  }, () => <BlogShow post={post} related={related.results} crumbs={crumbs} />);
});

// ---------------- Static pages ----------------
for (const [slug, page] of Object.entries(STATIC)) {
  app.get(`/${slug}`, async c => render(c, { title: page.title, description: page.description, canonical: `${await origin(c)}/${slug}` }, () => (
    <Simple heading={page.heading}><div class="prose-content">{raw(page.html)}</div>{slug === 'about' ? <a href="/shop" class="btn btn-primary mt-6 h-11 px-8">Shop Kaniva</a> : null}</Simple>
  )));
}
app.get('/contact', async c => {
  const s = await getSettings(c.env.DB);
  const o = siteOrigin(s, c.req.url);
  return render(c, { title: 'Contact Kaniva Bangladesh — Support & Orders', description: 'Call, email or WhatsApp the Kaniva Bangladesh team for product advice, order help and delivery questions.', canonical: `${o}/contact`,
    jsonld: [{ '@context': 'https://schema.org', '@type': 'PetStore', name: 'Kaniva Bangladesh', url: o, ...(s.contact.phone ? { telephone: s.contact.phone } : {}), ...(s.contact.email ? { email: s.contact.email } : {}), address: { '@type': 'PostalAddress', streetAddress: s.contact.address, addressCountry: 'BD' }, image: `${o}/images/hero-kaniva-1600.webp` }] }, v => <ContactPage v={v} />);
});
app.get('/faq', async c => render(c, { title: 'Frequently Asked Questions — Kaniva Bangladesh', description: 'Delivery times, delivery charges, payment methods, returns and feeding questions answered for Kaniva customers in Bangladesh.', canonical: `${await origin(c)}/faq`,
  jsonld: [{ '@context': 'https://schema.org', '@type': 'FAQPage', mainEntity: FAQS.map(([q, a]) => ({ '@type': 'Question', name: q, acceptedAnswer: { '@type': 'Answer', text: a } })) }] }, () => <FaqPage />));

app.get('/lang/:lang', c => {
  const l = c.req.param('lang');
  if (l === 'en' || l === 'bn') c.get('session').lang = l;
  return back(c);
});

// ---------------- Cart ----------------
app.get('/cart', async c => {
  const db = c.env.DB;
  const s = c.get('session');
  const [lines, settings] = await Promise.all([cartLines(db, s), getSettings(db)]);
  const subtotal = lines.reduce((a, l) => a + l.line_total, 0);
  let coupon = await findCoupon(db, s.coupon);
  if (coupon && couponProblem(coupon, subtotal)) coupon = null;
  const t = totals(settings, subtotal, coupon, 'inside_dhaka');
  return render(c, { title: 'Your Cart — Kaniva Bangladesh', description: 'Review your Kaniva pet food order.', robots: 'noindex, follow' }, v => <CartPage v={v} lines={lines} subtotal={subtotal} coupon={coupon} discount={t.discount} threshold={Number(settings.delivery.free_delivery_threshold)} />);
});

app.post('/cart/add', async c => {
  const f = await form(c);
  const s = c.get('session');
  const id = parseInt(f.variant_id, 10);
  const qty = Math.min(Math.max(parseInt(f.quantity || '1', 10) || 1, 1), 50);
  const row = id ? await c.env.DB.prepare('SELECT v.id, v.stock, v.weight_label, p.name, p.is_active FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.id = ?').bind(id).first<{ id: number; stock: number; weight_label: string; name: string; is_active: number }>() : null;
  if (!row || !row.is_active) { flash(c, 'error', 'That product is no longer available.'); return back(c); }
  if (row.stock <= 0) { flash(c, 'error', `${row.name} (${row.weight_label}) is out of stock.`); return back(c); }
  s.cart[String(id)] = Math.min((s.cart[String(id)] ?? 0) + qty, 50);
  if (f.buy_now) return c.redirect('/checkout', 303);
  flash(c, 'success', `${row.name} (${row.weight_label}) added to cart`, true);
  return back(c);
});

app.post('/cart/update', async c => {
  const f = await form(c);
  const s = c.get('session');
  const qty = Math.min(parseInt(f.quantity, 10) || 0, 50);
  if (qty <= 0) delete s.cart[f.variant_id]; else if (s.cart[f.variant_id] !== undefined) s.cart[f.variant_id] = qty;
  return c.redirect('/cart', 303);
});

app.post('/cart/remove', async c => {
  const f = await form(c);
  delete c.get('session').cart[f.variant_id];
  flash(c, 'success', 'Item removed from cart');
  return c.redirect('/cart', 303);
});

app.post('/cart/coupon', async c => {
  const f = await form(c);
  const s = c.get('session');
  if (f.remove) { delete s.coupon; flash(c, 'success', 'Coupon removed'); return back(c, '/cart'); }
  const lines = await cartLines(c.env.DB, s);
  const subtotal = lines.reduce((a, l) => a + l.line_total, 0);
  const coupon = await findCoupon(c.env.DB, f.code);
  const problem = coupon ? couponProblem(coupon, subtotal) : 'Coupon not found';
  if (problem || !coupon) { delete s.coupon; s.old = { code: f.code ?? '' }; flash(c, 'error', problem ?? 'Coupon not found'); return back(c, '/cart'); }
  s.coupon = coupon.code;
  const t = totals(await getSettings(c.env.DB), subtotal, coupon, 'inside_dhaka');
  flash(c, 'success', `Coupon applied — you saved ৳${t.discount.toLocaleString('en-US')}`);
  return back(c, '/cart');
});

// ---------------- Checkout ----------------
const paymentOptions = (p: Record<string, unknown>) => Object.entries(PAYMENT_LABELS).filter(([k]) => p[k]);

app.get('/checkout', async c => {
  const db = c.env.DB;
  const s = c.get('session');
  const lines = await cartLines(db, s);
  if (!lines.length) return c.redirect('/cart', 303);
  const settings = await getSettings(db);
  const subtotal = lines.reduce((a, l) => a + l.line_total, 0);
  let coupon = await findCoupon(db, s.coupon);
  if (coupon && couponProblem(coupon, subtotal)) coupon = null;
  const zone = s.old?.delivery_zone || 'inside_dhaka';
  return render(c, { title: 'Checkout — Kaniva Bangladesh', description: 'Complete your Kaniva pet food order.', robots: 'noindex, follow' }, v => <CheckoutPage v={v} lines={lines} t={totals(settings, subtotal, coupon, zone)} paymentOptions={paymentOptions(settings.payments)} />);
});

app.post('/checkout', async c => {
  const db = c.env.DB;
  const s = c.get('session');
  const f = await form(c);
  const settings = await getSettings(db);
  const pays = paymentOptions(settings.payments).map(([k]) => k);
  const errors: string[] = [];
  const phone = (f.phone ?? '').replace(/\s+/g, '');
  if (!f.customer_name || f.customer_name.length < 2) errors.push('Please enter your full name.');
  if (!/^(\+?88)?01[3-9]\d{8}$/.test(phone)) errors.push('Enter a valid Bangladeshi mobile number (01XXXXXXXXX).');
  if (!f.address || f.address.length < 5) errors.push('Please enter your full address.');
  if (!['inside_dhaka', 'outside_dhaka'].includes(f.delivery_zone)) errors.push('Choose a delivery area.');
  if (!pays.includes(f.payment_method)) errors.push('That payment method is not available right now.');
  for (const k of ['customer_name', 'area', 'city']) if ((f[k] ?? '').length > 120) errors.push(`${k} is too long.`);
  if ((f.address ?? '').length > 500 || (f.notes ?? '').length > 500) errors.push('Address or note is too long.');

  const lines = await cartLines(db, s);
  if (!lines.length) return c.redirect('/cart', 303);
  for (const l of lines) if (l.variant.stock < l.quantity) errors.push(`${l.product.name} (${l.variant.weight_label}) has only ${l.variant.stock} left in stock.`);
  if (errors.length) {
    s.errors = errors;
    s.old = Object.fromEntries(['customer_name', 'phone', 'address', 'area', 'city', 'notes', 'delivery_zone', 'payment_method'].map(k => [k, f[k] ?? '']));
    return c.redirect('/checkout', 303);
  }

  const subtotal = lines.reduce((a, l) => a + l.line_total, 0);
  let coupon = await findCoupon(db, s.coupon);
  if (coupon && couponProblem(coupon, subtotal)) coupon = null;
  const t = totals(settings, subtotal, coupon, f.delivery_zone);

  // Reserve stock atomically: each update only succeeds if enough stock is still there.
  let number = await nextOrderNumber(db);
  const stockStmts = lines.map(l => db.prepare('UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?').bind(l.quantity, l.variant.id, l.quantity));
  const results = await db.batch(stockStmts);
  const failed = results.findIndex(r => (r.meta.changes ?? 0) === 0);
  if (failed >= 0) {
    // Put back whatever was taken, then explain.
    const undo = lines.filter((_, i) => (results[i].meta.changes ?? 0) > 0).map(l => db.prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?').bind(l.quantity, l.variant.id));
    if (undo.length) await db.batch(undo);
    s.errors = [`${lines[failed].product.name} (${lines[failed].variant.weight_label}) just went out of stock.`];
    return c.redirect('/checkout', 303);
  }

  let orderId = 0;
  for (let attempt = 0; attempt < 3 && !orderId; attempt++) {
    try {
      const row = await db.prepare(`INSERT INTO orders (order_number, customer_name, phone, address, area, city, delivery_zone, payment_method, coupon_code, subtotal, discount, delivery_charge, total, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id`).bind(number, f.customer_name, phone, f.address, f.area || null, f.city || null, f.delivery_zone, f.payment_method, coupon?.code ?? null, t.subtotal, t.discount, t.delivery_charge, t.total, f.notes || null).first<{ id: number }>();
      orderId = row!.id;
    } catch {
      number = await nextOrderNumber(db); // order number taken by a parallel checkout
    }
  }
  if (!orderId) { s.errors = ['We could not place your order. Please try again.']; return c.redirect('/checkout', 303); }

  await db.batch([
    ...lines.map(l => db.prepare('INSERT INTO order_items (order_id, product_id, variant_id, product_name, product_slug, variant_label, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
      .bind(orderId, l.product.id, l.variant.id, l.product.name, l.product.slug, l.variant.weight_label, l.price, l.quantity, l.line_total)),
    ...lines.map(l => db.prepare('UPDATE products SET sold_count = sold_count + ? WHERE id = ?').bind(l.quantity, l.product.id)),
    ...(coupon ? [db.prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?').bind(coupon.id)] : []),
  ]);

  s.cart = {};
  delete s.coupon;
  s.orders = [...s.orders.slice(-9), number];
  return c.redirect(`/order/${number}`, 303);
});

// ---------------- Order confirmation ----------------
app.get('/order/:num', async c => {
  const num = c.req.param('num');
  const db = c.env.DB;
  const o = await db.prepare('SELECT * FROM orders WHERE order_number = ?').bind(num).first<Order>();
  const seo = { title: `Order ${num} — Kaniva Bangladesh`, description: 'Your Kaniva order confirmation.', robots: 'noindex, nofollow' };
  if (!o) return render(c, seo, () => <NotFound message="Order not found" />, 404);
  if (!c.get('session').orders.includes(o.order_number)) return render(c, seo, v => <OrderVerifyPage v={v} num={o.order_number} />);
  const { results } = await db.prepare('SELECT * FROM order_items WHERE order_id = ?').bind(o.id).all<OrderItem>();
  return render(c, seo, v => <OrderPage v={v} o={o} items={results} />);
});

app.post('/order/:num', async c => {
  const f = await form(c);
  const digits = (f.phone ?? '').replace(/\D/g, '');
  const o = await c.env.DB.prepare('SELECT order_number, phone FROM orders WHERE order_number = ?').bind(c.req.param('num')).first<{ order_number: string; phone: string }>();
  if (o && digits.length >= 10 && o.phone.replace(/\D/g, '').endsWith(digits.slice(-10))) {
    const s = c.get('session');
    s.orders = [...s.orders.slice(-9), o.order_number];
    return c.redirect(`/order/${o.order_number}`, 303);
  }
  flash(c, 'error', 'That phone number does not match this order.');
  return back(c);
});

// ---------------- SEO ----------------
app.get('/sitemap.xml', async c => {
  const db = c.env.DB;
  const o = await origin(c);
  const [cats, prods, posts] = await Promise.all([
    activeCategories(db),
    db.prepare("SELECT slug, updated_at, json_extract(images, '$[0]') AS image FROM products WHERE is_active = 1").all<{ slug: string; updated_at: string; image: string | null }>(),
    db.prepare('SELECT slug, updated_at, cover_url FROM blog_posts WHERE is_published = 1').all<{ slug: string; updated_at: string; cover_url: string | null }>(),
  ]);
  const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
  const urls: [string, string, string, string | null, string | null][] = [
    ['/', '1.0', 'daily', null, null], ['/shop', '0.9', 'daily', null, null], ['/blog', '0.8', 'weekly', null, null], ['/about', '0.5', 'monthly', null, null],
    ['/contact', '0.5', 'monthly', null, null], ['/faq', '0.5', 'monthly', null, null], ['/privacy-policy', '0.3', 'yearly', null, null], ['/terms', '0.3', 'yearly', null, null], ['/returns', '0.3', 'yearly', null, null],
    ...cats.map(x => [`/category/${x.slug}`, '0.8', 'weekly', x.updated_at, x.image ?? null] as [string, string, string, string | null, string | null]),
    ...prods.results.map(x => [`/products/${x.slug}`, '0.8', 'weekly', x.updated_at, x.image] as [string, string, string, string | null, string | null]),
    ...posts.results.map(x => [`/blog/${x.slug}`, '0.6', 'monthly', x.updated_at, x.cover_url] as [string, string, string, string | null, string | null]),
  ];
  const body = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">\n` +
    urls.map(([p, pr, cf, lm, img]) => `  <url><loc>${esc(o + p)}</loc>${lm ? `<lastmod>${lm.slice(0, 10)}</lastmod>` : ''}<changefreq>${cf}</changefreq><priority>${pr}</priority>${img ? `<image:image><image:loc>${esc(img.startsWith('http') ? img : o + img)}</image:loc></image:image>` : ''}</url>`).join('\n') +
    '\n</urlset>\n';
  return c.body(body, 200, { 'Content-Type': 'application/xml; charset=utf-8', 'Cache-Control': 'public, max-age=3600' });
});

app.get('/robots.txt', async c => c.text(`User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /auth\nDisallow: /setup\nDisallow: /cart\nDisallow: /checkout\nDisallow: /order/\n\nSitemap: ${await origin(c)}/sitemap.xml\n`));

// ---------------- Staff auth ----------------
const userCount = async (db: D1Database) => (await db.prepare('SELECT COUNT(*) AS n FROM users').first<{ n: number }>())!.n;

app.get('/auth', async c => {
  if (c.get('user')) return c.redirect('/admin', 303);
  if ((await userCount(c.env.DB)) === 0) return c.redirect('/setup', 303);
  return render(c, { title: 'Staff Sign In — Kaniva Bangladesh', robots: 'noindex' }, v => <LoginPage v={v} setup={false} />);
});

app.post('/auth', async c => {
  const f = await form(c);
  const s = c.get('session');
  const u = await c.env.DB.prepare('SELECT id, password_hash FROM users WHERE email = ? COLLATE NOCASE').bind(f.email ?? '').first<{ id: number; password_hash: string }>();
  if (!u || !(await verifyPassword(f.password ?? '', u.password_hash))) {
    s.errors = ['Wrong email or password.'];
    s.old = { email: f.email ?? '' };
    return c.redirect('/auth', 303);
  }
  s.uid = u.id;
  s.csrf = crypto.randomUUID();
  return c.redirect('/admin', 303);
});

app.get('/setup', async c => {
  if ((await userCount(c.env.DB)) > 0) return c.redirect('/auth', 303);
  return render(c, { title: 'Set up Kaniva admin', robots: 'noindex' }, v => <LoginPage v={v} setup />);
});

app.post('/setup', async c => {
  const db = c.env.DB;
  if ((await userCount(db)) > 0) return c.redirect('/auth', 303);
  const f = await form(c);
  const s = c.get('session');
  if (!f.name || !/^\S+@\S+\.\S+$/.test(f.email ?? '') || (f.password ?? '').length < 8) {
    s.errors = ['Enter a name, a valid email and a password of at least 8 characters.'];
    return c.redirect('/setup', 303);
  }
  const row = await db.prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?) RETURNING id').bind(f.name, f.email, await hashPassword(f.password), 'admin').first<{ id: number }>();
  s.uid = row!.id;
  s.csrf = crypto.randomUUID();
  flash(c, 'success', 'Admin account created. Welcome!');
  return c.redirect('/admin', 303);
});

app.post('/logout', c => {
  const s = c.get('session');
  delete s.uid;
  s.csrf = crypto.randomUUID();
  return c.redirect('/auth', 303);
});

app.route('/admin', admin);

app.notFound(c => render(c, { title: 'Page not found — Kaniva Bangladesh', robots: 'noindex' }, () => <NotFound />, 404));
app.onError((err, c) => {
  console.error(err);
  return c.html('<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Something went wrong — Kaniva</title><meta name="robots" content="noindex"><style>body{font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;color:#333;text-align:center;padding:1rem}a{display:inline-block;margin-top:1.5rem;background:#8B2E1C;color:#fff;padding:.7rem 1.4rem;border-radius:.75rem;text-decoration:none;font-weight:700}</style></head><body><div><h1>This page didn\'t load</h1><p>Something went wrong on our end. Please try again in a moment.</p><a href="/">Go home</a></div></body></html>', 500);
});

export default app;
