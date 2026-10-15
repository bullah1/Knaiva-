import { Hono, type Context } from 'hono';
import type { AppEnv, Category, Product, Variant } from './lib/env';
import { getSettings, hydrate, loadProducts, mainImage, minPrice, ORDER_STATUSES, PAYMENT_LABELS, type Coupon } from './lib/db';
import { hashPassword, verifyPassword } from './lib/auth';
import { flash } from './lib/session';
import { bdt, formatDate, slugify } from './lib/util';
import { back, form } from './render';
import { viewCtx, type V } from './views/ctx';
import { AdminLayout, Check, DeleteButton, Field, StatusBadge, Toggle } from './views/admin/layout';
import { Csrf, Icon, Stars } from './views/ui';
import { fullAddress, zoneLabel, type Order, type OrderItem, type Post } from './views/pages';

type C = Context<AppEnv>;
export const admin = new Hono<AppEnv>();

admin.use('*', async (c, next) => {
  const u = c.get('user');
  if (!u) return c.redirect('/auth', 303);
  if (u.role !== 'admin') return c.html('<h1>No admin access</h1>', 403);
  await next();
});

async function page(c: C, title: string, body: (v: V) => unknown, actions?: (v: V) => unknown) {
  const v = await viewCtx(c);
  return c.html(<AdminLayout v={v} title={title} actions={actions?.(v) as never}>{body(v) as never}</AdminLayout>);
}
const fail = (c: C, msg: string | string[], to?: string) => {
  c.get('session').errors = Array.isArray(msg) ? msg : [msg];
  return to ? c.redirect(to, 303) : back(c, '/admin');
};
const num = (s: string | undefined, d = 0) => (s === undefined || s === '' || isNaN(Number(s)) ? d : Number(s));
const urlOrNull = (s?: string) => (s && /^(https?:\/\/|\/)/.test(s) ? s : null);

// ---------------- Dashboard ----------------
admin.get('/', async c => {
  const db = c.env.DB;
  const todayUtc = new Date(Date.UTC(new Date().getUTCFullYear(), new Date().getUTCMonth(), new Date().getUTCDate()) - 6 * 3600_000 + (new Date().getUTCHours() >= 18 ? 86400_000 : 0)).toISOString().replace('T', ' ').slice(0, 19);
  const [stats, low, latest, pending] = await Promise.all([
    db.prepare(`SELECT (SELECT COUNT(*) FROM orders) AS orders, (SELECT COUNT(*) FROM orders WHERE status='pending') AS pending,
      (SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='cancelled') AS revenue, (SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='cancelled' AND created_at >= ?) AS today,
      (SELECT COUNT(*) FROM products) AS products, (SELECT COUNT(*) FROM blog_posts) AS posts`).bind(todayUtc).first<Record<string, number>>(),
    db.prepare('SELECT v.*, p.name AS product_name FROM product_variants v JOIN products p ON p.id = v.product_id WHERE v.stock <= 5 ORDER BY v.stock LIMIT 30').all<Variant & { product_name: string }>(),
    db.prepare('SELECT * FROM orders ORDER BY id DESC LIMIT 8').all<Order>(),
    db.prepare('SELECT COUNT(*) AS n FROM reviews WHERE is_approved = 0').first<{ n: number }>(),
  ]);
  const s = stats!;
  const cards: [string, string, string][] = [['Total orders', String(s.orders), 'receipt'], ['Pending orders', String(s.pending), 'package'], ['Revenue', bdt(s.revenue), 'tag'], ['Today', bdt(s.today), 'award'], ['Products', String(s.products), 'folder'], ['Blog posts', String(s.posts), 'newspaper']];
  return page(c, 'Dashboard', () => (
    <>
      <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
        {cards.map(([l, val, ic]) => <div class="card-soft flex items-center gap-3 p-4"><span class="flex size-10 items-center justify-center rounded-xl bg-cream text-primary"><Icon name={ic} /></span><div><p class="text-xs text-muted-foreground">{l}</p><p class="text-xl font-extrabold text-primary">{val}</p></div></div>)}
      </div>
      {pending!.n ? <a href="/admin/reviews" class="mt-4 block rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm font-semibold text-amber-900">{pending!.n} review(s) waiting for approval →</a> : null}
      <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card-soft p-4"><h2 class="mb-3 font-extrabold">Latest orders</h2>
          <ul class="divide-y divide-border text-sm">{latest.results.length ? latest.results.map(o => <li class="flex flex-wrap items-center justify-between gap-2 py-2"><a href={`/admin/orders/${o.id}`} class="font-semibold text-primary hover:underline">{o.order_number}</a><span class="text-muted-foreground">{o.customer_name}</span><span class="font-bold">{bdt(o.total)}</span><StatusBadge status={o.status} /></li>) : <li class="py-2 text-muted-foreground">No orders yet.</li>}</ul>
        </div>
        <div class="card-soft p-4"><h2 class="mb-3 font-extrabold">Low stock alerts</h2>
          {low.results.length ? <ul class="divide-y divide-border text-sm">{low.results.map(x => <li class="flex justify-between gap-3 py-2"><a href={`/admin/products/${x.product_id}`} class="hover:text-primary">{x.product_name} — {x.weight_label}</a><span class="font-bold text-destructive">{x.stock} left</span></li>)}</ul> : <p class="text-sm text-muted-foreground">All sizes are above 5 units.</p>}
        </div>
      </div>
    </>
  ));
});

// ---------------- Orders ----------------
admin.get('/orders', async c => {
  const db = c.env.DB;
  const status = ORDER_STATUSES.includes(c.req.query('status') as never) ? c.req.query('status')! : '';
  const q = (c.req.query('q') ?? '').trim();
  const where: string[] = []; const binds: unknown[] = [];
  if (status) { where.push('status = ?'); binds.push(status); }
  if (q) { where.push('(order_number LIKE ? OR customer_name LIKE ? OR phone LIKE ?)'); binds.push(`%${q}%`, `%${q}%`, `%${q}%`); }
  const [orders, counts] = await Promise.all([
    db.prepare(`SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS items_count FROM orders o ${where.length ? 'WHERE ' + where.join(' AND ') : ''} ORDER BY o.id DESC LIMIT 200`).bind(...binds).all<Order>(),
    db.prepare('SELECT status, COUNT(*) AS n FROM orders GROUP BY status').all<{ status: string; n: number }>(),
  ]);
  const cnt = Object.fromEntries(counts.results.map(r => [r.status, r.n]));
  const total = counts.results.reduce((a, r) => a + r.n, 0);
  const qs = (s: string) => '/admin/orders?' + new URLSearchParams({ ...(s ? { status: s } : {}), ...(q ? { q } : {}) }).toString();
  return page(c, 'Orders', v => (
    <>
      <div class="no-scrollbar -mx-4 mb-4 flex gap-2 overflow-x-auto px-4">
        <a href={qs('')} class={`chip h-9 ${!status ? 'chip-active' : ''}`}>All ({total})</a>
        {ORDER_STATUSES.map(s => <a href={qs(s)} class={`chip h-9 capitalize ${status === s ? 'chip-active' : ''}`}>{s} ({cnt[s] ?? 0})</a>)}
      </div>
      <form class="mb-4 flex max-w-md gap-2">{status ? <input type="hidden" name="status" value={status} /> : null}<input type="search" name="q" value={q} placeholder="Order number, name or phone" class="input h-10" /><button class="btn btn-outline h-10">Search</button></form>
      <div class="card-soft overflow-x-auto"><table class="w-full min-w-[760px] text-sm">
        <thead class="border-b border-border bg-cream text-left text-xs uppercase text-muted-foreground"><tr><th class="p-3">Order</th><th class="p-3">Customer</th><th class="p-3">Items</th><th class="p-3">Total</th><th class="p-3">Payment</th><th class="p-3">Status</th></tr></thead>
        <tbody class="divide-y divide-border">
          {orders.results.length ? orders.results.map(o => (
            <tr>
              <td class="p-3"><a href={`/admin/orders/${o.id}`} class="font-bold text-primary hover:underline">{o.order_number}</a><p class="text-xs text-muted-foreground">{formatDate(o.created_at)}</p></td>
              <td class="p-3">{o.customer_name}<p class="text-xs text-muted-foreground"><a href={`tel:${o.phone}`}>{o.phone}</a> · {zoneLabel(o.delivery_zone)}</p></td>
              <td class="p-3">{o.items_count}</td><td class="p-3 font-bold">{bdt(o.total)}</td><td class="p-3">{PAYMENT_LABELS[o.payment_method] ?? o.payment_method}</td>
              <td class="p-3"><form method="post" action={`/admin/orders/${o.id}/status`}><Csrf v={v} /><select name="status" onchange="this.form.submit()" class="h-9 rounded-lg border border-border bg-background px-2 text-xs font-semibold capitalize" aria-label={`Status for ${o.order_number}`}>{ORDER_STATUSES.map(s => <option value={s} selected={o.status === s}>{s}</option>)}</select></form></td>
            </tr>
          )) : <tr><td colspan={6} class="p-6 text-center text-muted-foreground">No orders in this view.</td></tr>}
        </tbody>
      </table></div>
    </>
  ));
});

admin.get('/orders/:id', async c => {
  const db = c.env.DB;
  const o = await db.prepare('SELECT * FROM orders WHERE id = ?').bind(c.req.param('id')).first<Order>();
  if (!o) return c.notFound();
  const { results: items } = await db.prepare('SELECT * FROM order_items WHERE order_id = ?').bind(o.id).all<OrderItem>();
  const waNum = o.phone.replace(/\D/g, '').replace(/^0/, '880');
  return page(c, `Order ${o.order_number}`, v => (
    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
      <div class="card-soft p-5"><h2 class="font-extrabold">Items</h2>
        <ul class="mt-3 divide-y divide-border text-sm">{items.map(i => <li class="flex justify-between gap-3 py-2"><span>{i.product_slug ? <a href={`/products/${i.product_slug}`} target="_blank" class="font-semibold hover:text-primary">{i.product_name}</a> : i.product_name} <span class="text-muted-foreground">({i.variant_label}) × {i.quantity} @ {bdt(i.unit_price)}</span></span><span class="font-bold">{bdt(i.line_total)}</span></li>)}</ul>
        <dl class="mt-3 space-y-1 border-t border-border pt-3 text-sm">
          <div class="flex justify-between"><dt>Subtotal</dt><dd>{bdt(o.subtotal)}</dd></div>
          {o.discount > 0 ? <div class="flex justify-between text-success"><dt>Discount ({o.coupon_code})</dt><dd>-{bdt(o.discount)}</dd></div> : null}
          <div class="flex justify-between"><dt>Delivery ({zoneLabel(o.delivery_zone)})</dt><dd>{bdt(o.delivery_charge)}</dd></div>
          <div class="flex justify-between text-base font-extrabold"><dt>Total</dt><dd class="text-primary">{bdt(o.total)}</dd></div>
        </dl>
      </div>
      <div class="space-y-6">
        <div class="card-soft space-y-2 p-5 text-sm"><h2 class="font-extrabold">Customer</h2>
          <p class="font-semibold">{o.customer_name}</p>
          <p><a href={`tel:${o.phone}`} class="text-primary">{o.phone}</a> · <a href={`https://wa.me/${waNum}`} target="_blank" class="text-success">WhatsApp</a></p>
          <p>{fullAddress(o)}</p><p class="text-muted-foreground">Payment: {PAYMENT_LABELS[o.payment_method] ?? o.payment_method}</p>
          {o.notes ? <p class="rounded-lg bg-cream p-2">Note: {o.notes}</p> : null}
          <p class="text-xs text-muted-foreground">Placed {formatDate(o.created_at)}</p>
        </div>
        <form method="post" action={`/admin/orders/${o.id}/status`} class="card-soft space-y-3 p-5"><Csrf v={v} />
          <h2 class="font-extrabold">Status</h2><p><StatusBadge status={o.status} /></p>
          <select name="status" class="input capitalize">{ORDER_STATUSES.map(s => <option value={s} selected={o.status === s}>{s}</option>)}</select>
          <button class="btn btn-primary h-10 w-full">Update status</button>
          <p class="text-xs text-muted-foreground">Cancelling puts the items back into stock.</p>
        </form>
      </div>
    </div>
  ), () => <><a href="/admin/orders" class="btn btn-ghost h-10"><Icon name="arrow-left" /> All orders</a><button onclick="window.print()" class="btn btn-outline h-10">Print</button></>);
});

admin.post('/orders/:id/status', async c => {
  const db = c.env.DB;
  const f = await form(c);
  if (!ORDER_STATUSES.includes(f.status as never)) return fail(c, 'Unknown status');
  const o = await db.prepare('SELECT id, order_number, status FROM orders WHERE id = ?').bind(c.req.param('id')).first<{ id: number; order_number: string; status: string }>();
  if (!o) return c.notFound();
  const stmts = [db.prepare('UPDATE orders SET status = ? WHERE id = ?').bind(f.status, o.id)];
  const sign = o.status !== 'cancelled' && f.status === 'cancelled' ? 1 : o.status === 'cancelled' && f.status !== 'cancelled' ? -1 : 0;
  if (sign) {
    const { results } = await db.prepare('SELECT variant_id, quantity FROM order_items WHERE order_id = ? AND variant_id IS NOT NULL').bind(o.id).all<{ variant_id: number; quantity: number }>();
    for (const i of results) stmts.push(db.prepare('UPDATE product_variants SET stock = MAX(stock + ?, 0) WHERE id = ?').bind(sign * i.quantity, i.variant_id));
  }
  await db.batch(stmts);
  flash(c, 'success', `Order ${o.order_number} marked ${f.status}`);
  return back(c, '/admin/orders');
});

// ---------------- Products ----------------
admin.get('/products', async c => {
  const q = (c.req.query('q') ?? '').trim();
  const products = await loadProducts(c.env.DB, q ? '(p.name LIKE ? OR p.sku LIKE ?)' : '1=1', q ? [`%${q}%`, `%${q}%`] : [], 'ORDER BY p.id DESC');
  const cats = Object.fromEntries((await c.env.DB.prepare('SELECT id, name FROM categories').all<{ id: number; name: string }>()).results.map(x => [x.id, x.name]));
  return page(c, 'Products', v => (
    <>
      <form class="mb-4 flex max-w-md gap-2"><input type="search" name="q" value={q} placeholder="Search name or SKU" class="input h-10" /><button class="btn btn-outline h-10">Search</button></form>
      <div class="card-soft overflow-x-auto"><table class="w-full min-w-[720px] text-sm">
        <thead class="border-b border-border bg-cream text-left text-xs uppercase text-muted-foreground"><tr><th class="p-3">Product</th><th class="p-3">Category</th><th class="p-3">Sizes / price</th><th class="p-3">Stock</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead>
        <tbody class="divide-y divide-border">
          {products.map(p => {
            const stock = p.variants.reduce((a, x) => a + x.stock, 0);
            return (
              <tr>
                <td class="p-3"><div class="flex items-center gap-3"><img src={mainImage(p)} alt="" class="size-12 rounded-lg bg-white object-contain" /><div><a href={`/admin/products/${p.id}`} class="font-bold text-primary hover:underline">{p.name}</a><p class="text-xs text-muted-foreground">/{p.slug}</p></div></div></td>
                <td class="p-3">{p.category_id ? cats[p.category_id] : '—'}</td>
                <td class="p-3">{p.variants.length} · {p.variants.length ? bdt(minPrice(p)) : '—'}</td>
                <td class="p-3"><span class={stock <= 5 ? 'font-bold text-destructive' : ''}>{stock}</span></td>
                <td class="p-3"><div class="flex flex-wrap gap-1"><Toggle v={v} action={`/admin/products/${p.id}/toggle`} field="is_active" on={p.is_active} label="Active" /><Toggle v={v} action={`/admin/products/${p.id}/toggle`} field="is_popular" on={p.is_popular} label="Popular" /></div></td>
                <td class="p-3 text-right"><div class="flex justify-end gap-1"><a href={`/products/${p.slug}`} target="_blank" class="btn-icon" aria-label="View"><Icon name="eye" class="size-4" /></a><a href={`/admin/products/${p.id}`} class="btn-icon" aria-label="Edit"><Icon name="pencil" class="size-4" /></a></div></td>
              </tr>
            );
          })}
        </tbody>
      </table></div>
    </>
  ), () => <a href="/admin/products/new" class="btn btn-primary h-10"><Icon name="plus" /> Add product</a>);
});

admin.post('/products/:id/toggle', async c => {
  const f = await form(c);
  if (!['is_active', 'is_popular'].includes(f.field)) return fail(c, 'Bad field');
  await c.env.DB.prepare(`UPDATE products SET ${f.field} = 1 - ${f.field}, updated_at = datetime('now') WHERE id = ?`).bind(c.req.param('id')).run();
  flash(c, 'success', 'Product updated');
  return back(c, '/admin/products');
});

async function productForm(c: C, p: Product | null) {
  const cats = (await c.env.DB.prepare('SELECT id, name FROM categories ORDER BY sort_order').all<{ id: number; name: string }>()).results;
  const variants = p?.variants.length ? p.variants.map(x => ({ id: x.id, weight_label: x.weight_label, sku: x.sku ?? '', mrp: x.mrp ?? '', price: x.price, stock: x.stock })) : [{ id: null, weight_label: '1kg', sku: '', mrp: '', price: '', stock: 0 }];
  return page(c, p ? 'Edit product' : 'New product', v => (
    <form method="post" action={p ? `/admin/products/${p.id}` : '/admin/products/new'} class="grid gap-6 lg:grid-cols-[1fr_320px]"><Csrf v={v} />
      <div class="space-y-6">
        <section class="card-soft grid gap-4 p-5 md:grid-cols-2">
          <h2 class="font-extrabold md:col-span-2">Basic info</h2>
          <Field label="Name (English)" name="name" value={p?.name} required />
          <Field label="Name (বাংলা)" name="name_bn" value={p?.name_bn} />
          <Field label="URL slug" name="slug" value={p?.slug} ph="auto from name" />
          <Field label="SKU" name="sku" value={p?.sku} />
          <div><label class="label">Category</label><select name="category_id" class="input"><option value="">None</option>{cats.map(x => <option value={String(x.id)} selected={p?.category_id === x.id}>{x.name}</option>)}</select></div>
          <Field label="Brand" name="brand" value={p?.brand ?? 'Kaniva'} />
          <Field label="Life stage" name="life_stage" value={p?.life_stage} ph="Kitten, Adult, Senior" />
          <Field label="Food type" name="food_type" value={p?.food_type} ph="dry, wet, treat" />
          <Field label="Flavour" name="flavor" value={p?.flavor} />
          <Field label="Tags (comma separated)" name="tags" value={p?.tags.join(', ')} hint="Used by shop filters: kitten, adult, senior, indoor, urinary care, skin & coat, hairball, grain free, jelly, gravy, chicken, tuna…" />
          <Field label="Short description" name="short_description" value={p?.short_description} area rows={2} span />
          <Field label="এক নজরে box (one per line, “label: value”)" name="quick_info" value={p?.quick_info.map(([a, b]) => `${a}: ${b}`).join('\n')} area rows={4} span />
          <Field label="Key benefits (one per line)" name="features" value={p?.features.join('\n')} area rows={4} span />
        </section>
        <section class="card-soft p-5" x-data={`{ rows: ${JSON.stringify(variants)} }`}>
          <div class="flex items-center justify-between"><h2 class="font-extrabold">Sizes &amp; prices</h2><button type="button" class="btn btn-outline h-9" {...{ '@click': "rows.push({ id: null, weight_label: '', sku: '', mrp: '', price: '', stock: 0 })" }}><Icon name="plus" /> Add size</button></div>
          <div class="mt-4 space-y-3">
            <template x-for="(row, i) in rows" {...{ ':key': 'i' }}>
              <div class="grid grid-cols-2 gap-2 rounded-xl bg-cream p-3 sm:grid-cols-[1fr_1fr_.8fr_.8fr_.7fr_auto] sm:items-end">
                <input type="hidden" {...{ ':name': '`v_id_${i}`', ':value': 'row.id ?? ""' }} />
                <div><label class="label text-xs">Weight *</label><input class="input h-10" required x-model="row.weight_label" {...{ ':name': '`v_label_${i}`' }} placeholder="1kg" /></div>
                <div><label class="label text-xs">SKU</label><input class="input h-10" x-model="row.sku" {...{ ':name': '`v_sku_${i}`' }} /></div>
                <div><label class="label text-xs">MRP ৳</label><input class="input h-10" type="number" step="any" min="0" x-model="row.mrp" {...{ ':name': '`v_mrp_${i}`' }} /></div>
                <div><label class="label text-xs">Price ৳ *</label><input class="input h-10" type="number" step="any" min="0" required x-model="row.price" {...{ ':name': '`v_price_${i}`' }} /></div>
                <div><label class="label text-xs">Stock *</label><input class="input h-10" type="number" min="0" required x-model="row.stock" {...{ ':name': '`v_stock_${i}`' }} /></div>
                <button type="button" class="btn btn-danger h-10" aria-label="Remove size" {...{ '@click': 'rows.splice(i, 1)' }}><Icon name="trash-2" /></button>
              </div>
            </template>
            <input type="hidden" name="v_count" {...{ ':value': 'rows.length' }} value={String(variants.length)} />
            <p x-show="rows.length === 0" class="text-sm text-muted-foreground">No sizes — customers cannot buy this product until you add one.</p>
          </div>
        </section>
        <section class="card-soft grid gap-4 p-5">
          <h2 class="font-extrabold">Details</h2>
          <Field label="Description (English)" name="description" value={p?.description} area rows={5} />
          <Field label="Description (বাংলা)" name="description_bn" value={p?.description_bn} area rows={5} />
          <Field label="Ingredients" name="ingredients" value={p?.ingredients} area />
          <Field label="Nutrition" name="nutrition" value={p?.nutrition} area />
          <Field label="Feeding guide" name="feeding_guide" value={p?.feeding_guide} area />
        </section>
        <section class="card-soft grid gap-4 p-5 md:grid-cols-2">
          <h2 class="font-extrabold md:col-span-2">SEO</h2>
          <Field label="SEO title (50–60 characters)" name="seo_title" value={p?.seo_title} span />
          <Field label="SEO description (150–160 characters)" name="seo_description" value={p?.seo_description} area rows={2} span />
          <Field label="SEO keywords" name="seo_keywords" value={p?.seo_keywords} />
          <Field label="Canonical URL (optional)" name="canonical_url" value={p?.canonical_url} />
        </section>
      </div>
      <div class="space-y-6">
        <section class="card-soft space-y-3 p-5 lg:sticky lg:top-6">
          <Check name="is_active" checked={p ? p.is_active : 1} label="Active (visible in shop)" />
          <Check name="is_popular" checked={p?.is_popular ?? 0} label="Popular (show on homepage)" />
          <div class="grid grid-cols-2 gap-2"><Field label="Rating" name="rating" type="number" value={p?.rating ?? 0} /><Field label="Review count" name="review_count" type="number" value={p?.review_count ?? 0} /></div>
          <button type="submit" class="btn btn-primary h-11 w-full">Save product</button>
        </section>
        <section class="card-soft p-5">
          <h2 class="font-extrabold">Images</h2>
          <p class="mt-1 text-xs text-muted-foreground">One image link per line. The first is the main photo. Upload photos to your Facebook page, Google Drive (public link), or Cloudflare R2 and paste the link.</p>
          {p?.images.length ? <div class="mt-3 grid grid-cols-3 gap-2">{p.images.map(i => <img src={i} alt="" class="aspect-square w-full rounded-lg bg-white object-contain" />)}</div> : null}
          <textarea name="images" rows={6} class="textarea mt-3 font-mono text-xs">{p?.images.join('\n') ?? ''}</textarea>
        </section>
      </div>
    </form>
  ), v => <>
    <a href="/admin/products" class="btn btn-ghost h-10"><Icon name="arrow-left" /> All products</a>
    {p ? <a href={`/products/${p.slug}`} target="_blank" class="btn btn-outline h-10"><Icon name="eye" /> View</a> : null}
    {p ? <DeleteButton v={v} action={`/admin/products/${p.id}/delete`} label="Delete" confirm="Delete this product? Products with orders are hidden instead." /> : null}
  </>);
}

admin.get('/products/new', c => productForm(c, null));
admin.get('/products/:id', async c => {
  const [p] = await loadProducts(c.env.DB, 'p.id = ?', [c.req.param('id')], '');
  return p ? productForm(c, p) : c.notFound();
});

async function saveProduct(c: C, id: number | null) {
  const db = c.env.DB;
  const f = await form(c);
  const errors: string[] = [];
  if (!f.name) errors.push('Name is required.');
  const slug = slugify(f.slug || f.name || '');
  const clash = await db.prepare('SELECT id FROM products WHERE slug = ? AND id != ?').bind(slug, id ?? 0).first();
  if (clash) errors.push(`The URL slug "${slug}" is already used by another product.`);
  const count = Math.min(num(f.v_count), 30);
  const variants: { id: number | null; weight_label: string; sku: string | null; mrp: number | null; price: number; stock: number }[] = [];
  for (let i = 0; i < 60 && variants.length < count; i++) {
    if (f[`v_label_${i}`] === undefined) continue;
    if (!f[`v_label_${i}`] || f[`v_price_${i}`] === '') { errors.push('Every size needs a weight label and a price.'); break; }
    variants.push({ id: num(f[`v_id_${i}`]) || null, weight_label: f[`v_label_${i}`], sku: f[`v_sku_${i}`] || null, mrp: f[`v_mrp_${i}`] ? num(f[`v_mrp_${i}`]) : null, price: num(f[`v_price_${i}`]), stock: Math.max(0, Math.round(num(f[`v_stock_${i}`]))) });
  }
  if (errors.length) return fail(c, errors);

  const lines = (s?: string) => (s ?? '').split(/\r?\n/).map(x => x.trim()).filter(Boolean);
  const vals = {
    slug, name: f.name, name_bn: f.name_bn || null, sku: f.sku || null, brand: f.brand || 'Kaniva', category_id: f.category_id ? num(f.category_id) : null,
    life_stage: f.life_stage || null, food_type: f.food_type || null, flavor: f.flavor || null,
    tags: JSON.stringify([...new Set((f.tags ?? '').split(',').map(t => t.trim().toLowerCase()).filter(Boolean))]),
    short_description: f.short_description || null, description: f.description || null, description_bn: f.description_bn || null,
    ingredients: f.ingredients || null, nutrition: f.nutrition || null, feeding_guide: f.feeding_guide || null,
    quick_info: JSON.stringify(lines(f.quick_info).map(l => l.split(/:(.*)/s).map(x => x.trim())).filter(x => x[0] && x[1]).map(x => [x[0], x[1]])),
    features: JSON.stringify(lines(f.features)), images: JSON.stringify(lines(f.images).filter(x => urlOrNull(x))),
    rating: Math.min(5, Math.max(0, num(f.rating))), review_count: Math.max(0, Math.round(num(f.review_count))),
    is_active: f.is_active ? 1 : 0, is_popular: f.is_popular ? 1 : 0,
    seo_title: f.seo_title || null, seo_description: f.seo_description || null, seo_keywords: f.seo_keywords || null, canonical_url: urlOrNull(f.canonical_url),
  };
  const cols = Object.keys(vals);
  if (id) {
    await db.prepare(`UPDATE products SET ${cols.map(k => `${k} = ?`).join(', ')}, updated_at = datetime('now') WHERE id = ?`).bind(...Object.values(vals), id).run();
  } else {
    id = (await db.prepare(`INSERT INTO products (${cols.join(', ')}) VALUES (${cols.map(() => '?').join(', ')}) RETURNING id`).bind(...Object.values(vals)).first<{ id: number }>())!.id;
  }
  const keep = variants.filter(x => x.id).map(x => x.id);
  const stmts = [db.prepare(`DELETE FROM product_variants WHERE product_id = ? ${keep.length ? `AND id NOT IN (${keep.join(',')})` : ''}`).bind(id)];
  variants.forEach((x, i) => stmts.push(x.id
    ? db.prepare('UPDATE product_variants SET weight_label = ?, sku = ?, mrp = ?, price = ?, stock = ?, sort_order = ? WHERE id = ? AND product_id = ?').bind(x.weight_label, x.sku, x.mrp, x.price, x.stock, i + 1, x.id, id)
    : db.prepare('INSERT INTO product_variants (product_id, weight_label, sku, mrp, price, stock, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)').bind(id, x.weight_label, x.sku, x.mrp, x.price, x.stock, i + 1)));
  await db.batch(stmts);
  flash(c, 'success', 'Product saved');
  return c.redirect(`/admin/products/${id}`, 303);
}
admin.post('/products/new', c => saveProduct(c, null));
admin.post('/products/:id', c => saveProduct(c, Number(c.req.param('id'))));
admin.post('/products/:id/delete', async c => {
  const db = c.env.DB;
  const id = c.req.param('id');
  const used = await db.prepare('SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1').bind(id).first();
  if (used) { await db.prepare('UPDATE products SET is_active = 0 WHERE id = ?').bind(id).run(); flash(c, 'success', 'This product has orders, so it was hidden instead of deleted.'); }
  else { await db.batch([db.prepare('DELETE FROM product_variants WHERE product_id = ?').bind(id), db.prepare('DELETE FROM reviews WHERE product_id = ?').bind(id), db.prepare('DELETE FROM products WHERE id = ?').bind(id)]); flash(c, 'success', 'Product deleted'); }
  return c.redirect('/admin/products', 303);
});

// ---------------- Categories ----------------
admin.get('/categories', async c => {
  const { results } = await c.env.DB.prepare('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY sort_order').all<Category>();
  return page(c, 'Categories', v => (
    <div class="card-soft divide-y divide-border">
      {results.map(x => (
        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
          <div><p class="font-bold">{x.name} <span class="font-normal text-muted-foreground">{x.name_bn}</span></p><p class="text-xs text-muted-foreground">/category/{x.slug} · {x.product_count} products · order {x.sort_order}</p></div>
          <div class="flex items-center gap-2"><span class={`text-xs font-bold ${x.is_active ? 'text-success' : 'text-muted-foreground'}`}>{x.is_active ? 'Active' : 'Hidden'}</span><a href={`/admin/categories/${x.id}`} class="btn-icon" aria-label="Edit"><Icon name="pencil" class="size-4" /></a></div>
        </div>
      ))}
    </div>
  ), () => <a href="/admin/categories/new" class="btn btn-primary h-10"><Icon name="plus" /> Add category</a>);
});

async function categoryForm(c: C, x: Category | null) {
  return page(c, x ? 'Edit category' : 'New category', v => (
    <form method="post" action={x ? `/admin/categories/${x.id}` : '/admin/categories/new'} class="card-soft grid max-w-3xl gap-4 p-5 md:grid-cols-2"><Csrf v={v} />
      <Field label="Name (English)" name="name" value={x?.name} required /><Field label="Name (বাংলা)" name="name_bn" value={x?.name_bn} />
      <Field label="Slug" name="slug" value={x?.slug} ph="auto from name" /><Field label="Sort order" name="sort_order" type="number" value={x?.sort_order ?? 0} />
      <Field label="Intro text (English) — shown under the heading, good for SEO" name="description" value={x?.description} area rows={2} span />
      <Field label="Intro text (বাংলা)" name="description_bn" value={x?.description_bn} area rows={2} span />
      <Field label="Image link (optional — otherwise the first product photo is used)" name="image_url" value={x?.image_url} span />
      <Field label="SEO title" name="seo_title" value={x?.seo_title} span /><Field label="SEO description" name="seo_description" value={x?.seo_description} area rows={2} span />
      <Field label="SEO keywords" name="seo_keywords" value={x?.seo_keywords} span />
      <div class="md:col-span-2"><Check name="is_active" checked={x ? x.is_active : 1} label="Active" /></div>
      <button class="btn btn-primary h-11 md:col-span-2">Save category</button>
    </form>
  ), v => <><a href="/admin/categories" class="btn btn-ghost h-10"><Icon name="arrow-left" /> All categories</a>{x ? <DeleteButton v={v} action={`/admin/categories/${x.id}/delete`} confirm="Delete this category? Its products become uncategorised." /> : null}</>);
}
admin.get('/categories/new', c => categoryForm(c, null));
admin.get('/categories/:id', async c => {
  const x = await c.env.DB.prepare('SELECT * FROM categories WHERE id = ?').bind(c.req.param('id')).first<Category>();
  return x ? categoryForm(c, x) : c.notFound();
});
async function saveCategory(c: C, id: number | null) {
  const db = c.env.DB;
  const f = await form(c);
  if (!f.name) return fail(c, 'Name is required.');
  const slug = slugify(f.slug || f.name);
  if (await db.prepare('SELECT id FROM categories WHERE slug = ? AND id != ?').bind(slug, id ?? 0).first()) return fail(c, `Slug "${slug}" is already used.`);
  const vals = [slug, f.name, f.name_bn || null, f.description || null, f.description_bn || null, urlOrNull(f.image_url), Math.round(num(f.sort_order)), f.is_active ? 1 : 0, f.seo_title || null, f.seo_description || null, f.seo_keywords || null];
  if (id) await db.prepare(`UPDATE categories SET slug=?, name=?, name_bn=?, description=?, description_bn=?, image_url=?, sort_order=?, is_active=?, seo_title=?, seo_description=?, seo_keywords=?, updated_at=datetime('now') WHERE id=?`).bind(...vals, id).run();
  else await db.prepare('INSERT INTO categories (slug, name, name_bn, description, description_bn, image_url, sort_order, is_active, seo_title, seo_description, seo_keywords) VALUES (?,?,?,?,?,?,?,?,?,?,?)').bind(...vals).run();
  flash(c, 'success', 'Category saved');
  return c.redirect('/admin/categories', 303);
}
admin.post('/categories/new', c => saveCategory(c, null));
admin.post('/categories/:id', c => saveCategory(c, Number(c.req.param('id'))));
admin.post('/categories/:id/delete', async c => {
  const db = c.env.DB;
  await db.batch([db.prepare('UPDATE products SET category_id = NULL WHERE category_id = ?').bind(c.req.param('id')), db.prepare('DELETE FROM categories WHERE id = ?').bind(c.req.param('id'))]);
  flash(c, 'success', 'Category deleted');
  return c.redirect('/admin/categories', 303);
});

// ---------------- Coupons ----------------
admin.get('/coupons', async c => {
  const { results } = await c.env.DB.prepare('SELECT * FROM coupons ORDER BY id DESC').all<Coupon>();
  return page(c, 'Coupons', v => (
    <>
      <form method="post" action="/admin/coupons" class="card-soft grid gap-3 p-5 md:grid-cols-3"><Csrf v={v} />
        <h2 class="font-extrabold md:col-span-3">New coupon</h2>
        <Field label="Code" name="code" required ph="SAVE10" />
        <div><label class="label">Type</label><select name="discount_type" class="input"><option value="percent">Percent (%)</option><option value="fixed">Fixed (৳)</option></select></div>
        <Field label="Value" name="discount_value" type="number" value={10} required />
        <Field label="Min order (৳)" name="min_order_value" type="number" value={0} />
        <Field label="Expires on" name="expires_at" type="date" />
        <Field label="Usage limit" name="usage_limit" type="number" ph="Unlimited" />
        <button class="btn btn-primary h-11 md:col-span-3">Create coupon</button>
      </form>
      <div class="mt-6 space-y-3">
        {results.length ? results.map(x => (
          <div class="card-soft flex flex-wrap items-center justify-between gap-3 p-4">
            <div><p class="font-extrabold text-primary">{x.code}</p>
              <p class="text-xs text-muted-foreground">{x.discount_type === 'percent' ? `${x.discount_value}%` : bdt(x.discount_value)} · min {bdt(x.min_order_value)} · used {x.used_count}{x.usage_limit ? `/${x.usage_limit}` : ''}{x.expires_at ? ` · expires ${x.expires_at}` : ''}</p></div>
            <div class="flex items-center gap-2"><Toggle v={v} action={`/admin/coupons/${x.id}/toggle`} on={x.is_active} label="Active" />
              <form method="post" action={`/admin/coupons/${x.id}/delete`} onsubmit={`return confirm('Delete ${x.code}?')`}><Csrf v={v} /><button class="btn-icon text-destructive" aria-label="Delete"><Icon name="trash-2" class="size-4" /></button></form></div>
          </div>
        )) : <p class="text-sm text-muted-foreground">No coupons yet.</p>}
      </div>
    </>
  ));
});
admin.post('/coupons', async c => {
  const f = await form(c);
  const code = (f.code ?? '').toUpperCase().replace(/\s+/g, '');
  const value = num(f.discount_value);
  const type = f.discount_type === 'fixed' ? 'fixed' : 'percent';
  if (!/^[A-Z0-9_-]{2,40}$/.test(code)) return fail(c, 'Code must be 2–40 letters, numbers, - or _.');
  if (value <= 0 || (type === 'percent' && value > 100)) return fail(c, 'Enter a valid discount value.');
  if (await c.env.DB.prepare('SELECT 1 FROM coupons WHERE code = ? COLLATE NOCASE').bind(code).first()) return fail(c, 'That code already exists.');
  await c.env.DB.prepare('INSERT INTO coupons (code, discount_type, discount_value, min_order_value, expires_at, usage_limit) VALUES (?, ?, ?, ?, ?, ?)').bind(code, type, value, Math.max(0, num(f.min_order_value)), /^\d{4}-\d{2}-\d{2}$/.test(f.expires_at ?? '') ? f.expires_at : null, f.usage_limit ? Math.max(1, Math.round(num(f.usage_limit))) : null).run();
  flash(c, 'success', 'Coupon created');
  return c.redirect('/admin/coupons', 303);
});
admin.post('/coupons/:id/toggle', async c => { await c.env.DB.prepare('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?').bind(c.req.param('id')).run(); flash(c, 'success', 'Coupon updated'); return c.redirect('/admin/coupons', 303); });
admin.post('/coupons/:id/delete', async c => { await c.env.DB.prepare('DELETE FROM coupons WHERE id = ?').bind(c.req.param('id')).run(); flash(c, 'success', 'Coupon deleted'); return c.redirect('/admin/coupons', 303); });

// ---------------- Blog ----------------
admin.get('/blog', async c => {
  const { results } = await c.env.DB.prepare('SELECT * FROM blog_posts ORDER BY published_at DESC').all<Post>();
  return page(c, 'Blog', () => (
    <div class="card-soft divide-y divide-border">
      {results.length ? results.map(x => (
        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
          <div><a href={`/admin/blog/${x.id}`} class="font-bold text-primary hover:underline">{x.title}</a><p class="text-xs text-muted-foreground">/blog/{x.slug} · {x.category || 'Uncategorised'} · {formatDate(x.published_at)}</p></div>
          <div class="flex items-center gap-2"><span class={`text-xs font-bold ${x.is_published ? 'text-success' : 'text-muted-foreground'}`}>{x.is_published ? 'Published' : 'Draft'}</span>{x.is_published ? <a href={`/blog/${x.slug}`} target="_blank" class="btn-icon" aria-label="View"><Icon name="eye" class="size-4" /></a> : null}<a href={`/admin/blog/${x.id}`} class="btn-icon" aria-label="Edit"><Icon name="pencil" class="size-4" /></a></div>
        </div>
      )) : <p class="p-6 text-center text-muted-foreground">No posts yet.</p>}
    </div>
  ), () => <a href="/admin/blog/new" class="btn btn-primary h-10"><Icon name="plus" /> New post</a>);
});
async function postForm(c: C, x: Post | null) {
  return page(c, x ? 'Edit post' : 'New post', v => (
    <form method="post" action={x ? `/admin/blog/${x.id}` : '/admin/blog/new'} class="grid gap-6 lg:grid-cols-[1fr_300px]"><Csrf v={v} />
      <div class="card-soft grid gap-4 p-5">
        <Field label="Title" name="title" value={x?.title} required />
        <Field label="Excerpt" name="excerpt" value={x?.excerpt} area rows={2} />
        <div><label class="label">Content</label><textarea name="content" rows={16} class="textarea font-mono text-sm">{x?.content ?? ''}</textarea>
          <p class="mt-1 text-xs text-muted-foreground">Start a line with <code>## </code> for a heading, <code>### </code> for a sub-heading and <code>- </code> for a bullet. Blank lines separate paragraphs.</p></div>
        <h2 class="pt-2 font-extrabold">SEO</h2>
        <Field label="SEO title" name="seo_title" value={x?.seo_title} />
        <Field label="SEO description" name="seo_description" value={x?.seo_description} area rows={2} />
        <Field label="SEO keywords" name="seo_keywords" value={x?.seo_keywords} />
      </div>
      <div class="card-soft h-fit space-y-3 p-5">
        <Check name="is_published" checked={x ? x.is_published : 1} label="Published" />
        <Field label="Slug" name="slug" value={x?.slug} ph="auto from title" />
        <Field label="Category" name="category" value={x?.category} ph="Pet Tips" />
        <Field label="Author" name="author" value={x?.author ?? 'Kaniva Bangladesh'} />
        <Field label="Publish date" name="published_at" type="date" value={x?.published_at?.slice(0, 10) ?? new Date().toISOString().slice(0, 10)} />
        <Field label="Cover image link" name="cover_url" value={x?.cover_url} />
        <button class="btn btn-primary h-11 w-full">Save post</button>
      </div>
    </form>
  ), v => <><a href="/admin/blog" class="btn btn-ghost h-10"><Icon name="arrow-left" /> All posts</a>{x ? <DeleteButton v={v} action={`/admin/blog/${x.id}/delete`} confirm="Delete this post?" /> : null}</>);
}
admin.get('/blog/new', c => postForm(c, null));
admin.get('/blog/:id', async c => {
  const x = await c.env.DB.prepare('SELECT * FROM blog_posts WHERE id = ?').bind(c.req.param('id')).first<Post>();
  return x ? postForm(c, x) : c.notFound();
});
async function savePost(c: C, id: number | null) {
  const db = c.env.DB;
  const f = await form(c);
  if (!f.title) return fail(c, 'Title is required.');
  const slug = slugify(f.slug || f.title);
  if (await db.prepare('SELECT id FROM blog_posts WHERE slug = ? AND id != ?').bind(slug, id ?? 0).first()) return fail(c, `Slug "${slug}" is already used.`);
  const date = /^\d{4}-\d{2}-\d{2}$/.test(f.published_at ?? '') ? `${f.published_at} 00:00:00` : new Date().toISOString().replace('T', ' ').slice(0, 19);
  const vals = [slug, f.title, f.excerpt || null, f.content || null, urlOrNull(f.cover_url), f.author || 'Kaniva Bangladesh', f.category || null, f.is_published ? 1 : 0, date, f.seo_title || null, f.seo_description || null, f.seo_keywords || null];
  if (id) await db.prepare(`UPDATE blog_posts SET slug=?, title=?, excerpt=?, content=?, cover_url=?, author=?, category=?, is_published=?, published_at=?, seo_title=?, seo_description=?, seo_keywords=?, updated_at=datetime('now') WHERE id=?`).bind(...vals, id).run();
  else id = (await db.prepare('INSERT INTO blog_posts (slug, title, excerpt, content, cover_url, author, category, is_published, published_at, seo_title, seo_description, seo_keywords) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) RETURNING id').bind(...vals).first<{ id: number }>())!.id;
  flash(c, 'success', 'Post saved');
  return c.redirect(`/admin/blog/${id}`, 303);
}
admin.post('/blog/new', c => savePost(c, null));
admin.post('/blog/:id', c => savePost(c, Number(c.req.param('id'))));
admin.post('/blog/:id/delete', async c => { await c.env.DB.prepare('DELETE FROM blog_posts WHERE id = ?').bind(c.req.param('id')).run(); flash(c, 'success', 'Post deleted'); return c.redirect('/admin/blog', 303); });

// ---------------- Reviews ----------------
type ReviewRow = { id: number; product_id: number | null; product_name: string | null; customer_name: string; rating: number; body: string | null; is_approved: number; is_featured: number; created_at: string };
admin.get('/reviews', async c => {
  const db = c.env.DB;
  const [reviews, products] = await Promise.all([
    db.prepare('SELECT r.*, p.name AS product_name FROM reviews r LEFT JOIN products p ON p.id = r.product_id ORDER BY r.is_approved, r.id DESC').all<ReviewRow>(),
    db.prepare('SELECT id, name FROM products ORDER BY name').all<{ id: number; name: string }>(),
  ]);
  return page(c, 'Reviews', v => (
    <>
      <form method="post" action="/admin/reviews" class="card-soft grid gap-3 p-5 md:grid-cols-4"><Csrf v={v} />
        <h2 class="font-extrabold md:col-span-4">Add a customer review</h2>
        <div class="md:col-span-2"><label class="label">Product</label><select name="product_id" class="input"><option value="">General (no product)</option>{products.results.map(p => <option value={String(p.id)}>{p.name}</option>)}</select></div>
        <Field label="Customer name" name="customer_name" required />
        <div><label class="label">Rating</label><select name="rating" class="input">{[5, 4, 3, 2, 1].map(i => <option value={String(i)}>{i} ★</option>)}</select></div>
        <div class="md:col-span-4"><label class="label">Review</label><textarea name="body" rows={2} class="textarea"></textarea></div>
        <div class="md:col-span-3"><Check name="is_featured" checked={0} label="Feature on homepage" /></div>
        <button class="btn btn-primary h-10">Add review</button>
      </form>
      <div class="mt-6 space-y-3">
        {reviews.results.length ? reviews.results.map(r => (
          <div class={`card-soft flex flex-wrap items-start justify-between gap-3 p-4 ${r.is_approved ? '' : 'border-amber-300'}`}>
            <div class="max-w-2xl"><p class="flex items-center gap-2 font-bold"><Stars rating={r.rating} /> {r.customer_name}</p><p class="mt-1 text-sm">{r.body}</p><p class="mt-1 text-xs text-muted-foreground">{r.product_name ?? 'General'} · {formatDate(r.created_at)}</p></div>
            <div class="flex items-center gap-2">
              <Toggle v={v} action={`/admin/reviews/${r.id}/toggle`} field="is_approved" on={r.is_approved} label="Approved" />
              <Toggle v={v} action={`/admin/reviews/${r.id}/toggle`} field="is_featured" on={r.is_featured} label="Featured" />
              <form method="post" action={`/admin/reviews/${r.id}/delete`} onsubmit="return confirm('Delete this review?')"><Csrf v={v} /><button class="btn-icon text-destructive" aria-label="Delete"><Icon name="trash-2" class="size-4" /></button></form>
            </div>
          </div>
        )) : <p class="text-sm text-muted-foreground">No reviews yet.</p>}
      </div>
    </>
  ));
});
const syncRating = (db: D1Database, pid: number | null) => pid
  ? db.prepare(`UPDATE products SET rating = COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE product_id = ?1 AND is_approved = 1), 0), review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ?1 AND is_approved = 1) WHERE id = ?1`).bind(pid).run()
  : Promise.resolve();
admin.post('/reviews', async c => {
  const f = await form(c);
  if (!f.customer_name) return fail(c, 'Customer name is required.');
  const pid = f.product_id ? num(f.product_id) : null;
  await c.env.DB.prepare('INSERT INTO reviews (product_id, customer_name, rating, body, is_approved, is_featured) VALUES (?, ?, ?, ?, 1, ?)').bind(pid, f.customer_name, Math.min(5, Math.max(1, Math.round(num(f.rating, 5)))), f.body || null, f.is_featured ? 1 : 0).run();
  await syncRating(c.env.DB, pid);
  flash(c, 'success', 'Review added');
  return c.redirect('/admin/reviews', 303);
});
admin.post('/reviews/:id/toggle', async c => {
  const f = await form(c);
  if (!['is_approved', 'is_featured'].includes(f.field)) return fail(c, 'Bad field');
  const r = await c.env.DB.prepare(`UPDATE reviews SET ${f.field} = 1 - ${f.field} WHERE id = ? RETURNING product_id`).bind(c.req.param('id')).first<{ product_id: number | null }>();
  await syncRating(c.env.DB, r?.product_id ?? null);
  flash(c, 'success', 'Review updated');
  return c.redirect('/admin/reviews', 303);
});
admin.post('/reviews/:id/delete', async c => {
  const r = await c.env.DB.prepare('DELETE FROM reviews WHERE id = ? RETURNING product_id').bind(c.req.param('id')).first<{ product_id: number | null }>();
  await syncRating(c.env.DB, r?.product_id ?? null);
  flash(c, 'success', 'Review deleted');
  return c.redirect('/admin/reviews', 303);
});

// ---------------- Homepage content ----------------
const TRUST_ICONS: Record<string, string> = { 'wheat-off': 'Grain free', 'flask-conical': 'Lab tested', sprout: 'Natural', 'shield-check': 'Quality', 'paw-print': 'Paw', award: 'Award' };
type Row = Record<string, string | number | null> & { id: number };
admin.get('/homepage', async c => {
  const db = c.env.DB;
  const [b, t, ce] = await Promise.all(['hero_banners', 'trust_points', 'certificates'].map(tb => db.prepare(`SELECT * FROM ${tb} ORDER BY sort_order, id`).all<Row>()));
  const block = (v: V, type: string, rows: Row[], blank: Row, fields: (r: Row) => unknown, title: string, note?: string) => (
    <section class="mb-10">
      <h2 class="mb-1 text-lg font-extrabold">{title}</h2>{note ? <p class="mb-3 text-sm text-muted-foreground">{note}</p> : null}
      <div class="grid gap-4 md:grid-cols-2">
        {[...rows, blank].map(r => (
          <form method="post" action={r.id ? `/admin/homepage/${type}/${r.id}` : `/admin/homepage/${type}`} class={`card-soft grid gap-3 p-4 sm:grid-cols-2 ${r.id ? '' : 'border-dashed'}`}><Csrf v={v} />
            {r.id ? null : <p class="font-bold text-primary sm:col-span-2">Add new</p>}
            {fields(r) as never}
            <div class="flex items-end gap-3"><div class="w-24"><Field label="Order" name="sort_order" type="number" value={r.sort_order ?? 0} /></div><div class="mb-3"><Check name="is_active" checked={r.id ? Number(r.is_active) : 1} label="Active" /></div></div>
            <div class="flex gap-2 sm:col-span-2"><button class="btn btn-primary h-10">{r.id ? 'Save' : 'Add'}</button>
              {r.id ? <button type="submit" formaction={`/admin/homepage/${type}/${r.id}/delete`} class="btn btn-danger h-10" onclick="return confirm('Delete?')">Delete</button> : null}</div>
          </form>
        ))}
      </div>
    </section>
  );
  return page(c, 'Homepage content', v => (
    <>
      {block(v, 'banner', b.results, { id: 0, cta_label: 'Shop Now', cta_link: '/shop' } as Row, r => <>
        <Field label="Title (EN)" name="title" value={r.title as string} required /><Field label="Title (বাংলা)" name="title_bn" value={r.title_bn as string} />
        <Field label="Subtitle (EN)" name="subtitle" value={r.subtitle as string} area rows={2} /><Field label="Subtitle (বাংলা)" name="subtitle_bn" value={r.subtitle_bn as string} area rows={2} />
        <Field label="Button label" name="cta_label" value={r.cta_label as string} /><Field label="Button link" name="cta_link" value={r.cta_link as string} />
        <Field label="Image link (1600×1000)" name="image_url" value={r.image_url as string} span />
      </>, 'Hero banner', 'The first active banner is shown at the top of the homepage.')}
      {block(v, 'trust', t.results, { id: 0, icon: 'paw-print' } as Row, r => <>
        <Field label="Title (EN)" name="title" value={r.title as string} required /><Field label="Title (বাংলা)" name="title_bn" value={r.title_bn as string} />
        <Field label="Text (EN)" name="description" value={r.description as string} /><Field label="Text (বাংলা)" name="description_bn" value={r.description_bn as string} />
        <div><label class="label">Icon</label><select name="icon" class="input">{Object.entries(TRUST_ICONS).map(([k, l]) => <option value={k} selected={r.icon === k}>{l}</option>)}</select></div>
      </>, 'Why choose us')}
      {block(v, 'certificate', ce.results, { id: 0 } as Row, r => <>
        <Field label="Title" name="title" value={r.title as string} required /><Field label="Logo link (optional)" name="image_url" value={r.image_url as string} />
      </>, 'Certificates')}
    </>
  ));
});
const HOME_TABLES: Record<string, [string, string[]]> = {
  banner: ['hero_banners', ['title', 'title_bn', 'subtitle', 'subtitle_bn', 'cta_label', 'cta_link', 'image_url']],
  trust: ['trust_points', ['title', 'title_bn', 'description', 'description_bn', 'icon']],
  certificate: ['certificates', ['title', 'image_url']],
};
admin.post('/homepage/:type/:id?', async c => {
  const conf = HOME_TABLES[c.req.param('type')];
  if (!conf) return c.notFound();
  const [table, cols] = conf;
  const f = await form(c);
  if (!f.title) return fail(c, 'Title is required.');
  if (f.icon !== undefined && !TRUST_ICONS[f.icon]) f.icon = 'paw-print';
  const vals = cols.map(k => (k === 'image_url' ? urlOrNull(f[k]) : f[k] || null));
  const all = [...cols, 'sort_order', 'is_active'];
  const v2 = [...vals, Math.round(num(f.sort_order)), f.is_active ? 1 : 0];
  const id = c.req.param('id');
  if (id) await c.env.DB.prepare(`UPDATE ${table} SET ${all.map(k => `${k} = ?`).join(', ')} WHERE id = ?`).bind(...v2, id).run();
  else await c.env.DB.prepare(`INSERT INTO ${table} (${all.join(', ')}) VALUES (${all.map(() => '?').join(', ')})`).bind(...v2).run();
  flash(c, 'success', 'Saved');
  return c.redirect('/admin/homepage', 303);
});
admin.post('/homepage/:type/:id/delete', async c => {
  const conf = HOME_TABLES[c.req.param('type')];
  if (!conf) return c.notFound();
  await c.env.DB.prepare(`DELETE FROM ${conf[0]} WHERE id = ?`).bind(c.req.param('id')).run();
  flash(c, 'success', 'Deleted');
  return c.redirect('/admin/homepage', 303);
});

// ---------------- Settings ----------------
admin.get('/settings', async c => {
  const s = await getSettings(c.env.DB);
  return page(c, 'Settings', v => (
    <>
      <form method="post" action="/admin/settings" class="space-y-6"><Csrf v={v} />
        <section class="card-soft grid gap-4 p-5 md:grid-cols-3"><h2 class="font-extrabold md:col-span-3">Delivery charges (৳)</h2>
          <Field label="Inside Dhaka" name="inside_dhaka" type="number" value={s.delivery.inside_dhaka} /><Field label="Outside Dhaka" name="outside_dhaka" type="number" value={s.delivery.outside_dhaka} /><Field label="Free delivery from (0 = never)" name="free_delivery_threshold" type="number" value={s.delivery.free_delivery_threshold} /></section>
        <section class="card-soft grid gap-4 p-5 md:grid-cols-2"><h2 class="font-extrabold md:col-span-2">Payment methods</h2>
          <div class="flex flex-wrap gap-4 md:col-span-2">{Object.entries(PAYMENT_LABELS).map(([k, l]) => <Check name={`pay_${k}`} checked={Boolean((s.payments as Record<string, unknown>)[k])} label={l} />)}</div>
          <Field label="bKash number" name="bkash_number" value={s.payments.bkash_number} /><Field label="Nagad number" name="nagad_number" value={s.payments.nagad_number} /></section>
        <section class="card-soft grid gap-4 p-5 md:grid-cols-2"><h2 class="font-extrabold md:col-span-2">Contact</h2>
          <Field label="Phone" name="phone" value={s.contact.phone} ph="+8801XXXXXXXXX" /><Field label="WhatsApp number (with 880)" name="whatsapp" value={s.contact.whatsapp} ph="8801XXXXXXXXX" />
          <Field label="Email" name="email" type="email" value={s.contact.email} /><Field label="Address" name="address" value={s.contact.address} />
          <Field label="Facebook URL" name="facebook" value={s.contact.facebook} /><Field label="Instagram URL" name="instagram" value={s.contact.instagram} /><Field label="YouTube URL" name="youtube" value={s.contact.youtube} /></section>
        <section class="card-soft grid gap-4 p-5 md:grid-cols-2"><h2 class="font-extrabold md:col-span-2">SEO defaults</h2>
          <Field label="Site name" name="site_name" value={s.seo.site_name} /><Field label="Canonical domain" name="canonical_base" value={s.seo.canonical_base} ph="https://yourdomain.com" hint="Used in canonical tags, sitemap and structured data. Leave empty to use the current address." />
          <Field label="Default title" name="default_title" value={s.seo.default_title} span /><Field label="Default description" name="default_description" value={s.seo.default_description} area rows={2} span /></section>
        <button class="btn btn-primary h-11 px-10">Save settings</button>
      </form>
      <form method="post" action="/admin/password" class="card-soft mt-10 grid max-w-xl gap-4 p-5"><Csrf v={v} />
        <h2 class="font-extrabold">Change your password</h2>
        <Field label="Current password" name="current_password" type="password" required /><Field label="New password (8+ characters)" name="password" type="password" required /><Field label="Confirm new password" name="password_confirmation" type="password" required />
        <button class="btn btn-outline h-10">Change password</button>
      </form>
    </>
  ));
});
admin.post('/settings', async c => {
  const f = await form(c);
  const pays = Object.fromEntries(Object.keys(PAYMENT_LABELS).map(k => [k, Boolean(f[`pay_${k}`])]));
  if (!Object.values(pays).some(Boolean)) return fail(c, 'Turn on at least one payment method.');
  const url = (s?: string) => (s && /^https?:\/\//.test(s) ? s.replace(/\/$/, '') : '');
  const groups = {
    delivery: { inside_dhaka: Math.max(0, num(f.inside_dhaka)), outside_dhaka: Math.max(0, num(f.outside_dhaka)), free_delivery_threshold: Math.max(0, num(f.free_delivery_threshold)) },
    payments: { ...pays, bkash_number: f.bkash_number ?? '', nagad_number: f.nagad_number ?? '' },
    contact: { phone: f.phone ?? '', whatsapp: f.whatsapp ?? '', email: f.email ?? '', address: f.address ?? '', facebook: url(f.facebook), instagram: url(f.instagram), youtube: url(f.youtube) },
    seo: { site_name: f.site_name ?? '', default_title: f.default_title ?? '', default_description: f.default_description ?? '', canonical_base: url(f.canonical_base) },
  };
  await c.env.DB.batch(Object.entries(groups).map(([k, val]) => c.env.DB.prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value').bind(k, JSON.stringify(val))));
  flash(c, 'success', 'Settings saved');
  return c.redirect('/admin/settings', 303);
});
admin.post('/password', async c => {
  const f = await form(c);
  const u = c.get('user')!;
  const row = await c.env.DB.prepare('SELECT password_hash FROM users WHERE id = ?').bind(u.id).first<{ password_hash: string }>();
  if (!row || !(await verifyPassword(f.current_password ?? '', row.password_hash))) return fail(c, 'Current password is wrong.');
  if ((f.password ?? '').length < 8 || f.password !== f.password_confirmation) return fail(c, 'New password must be 8+ characters and match the confirmation.');
  await c.env.DB.prepare('UPDATE users SET password_hash = ? WHERE id = ?').bind(await hashPassword(f.password), u.id).run();
  flash(c, 'success', 'Password changed');
  return c.redirect('/admin/settings', 303);
});

export { hydrate };
