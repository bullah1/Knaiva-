export type Env = {
  DB: D1Database;
  ASSETS: Fetcher;
  /** Long random string used to sign cookies. Set with: npx wrangler secret put SESSION_SECRET */
  SESSION_SECRET?: string;
};

export type Session = {
  uid?: number;
  cart: Record<string, number>; // variantId -> qty
  coupon?: string;
  orders: string[];
  lang: 'en' | 'bn';
  csrf: string;
  flash?: { type: 'success' | 'error'; text: string; cart?: boolean };
  old?: Record<string, string>;
  errors?: string[];
};

export type AppEnv = { Bindings: Env; Variables: { session: Session; user: User | null } };

export type User = { id: number; name: string; email: string; role: string };

export type Variant = { id: number; product_id: number; weight_label: string; sku: string | null; mrp: number | null; price: number; stock: number; sort_order: number };

export type Product = {
  id: number; slug: string; name: string; name_bn: string | null; short_description: string | null; description: string | null;
  description_bn: string | null; ingredients: string | null; nutrition: string | null; feeding_guide: string | null;
  quick_info: [string, string][]; features: string[]; images: string[]; tags: string[]; brand: string; sku: string | null;
  category_id: number | null; life_stage: string | null; food_type: string | null; flavor: string | null; rating: number;
  review_count: number; is_popular: number; is_active: number; sold_count: number; seo_title: string | null;
  seo_description: string | null; seo_keywords: string | null; canonical_url: string | null; created_at: string; updated_at: string;
  variants: Variant[];
};

export type Category = {
  id: number; slug: string; name: string; name_bn: string | null; description: string | null; description_bn: string | null;
  image_url: string | null; sort_order: number; is_active: number; seo_title: string | null; seo_description: string | null;
  seo_keywords: string | null; updated_at: string; product_count?: number; image?: string | null;
};
