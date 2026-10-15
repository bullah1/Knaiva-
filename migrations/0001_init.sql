-- Kaniva Bangladesh — Cloudflare D1 schema
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'admin',
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  name_bn TEXT,
  description TEXT,
  description_bn TEXT,
  image_url TEXT,
  sort_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  seo_title TEXT, seo_description TEXT, seo_keywords TEXT,
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  name_bn TEXT,
  short_description TEXT,
  description TEXT,
  description_bn TEXT,
  ingredients TEXT,
  nutrition TEXT,
  feeding_guide TEXT,
  quick_info TEXT NOT NULL DEFAULT '[]',   -- JSON [[label, value], ...]
  features TEXT NOT NULL DEFAULT '[]',     -- JSON [string]
  images TEXT NOT NULL DEFAULT '[]',       -- JSON [url]
  tags TEXT NOT NULL DEFAULT '[]',         -- JSON [lowercase tag]
  brand TEXT NOT NULL DEFAULT 'Kaniva',
  sku TEXT,
  category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
  life_stage TEXT, food_type TEXT, flavor TEXT,
  rating REAL NOT NULL DEFAULT 0,
  review_count INTEGER NOT NULL DEFAULT 0,
  is_popular INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  sold_count INTEGER NOT NULL DEFAULT 0,
  seo_title TEXT, seo_description TEXT, seo_keywords TEXT, canonical_url TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX idx_products_category ON products(category_id);

CREATE TABLE product_variants (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  weight_label TEXT NOT NULL,
  sku TEXT,
  mrp REAL,
  price REAL NOT NULL,
  stock INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX idx_variants_product ON product_variants(product_id);

CREATE TABLE coupons (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL UNIQUE COLLATE NOCASE,
  discount_type TEXT NOT NULL DEFAULT 'percent',
  discount_value REAL NOT NULL,
  min_order_value REAL NOT NULL DEFAULT 0,
  expires_at TEXT,
  usage_limit INTEGER,
  used_count INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_number TEXT NOT NULL UNIQUE,
  customer_name TEXT NOT NULL,
  phone TEXT NOT NULL,
  address TEXT NOT NULL,
  area TEXT, city TEXT,
  delivery_zone TEXT NOT NULL DEFAULT 'inside_dhaka',
  payment_method TEXT NOT NULL DEFAULT 'cod',
  coupon_code TEXT,
  subtotal REAL NOT NULL DEFAULT 0,
  discount REAL NOT NULL DEFAULT 0,
  delivery_charge REAL NOT NULL DEFAULT 0,
  total REAL NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'pending',
  notes TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX idx_orders_status ON orders(status);

CREATE TABLE order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
  variant_id INTEGER,
  product_name TEXT NOT NULL,
  product_slug TEXT,
  variant_label TEXT,
  unit_price REAL NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  line_total REAL NOT NULL
);
CREATE INDEX idx_items_order ON order_items(order_id);

CREATE TABLE reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
  customer_name TEXT NOT NULL,
  rating INTEGER NOT NULL DEFAULT 5,
  body TEXT,
  is_approved INTEGER NOT NULL DEFAULT 0,
  is_featured INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE blog_posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT NOT NULL UNIQUE,
  title TEXT NOT NULL,
  excerpt TEXT,
  content TEXT,
  cover_url TEXT,
  author TEXT NOT NULL DEFAULT 'Kaniva Bangladesh',
  category TEXT,
  is_published INTEGER NOT NULL DEFAULT 1,
  published_at TEXT NOT NULL DEFAULT (datetime('now')),
  seo_title TEXT, seo_description TEXT, seo_keywords TEXT,
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE hero_banners (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL, title_bn TEXT,
  subtitle TEXT, subtitle_bn TEXT,
  image_url TEXT,
  cta_label TEXT DEFAULT 'Shop Now', cta_link TEXT DEFAULT '/shop',
  sort_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE trust_points (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  icon TEXT NOT NULL DEFAULT 'paw-print',
  title TEXT NOT NULL, title_bn TEXT,
  description TEXT, description_bn TEXT,
  sort_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE certificates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  image_url TEXT,
  sort_order INTEGER NOT NULL DEFAULT 0,
  is_active INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE settings (
  key TEXT PRIMARY KEY,
  value TEXT NOT NULL   -- JSON
);
