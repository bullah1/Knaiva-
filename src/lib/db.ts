import type { Category, Product, Session, Variant } from './env';
import { parseJson } from './util';

export type Settings = {
  delivery: { inside_dhaka: number; outside_dhaka: number; free_delivery_threshold: number };
  payments: { cod: boolean; bkash: boolean; nagad: boolean; card: boolean; bkash_number: string; nagad_number: string };
  contact: { phone: string; whatsapp: string; email: string; address: string; facebook: string; instagram: string; youtube: string };
  seo: { site_name: string; default_title: string; default_description: string; canonical_base: string };
};

export const DEFAULT_SETTINGS: Settings = {
  delivery: { inside_dhaka: 60, outside_dhaka: 120, free_delivery_threshold: 2000 },
  payments: { cod: true, bkash: true, nagad: true, card: false, bkash_number: '', nagad_number: '' },
  contact: { phone: '', whatsapp: '', email: '', address: 'Dhaka, Bangladesh', facebook: '', instagram: '', youtube: '' },
  seo: { site_name: 'Kaniva Bangladesh', default_title: 'Kaniva Bangladesh — Premium Cat & Dog Food', default_description: '', canonical_base: '' },
};

export const PAYMENT_LABELS: Record<string, string> = { cod: 'Cash on Delivery', bkash: 'bKash', nagad: 'Nagad', card: 'Card Payment' };
export const ORDER_STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as const;

export async function getSettings(db: D1Database): Promise<Settings> {
  const { results } = await db.prepare('SELECT key, value FROM settings').all<{ key: string; value: string }>();
  const s = structuredClone(DEFAULT_SETTINGS) as Record<string, Record<string, unknown>>;
  for (const r of results) if (s[r.key]) Object.assign(s[r.key], parseJson(r.value, {}));
  return s as unknown as Settings;
}

type ProductRow = Omit<Product, 'quick_info' | 'features' | 'images' | 'tags' | 'variants'> & { quick_info: string; features: string; images: string; tags: string };

export function hydrate(row: ProductRow, variants: Variant[] = []): Product {
  return {
    ...row,
    quick_info: parseJson(row.quick_info, []),
    features: parseJson(row.features, []),
    images: parseJson(row.images, []),
    tags: parseJson(row.tags, []),
    variants,
  };
}

/** Loads products (by a WHERE clause) together with their variants in two queries. */
export async function loadProducts(db: D1Database, where = 'p.is_active = 1', binds: unknown[] = [], tail = 'ORDER BY p.created_at DESC'): Promise<Product[]> {
  const { results } = await db.prepare(`SELECT p.* FROM products p WHERE ${where} ${tail}`).bind(...binds).all<ProductRow>();
  if (!results.length) return [];
  const ids = results.map(r => r.id);
  const vs = await db.prepare(`SELECT * FROM product_variants WHERE product_id IN (${ids.map(() => '?').join(',')}) ORDER BY sort_order, id`).bind(...ids).all<Variant>();
  const byProduct = new Map<number, Variant[]>();
  for (const v of vs.results) {
    if (!byProduct.has(v.product_id)) byProduct.set(v.product_id, []);
    byProduct.get(v.product_id)!.push(v);
  }
  return results.map(r => hydrate(r, byProduct.get(r.id) ?? []));
}

export const minPrice = (p: Product) => (p.variants.length ? Math.min(...p.variants.map(v => v.price)) : 0);
export const inStock = (p: Product) => p.variants.some(v => v.stock > 0);
export const mainImage = (p: Product) => p.images[0] || '/images/logo.png';
export function discountPercent(p: Product) {
  const first = p.variants[0];
  const min = minPrice(p);
  return first?.mrp && first.mrp > min ? Math.round(((first.mrp - min) / first.mrp) * 100) : 0;
}

/** Categories that are active and have at least one active product, with a picture. */
export async function activeCategories(db: D1Database): Promise<Category[]> {
  const { results } = await db.prepare(`
    SELECT c.*, COUNT(p.id) AS product_count,
      COALESCE(c.image_url, (SELECT json_extract(p2.images, '$[0]') FROM products p2 WHERE p2.category_id = c.id AND p2.is_active = 1 ORDER BY p2.is_popular DESC, p2.id LIMIT 1)) AS image
    FROM categories c JOIN products p ON p.category_id = c.id AND p.is_active = 1
    WHERE c.is_active = 1 GROUP BY c.id ORDER BY c.sort_order`).all<Category>();
  return results;
}

// ---------- Cart ----------
export type CartLine = { variant: Variant; product: Product; quantity: number; price: number; line_total: number };

export async function cartLines(db: D1Database, session: Session): Promise<CartLine[]> {
  const ids = Object.keys(session.cart).map(Number).filter(Boolean);
  if (!ids.length) return [];
  const { results } = await db.prepare(`SELECT * FROM product_variants WHERE id IN (${ids.map(() => '?').join(',')})`).bind(...ids).all<Variant>();
  const products = await loadProducts(db, `p.id IN (${[...new Set(results.map(v => v.product_id))].join(',') || 0}) AND p.is_active = 1`, [], '');
  const byId = new Map(products.map(p => [p.id, p]));
  const lines: CartLine[] = [];
  const clean: Record<string, number> = {};
  for (const v of results) {
    const product = byId.get(v.product_id);
    const qty = session.cart[String(v.id)];
    if (!product || !qty) continue;
    clean[String(v.id)] = qty;
    lines.push({ variant: v, product, quantity: qty, price: v.price, line_total: v.price * qty });
  }
  if (Object.keys(clean).length !== Object.keys(session.cart).length) session.cart = clean;
  return lines;
}

export type Coupon = { id: number; code: string; discount_type: string; discount_value: number; min_order_value: number; expires_at: string | null; usage_limit: number | null; used_count: number; is_active: number };

export async function findCoupon(db: D1Database, code?: string | null): Promise<Coupon | null> {
  if (!code?.trim()) return null;
  return db.prepare('SELECT * FROM coupons WHERE code = ? COLLATE NOCASE').bind(code.trim()).first<Coupon>();
}

export function couponProblem(c: Coupon, subtotal: number): string | null {
  if (!c.is_active) return 'Coupon not found';
  if (c.expires_at && new Date(c.expires_at + (c.expires_at.includes('T') ? '' : 'T23:59:59+06:00')) < new Date()) return 'This coupon has expired';
  if (c.usage_limit !== null && c.used_count >= c.usage_limit) return 'This coupon has reached its limit';
  if (subtotal < c.min_order_value) return `Minimum order ৳${Math.round(c.min_order_value)} required`;
  return null;
}

export function couponDiscount(c: Coupon | null, subtotal: number): number {
  if (!c || subtotal < c.min_order_value) return 0;
  const amount = c.discount_type === 'percent' ? (subtotal * c.discount_value) / 100 : c.discount_value;
  return Math.min(Math.round(amount), subtotal);
}

export function totals(settings: Settings, subtotal: number, coupon: Coupon | null, zone: string) {
  const d = settings.delivery;
  const discount = couponDiscount(coupon, subtotal);
  const base = zone === 'outside_dhaka' ? Number(d.outside_dhaka) : Number(d.inside_dhaka);
  const threshold = Number(d.free_delivery_threshold);
  const free = threshold > 0 && subtotal - discount >= threshold;
  const delivery_charge = free ? 0 : base;
  return { subtotal, discount, coupon, delivery_charge, free_delivery: free, total: subtotal - discount + delivery_charge };
}

export async function nextOrderNumber(db: D1Database): Promise<string> {
  const day = new Date(Date.now() + 6 * 3600_000).toISOString().slice(0, 10).replace(/-/g, '');
  const prefix = `KAN-${day}-`;
  const last = await db.prepare('SELECT order_number FROM orders WHERE order_number LIKE ? ORDER BY order_number DESC LIMIT 1').bind(prefix + '%').first<{ order_number: string }>();
  const n = last ? parseInt(last.order_number.slice(-4), 10) + 1 : 1;
  return prefix + String(n).padStart(4, '0');
}

export function siteOrigin(settings: Settings, reqUrl: string): string {
  return (settings.seo.canonical_base || new URL(reqUrl).origin).replace(/\/$/, '');
}

export function breadcrumbSchema(crumbs: [string, string | null][], origin: string) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: crumbs.map(([name, url], i) => ({ '@type': 'ListItem', position: i + 1, name, ...(url ? { item: origin + url } : {}) })),
  };
}
