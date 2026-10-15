import { raw } from 'hono/html';
import type { Child } from 'hono/jsx';
import type { Category, Product } from '../lib/env';
import { inStock, mainImage, PAYMENT_LABELS, type CartLine, type Coupon } from '../lib/db';
import { bdt, formatDate, isoDate, renderMarkdown, whatsappLink } from '../lib/util';
import type { V } from './ctx';
import { Breadcrumbs, Csrf, Grid, Icon, ProductCard, SectionTitle, Stars } from './ui';

export type Post = { id: number; slug: string; title: string; excerpt: string | null; content: string | null; cover_url: string | null; author: string; category: string | null; published_at: string; updated_at: string; is_published: number; seo_title: string | null; seo_description: string | null; seo_keywords: string | null };

const PostCard = ({ post, v, h = 'h2' }: { post: Post; v: V; h?: 'h2' | 'h3' }) => {
  const link = <a href={`/blog/${post.slug}`} class="text-primary hover:underline">{post.title}</a>;
  return (
    <article class="card-soft flex flex-col overflow-hidden">
      {post.cover_url ? <img src={post.cover_url} alt={post.title} loading="lazy" class="h-44 w-full object-cover" /> : <div class="flex h-44 items-center justify-center bg-accent"><Icon name="paw-print" class="size-10 text-primary" /></div>}
      <div class="flex flex-1 flex-col p-4">
        {post.category ? <p class="text-xs font-semibold uppercase text-muted-foreground">{post.category}</p> : null}
        {h === 'h2' ? <h2 class="mt-1 text-base font-extrabold">{link}</h2> : <h3 class="mt-1 text-base font-extrabold">{link}</h3>}
        <p class="mt-2 line-clamp-3 text-sm text-muted-foreground">{post.excerpt}</p>
        <time datetime={isoDate(post.published_at)} class="mt-2 text-xs text-muted-foreground">{formatDate(post.published_at)}</time>
        <a href={`/blog/${post.slug}`} class="btn btn-outline mt-4 h-10 self-start">{v.t('readMore')}<span class="sr-only">: {post.title}</span></a>
      </div>
    </article>
  );
};

// ---------------- Home ----------------
export type Banner = { title: string; title_bn: string | null; subtitle: string | null; subtitle_bn: string | null; image_url: string | null; cta_label: string | null; cta_link: string | null };
export type Trust = { icon: string; title: string; title_bn: string | null; description: string | null; description_bn: string | null };
export type Cert = { title: string; image_url: string | null };
export type Review = { id: number; customer_name: string; rating: number; body: string | null; product_id?: number | null };

export const HomePage = ({ v, banner, categories, popular, trust, certs, reviews, posts }: { v: V; banner: Banner | null; categories: Category[]; popular: Product[]; trust: Trust[]; certs: Cert[]; reviews: Review[]; posts: Post[] }) => (
  <>
    <section class="bg-cream">
      <div class="container-page grid items-center gap-6 py-10 md:grid-cols-2 md:py-16">
        <div>
          <h1 class="heading-major text-3xl leading-tight md:text-5xl">{banner ? v.pick(banner.title, banner.title_bn) : 'PREMIUM NUTRITION FOR BANGLADESHI PETS'}</h1>
          <p class="mt-4 max-w-md text-base text-muted-foreground">{banner ? v.pick(banner.subtitle, banner.subtitle_bn) : 'Grain free, lab tested Kaniva food delivered all over Bangladesh.'}</p>
          <div class="mt-6 flex flex-wrap gap-3">
            <a href={banner?.cta_link || '/shop'} class="btn btn-primary h-12 px-8 text-base">{banner?.cta_label && v.lang === 'en' ? banner.cta_label : v.t('shopNow')}</a>
            <a href="/about" class="btn btn-outline h-12 px-8 text-base">{v.t('about')}</a>
          </div>
        </div>
        {banner?.image_url ? (
          <img src={banner.image_url} alt={banner.title} width="1600" height="1008" fetchpriority="high" class="w-full rounded-2xl object-cover shadow-card" />
        ) : (
          <img src="/images/hero-kaniva-1600.webp" srcset="/images/hero-kaniva-800.webp 800w, /images/hero-kaniva-1600.webp 1600w" sizes="(min-width: 768px) 50vw, 100vw" alt="Kaniva premium cat food pack with a healthy cat" width="1600" height="1008" fetchpriority="high" class="w-full rounded-2xl object-cover shadow-card" />
        )}
      </div>
    </section>

    {categories.length ? (
      <section class="section"><div class="container-page">
        <SectionTitle title={v.t('categories')} />
        <div class="flex flex-wrap justify-center gap-6">
          {categories.map(c => (
            <a href={`/category/${c.slug}`} class="group flex w-28 flex-col items-center gap-2 text-center">
              <span class="flex size-24 items-center justify-center rounded-full bg-cream shadow-soft transition-transform group-hover:scale-105">
                {c.image ? <img src={c.image} alt="" loading="lazy" width="96" height="96" class="size-24 rounded-full bg-white object-contain p-1" /> : <Icon name="paw-print" class="size-9 text-primary" />}
              </span>
              <span class="text-sm font-bold text-primary">{v.pick(c.name, c.name_bn)}</span>
            </a>
          ))}
        </div>
      </div></section>
    ) : null}

    <section class="section-cream"><div class="container-page">
      <SectionTitle title={v.t('popularProducts')} />
      <Grid>{popular.map(p => <ProductCard p={p} v={v} />)}</Grid>
      <div class="mt-8 text-center"><a href="/shop" class="btn btn-outline h-11 px-8">{v.t('shop')}</a></div>
    </div></section>

    {trust.length ? (
      <section class="section"><div class="container-page">
        <SectionTitle title={v.t('whyChooseUs')} />
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
          {trust.map(t => (
            <div class="card-soft flex flex-col items-center gap-2 p-5 text-center">
              <Icon name={t.icon} class="size-8 text-primary" />
              <h3 class="text-sm font-extrabold md:text-base">{v.pick(t.title, t.title_bn)}</h3>
              <p class="text-xs text-muted-foreground">{v.pick(t.description, t.description_bn)}</p>
            </div>
          ))}
        </div>
      </div></section>
    ) : null}

    {certs.length ? (
      <section class="section-cream"><div class="container-page">
        <SectionTitle title={v.t('certificates')} />
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
          {certs.map(c => <div class="card-soft flex h-24 items-center justify-center p-4 text-center text-sm font-bold text-primary">{c.image_url ? <img src={c.image_url} alt={c.title} loading="lazy" class="max-h-16" /> : c.title}</div>)}
        </div>
      </div></section>
    ) : null}

    {reviews.length ? (
      <section class="section"><div class="container-page">
        <SectionTitle title={v.t('reviews')} />
        <div class="grid gap-4 md:grid-cols-3">
          {reviews.map(r => (
            <figure class="card-soft p-5"><Stars rating={r.rating} /><blockquote class="mt-3 text-sm">{r.body}</blockquote><figcaption class="mt-3 text-sm font-bold text-primary">{r.customer_name}</figcaption></figure>
          ))}
        </div>
      </div></section>
    ) : null}

    {posts.length ? (
      <section class="section-cream"><div class="container-page">
        <SectionTitle title={v.t('latestTips')} />
        <div class="grid gap-4 md:grid-cols-3">{posts.map(p => <PostCard post={p} v={v} h="h3" />)}</div>
      </div></section>
    ) : null}
  </>
);

// ---------------- Shop / category ----------------
export const FILTERS: Record<string, [string, string]> = {
  kitten: ['kitten', 'Kitten'], adult: ['adult', 'Adult'], senior: ['senior', 'Senior 7+'], indoor: ['indoor', 'Indoor'],
  urinary: ['urinary care', 'Urinary Care'], 'skin-coat': ['skin & coat', 'Skin & Coat'], hairball: ['hairball', 'Hairball'],
  'grain-free': ['grain free', 'Grain Free'], immunity: ['immunity', 'Immunity'], jelly: ['jelly', 'In Jelly'], gravy: ['gravy', 'In Gravy'],
  chicken: ['chicken', 'Chicken'], tuna: ['tuna', 'Tuna'], salmon: ['salmon', 'Salmon'], lamb: ['lamb', 'Lamb'],
};
export const SORTS: Record<string, string> = { popular: 'Popular', newest: 'Newest', 'price-asc': 'Price: Low → High', 'price-desc': 'Price: High → Low', 'best-selling': 'Best Selling' };

export const ShopPage = (props: { v: V; products: Product[]; total: number; hasMore: boolean; nextPage: number; categories: Category[]; active: Category | null; filters: string[]; listUrl: string; crumbs: [string, string | null][]; q: string; tag: string | null; sort: string }) => {
  const { v, active, q, tag, sort, listUrl } = props;
  const params: Record<string, string> = {};
  if (q) params.q = q; if (tag) params.tag = tag; if (sort !== 'popular') params.sort = sort;
  const link = (change: Record<string, string | null>, base = listUrl) => {
    const merged: Record<string, string> = { ...params };
    for (const [k, val] of Object.entries(change)) { if (val === null || val === '') delete merged[k]; else merged[k] = val; }
    const qs = new URLSearchParams(merged).toString();
    return base + (qs ? `?${qs}` : '');
  };
  const name = active ? v.pick(active.name, active.name_bn) : null;
  return (
    <div class="container-page pb-12">
      <Breadcrumbs items={props.crumbs} />
      <SectionTitle title={name ?? 'Shop All Products'} as="h1" subtitle={active ? v.pick(active.description, active.description_bn) : 'Cat food, treats, litter and care products — delivered across Bangladesh.'} />
      <form action={listUrl} method="get" class="mx-auto mb-6 flex max-w-xl gap-2" role="search">
        <input type="search" name="q" value={q} placeholder="Search by name, flavour or SKU" class="input h-10" aria-label="Search products" />
        <button type="submit" class="btn btn-primary h-10">Search</button>
      </form>
      <nav class="no-scrollbar -mx-4 mb-3 flex gap-2 overflow-x-auto px-4 pb-1" aria-label="Categories">
        <a href={link({ tag: null }, '/shop')} class={`chip ${!active ? 'chip-active' : ''}`}>All</a>
        {props.categories.map(c => <a href={link({ tag: null }, `/category/${c.slug}`)} class={`chip ${active?.id === c.id ? 'chip-active' : ''}`}>{v.pick(c.name, c.name_bn)}</a>)}
      </nav>
      {props.filters.length ? (
        <div class="no-scrollbar -mx-4 mb-4 flex items-center gap-2 overflow-x-auto px-4 pb-1">
          <span class="shrink-0 text-xs font-bold uppercase text-muted-foreground">Filter:</span>
          {props.filters.map(k => <a href={link({ tag: tag === k ? null : k })} rel="nofollow" class={`chip h-9 text-xs ${tag === k ? 'chip-active' : ''}`}>{FILTERS[k][1]}{tag === k ? <span class="ml-1">✕</span> : null}</a>)}
        </div>
      ) : null}
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted-foreground">{props.total} {props.total === 1 ? 'product' : 'products'}</p>
        <label class="flex items-center gap-2 text-sm">
          <span class="text-muted-foreground">Sort:</span>
          <select class="h-10 rounded-xl border border-border bg-background px-3 text-sm font-semibold" onchange="window.location = this.value" aria-label="Sort products">
            {Object.entries(SORTS).map(([k, l]) => <option value={link({ sort: k === 'popular' ? null : k })} selected={sort === k}>{l}</option>)}
          </select>
        </label>
      </div>
      {props.products.length === 0 ? (
        <div class="py-16 text-center"><p class="text-sm text-muted-foreground">No products matched your search.</p><a href={listUrl} class="btn btn-outline mt-4 h-10">Clear filters</a></div>
      ) : (
        <>
          <h2 class="sr-only">{name ?? 'All products'}</h2>
          <Grid>{props.products.map(p => <ProductCard p={p} v={v} />)}</Grid>
          {props.hasMore ? <div class="mt-8 text-center"><a href={link({ page: String(props.nextPage) })} rel="nofollow" class="btn btn-outline h-11 px-8">Load More</a></div> : null}
        </>
      )}
    </div>
  );
};

// ---------------- Product ----------------
export const ProductPage = ({ v, p, reviews, related, crumbs }: { v: V; p: Product; reviews: Review[]; related: Product[]; crumbs: [string, string | null][] }) => {
  const variants = p.variants;
  const first = variants[0];
  const images = p.images.length ? p.images : [mainImage(p)];
  const vjson = JSON.stringify(variants.map(x => ({ id: x.id, label: x.weight_label, price: x.price, mrp: x.mrp, stock: x.stock })));
  const tabs = ([['description', 'Description', v.pick(p.description, p.description_bn)], ['ingredients', 'Ingredients', p.ingredients], ['nutrition', 'Nutrition', p.nutrition], ['feeding', 'Feeding Guide', p.feeding_guide]] as [string, string, string | null][]).filter(t => t[2]);
  const outOfStock = !first || first.stock <= 0;
  return (
    <div class="container-page pb-28 md:pb-12" x-data={`productPage(${vjson})`}>
      <Breadcrumbs items={crumbs} />
      <div class="grid gap-8 md:grid-cols-2">
        <div>
          <div class="card-soft overflow-hidden bg-white">
            {images.map((img, i) => <img src={img} alt={`${p.name} pack shot${i ? ' ' + (i + 1) : ''}`} width="912" height="912" class="aspect-square w-full object-contain" loading={i ? 'lazy' : undefined} x-show={`image === ${i}`} {...(i ? { 'x-cloak': '' } : {})} />)}
          </div>
          {images.length > 1 ? (
            <div class="mt-3 flex flex-wrap gap-2">
              {images.map((img, i) => <button type="button" {...{ '@click': `image = ${i}` }} class="size-16 overflow-hidden rounded-lg border" {...{ ':class': `image === ${i} ? 'border-primary' : 'border-border'` }} aria-label={`Show image ${i + 1}`}><img src={img} alt="" loading="lazy" class="size-full bg-white object-contain" /></button>)}
            </div>
          ) : null}
        </div>
        <div>
          <h1 class="text-2xl font-extrabold md:text-3xl">{v.pick(p.name, p.name_bn)}</h1>
          <p class="mt-1 text-xs text-muted-foreground">SKU: {p.sku || '—'} · {p.brand}</p>
          {p.review_count > 0 ? <a href="#reviews" class="mt-2 flex items-center gap-2 text-sm text-muted-foreground"><Stars rating={p.rating} size="size-4" /><span>{p.rating.toFixed(1)} ({p.review_count} reviews)</span></a> : null}
          {p.short_description ? <p class="mt-3 text-sm text-muted-foreground">{p.short_description}</p> : null}
          {first ? (
            <>
              <div class="mt-4 flex items-baseline gap-3">
                <span class="text-3xl font-extrabold text-primary" x-text="bdt(variant.price)">{bdt(first.price)}</span>
                <template x-if="savePercent > 0"><span class="flex items-baseline gap-3"><span class="text-base text-muted-foreground line-through" x-text="bdt(variant.mrp)"></span><span class="rounded-full bg-accent px-2 py-1 text-xs font-bold text-accent-foreground" x-text="'Save ' + savePercent + '%'"></span></span></template>
              </div>
              <p class="mt-1 text-sm font-semibold" {...{ ':class': "available ? 'text-success' : 'text-destructive'" }} x-text={`available ? ${JSON.stringify(v.t('inStock'))} : ${JSON.stringify(v.t('outOfStock'))}`}>{first.stock > 0 ? v.t('inStock') : v.t('outOfStock')}</p>
              <div class={`mt-5 ${variants.length < 2 ? 'hidden' : ''}`}>
                <p class="text-sm font-bold">Size</p>
                <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-label="Size">
                  {variants.map(x => <button type="button" role="radio" {...{ '@click': `variantId = ${x.id}`, ':aria-checked': `variantId === ${x.id}`, ':class': `variantId === ${x.id} ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-background'` }} class="h-10 rounded-lg border px-4 text-sm font-semibold">{x.weight_label}</button>)}
                </div>
              </div>
            </>
          ) : null}
          {p.quick_info.length ? (
            <div class="mt-5 rounded-2xl bg-cream p-4">
              <p class="text-sm font-extrabold text-primary">এক নজরে</p>
              <dl class="mt-2 grid gap-1.5 text-sm">{p.quick_info.map(([l, val]) => <div class="flex gap-2"><dt class="shrink-0 font-bold">{l}:</dt><dd>{val}</dd></div>)}</dl>
            </div>
          ) : null}
          {p.features.length ? <ul class="mt-5 grid gap-2 sm:grid-cols-2">{p.features.map(f => <li class="flex items-center gap-2 text-sm"><Icon name="check" class="size-4 shrink-0 text-primary" /> {f}</li>)}</ul> : null}
          {first ? (
            <form method="post" action="/cart/add" class="mt-6 space-y-3" id="add-to-cart">
              <Csrf v={v} />
              <input type="hidden" name="variant_id" value={String(first.id)} {...{ ':value': 'variantId' }} />
              <input type="hidden" name="quantity" value="1" {...{ ':value': 'qty' }} />
              <div class="flex items-center gap-3">
                <div class="flex h-11 items-center rounded-lg border border-border">
                  <button type="button" aria-label="Decrease quantity" class="px-3 text-primary" {...{ '@click': 'qty = Math.max(1, qty - 1)' }}><Icon name="minus" class="size-4" /></button>
                  <span class="w-8 text-center text-sm font-bold" x-text="qty">1</span>
                  <button type="button" aria-label="Increase quantity" class="px-3 text-primary" {...{ '@click': 'qty = Math.min(50, qty + 1)' }}><Icon name="plus" class="size-4" /></button>
                </div>
                <button type="submit" class="btn btn-primary h-11 flex-1" disabled={outOfStock} {...{ ':disabled': '!available' }}>{v.t('addToCart')}</button>
              </div>
              <button type="submit" name="buy_now" value="1" class="btn btn-outline h-11 w-full" disabled={outOfStock} {...{ ':disabled': '!available' }}>Buy Now</button>
            </form>
          ) : null}
        </div>
      </div>

      {tabs.length ? (
        <div class="mt-10" x-data={`{ tab: '${tabs[0][0]}' }`}>
          <div class="flex flex-wrap gap-1 rounded-xl bg-cream p-1" role="tablist">
            {tabs.map(([k, l]) => <button type="button" role="tab" {...{ '@click': `tab = '${k}'`, ':aria-selected': `tab === '${k}'`, ':class': `tab === '${k}' ? 'bg-background text-primary shadow-sm' : 'text-muted-foreground'` }} class="rounded-lg px-4 py-2 text-sm font-semibold">{l}</button>)}
          </div>
          {tabs.map(([k, l, body], i) => (
            <section class="prose-content pt-4 text-sm" role="tabpanel" x-show={`tab === '${k}'`} {...(i ? { 'x-cloak': '' } : {})}>
              <h2 class="sr-only">{l}</h2>
              {(body ?? '').split(/\n/).map((line, j) => <>{j ? <br /> : null}{line}</>)}
            </section>
          ))}
        </div>
      ) : null}

      <section class="section" id="reviews">
        <SectionTitle title="Customer Reviews" />
        <div class="grid gap-4 md:grid-cols-3">
          {reviews.length ? reviews.map(r => <figure class="card-soft p-5"><Stars rating={r.rating} /><blockquote class="mt-3 text-sm">{r.body}</blockquote><figcaption class="mt-3 text-sm font-bold text-primary">{r.customer_name}</figcaption></figure>)
            : <p class="text-sm text-muted-foreground md:col-span-3">No reviews yet for this product. Share yours by messaging us on WhatsApp.</p>}
        </div>
      </section>

      {related.length ? (
        <section class="section-cream -mx-4 px-4 md:-mx-8 md:px-8">
          <SectionTitle title="Related Products" />
          <div class="grid grid-cols-2 gap-4 md:grid-cols-4">{related.map(r => <ProductCard p={r} v={v} />)}</div>
        </section>
      ) : null}

      {first ? (
        <div class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-3 border-t border-border bg-background p-3 md:hidden">
          <div><p class="text-xs text-muted-foreground" x-text="variant.label">{first.weight_label}</p><p class="text-lg font-extrabold text-primary" x-text="bdt(variant.price)">{bdt(first.price)}</p></div>
          <button type="submit" form="add-to-cart" class="btn btn-primary h-11 flex-1" disabled={outOfStock} {...{ ':disabled': '!available' }}>{v.t('addToCart')}</button>
        </div>
      ) : null}
    </div>
  );
};

// ---------------- Cart ----------------
const Qty = ({ v, id, qty }: { v: V; id: number; qty: number }) => (
  <div class="flex h-10 items-center rounded-lg border border-border">
    {[[qty - 1, 'minus', 'Decrease quantity'], [qty + 1, 'plus', 'Increase quantity']].map(([n, ic, label], i) => (
      <>
        {i === 1 ? <span class="w-8 text-center text-sm font-bold">{qty}</span> : null}
        <form method="post" action="/cart/update"><Csrf v={v} /><input type="hidden" name="variant_id" value={String(id)} /><input type="hidden" name="quantity" value={String(n)} />
          <button type="submit" aria-label={String(label)} class="flex h-10 items-center px-3 text-primary"><Icon name={String(ic)} class="size-4" /></button></form>
      </>
    ))}
  </div>
);

export const CartPage = ({ v, lines, subtotal, coupon, discount, threshold }: { v: V; lines: CartLine[]; subtotal: number; coupon: Coupon | null; discount: number; threshold: number }) => {
  if (!lines.length) return (
    <div class="container-page py-20 text-center">
      <Icon name="shopping-bag" class="mx-auto size-14 text-primary" />
      <h1 class="mt-4 text-2xl font-extrabold">{v.t('emptyCart')}</h1>
      <p class="mt-2 text-sm text-muted-foreground">Add some Kaniva food and your pet will thank you.</p>
      <a href="/shop" class="btn btn-primary mt-6 h-11 px-8">{v.t('continueShopping')}</a>
    </div>
  );
  const remaining = Math.max(threshold - subtotal, 0);
  const pct = threshold ? Math.min((subtotal / threshold) * 100, 100) : 100;
  return (
    <div class="container-page pb-12">
      <Breadcrumbs items={[['Home', '/'], ['Cart', null]]} />
      <SectionTitle title={v.t('cart')} as="h1" />
      <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-3">
          {lines.map(l => (
            <div class="card-soft flex gap-3 p-3">
              <img src={mainImage(l.product)} alt={l.product.name} loading="lazy" width="96" height="96" class="size-24 rounded-lg bg-white object-contain" />
              <div class="flex flex-1 flex-col">
                <a href={`/products/${l.product.slug}`} class="text-sm font-bold text-primary hover:underline">{v.pick(l.product.name, l.product.name_bn)}</a>
                <p class="text-xs text-muted-foreground">{l.variant.weight_label}</p>
                <p class="mt-1 text-sm font-extrabold">{bdt(l.price)}</p>
                {l.variant.stock < l.quantity ? <p class="text-xs font-semibold text-destructive">Only {l.variant.stock} left in stock</p> : null}
                <div class="mt-auto flex items-center justify-between gap-2">
                  <Qty v={v} id={l.variant.id} qty={l.quantity} />
                  <div class="flex items-center gap-3">
                    <span class="text-sm font-extrabold">{bdt(l.line_total)}</span>
                    <form method="post" action="/cart/remove"><Csrf v={v} /><input type="hidden" name="variant_id" value={String(l.variant.id)} /><button type="submit" aria-label="Remove item" class="text-muted-foreground hover:text-destructive"><Icon name="trash-2" class="size-4" /></button></form>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
        <aside class="card-soft h-fit p-5">
          {threshold > 0 ? (
            <div class="mb-5">
              <p class="text-sm font-semibold">{remaining > 0 ? `Add ${bdt(remaining)} more to get free delivery.` : 'You have free delivery!'}</p>
              <div class="mt-2 h-2 overflow-hidden rounded-full bg-accent" role="progressbar" aria-valuemin={0} aria-valuemax={100} aria-valuenow={Math.round(pct)}><div class="h-full rounded-full bg-primary" style={`width: ${pct}%`}></div></div>
            </div>
          ) : null}
          {coupon ? (
            <form method="post" action="/cart/coupon" class="flex items-center justify-between rounded-xl bg-cream px-3 py-2 text-sm">
              <Csrf v={v} /><input type="hidden" name="remove" value="1" />
              <span class="flex items-center gap-2 font-bold text-success"><Icon name="ticket" class="size-4" /> {coupon.code}</span>
              <button type="submit" class="text-xs font-semibold text-muted-foreground underline">Remove</button>
            </form>
          ) : (
            <form method="post" action="/cart/coupon" class="flex gap-2"><Csrf v={v} /><input name="code" value={v.old.code ?? ''} placeholder="Coupon code" class="input h-10" aria-label="Coupon code" /><button type="submit" class="btn btn-outline h-10">Apply</button></form>
          )}
          <dl class="mt-5 space-y-2 text-sm">
            <div class="flex justify-between"><dt>{v.t('subtotal')}</dt><dd class="font-bold">{bdt(subtotal)}</dd></div>
            {discount > 0 ? <div class="flex justify-between text-success"><dt>{v.t('discount')} ({coupon?.code})</dt><dd class="font-bold">-{bdt(discount)}</dd></div> : null}
            <div class="flex justify-between text-muted-foreground"><dt>{v.t('delivery')}</dt><dd>Calculated at checkout</dd></div>
            <div class="flex justify-between border-t border-border pt-3 text-base"><dt class="font-extrabold">{v.t('total')}</dt><dd class="font-extrabold text-primary">{bdt(subtotal - discount)}</dd></div>
          </dl>
          <a href="/checkout" class="btn btn-primary mt-5 h-12 w-full text-base">{v.t('checkout')}</a>
          <a href="/shop" class="btn btn-ghost mt-2 h-10 w-full">{v.t('continueShopping')}</a>
        </aside>
      </div>
    </div>
  );
};

// ---------------- Checkout ----------------
export const CheckoutPage = ({ v, lines, t, paymentOptions }: { v: V; lines: CartLine[]; t: { subtotal: number; discount: number; coupon: Coupon | null; delivery_charge: number; free_delivery: boolean; total: number }; paymentOptions: [string, string][] }) => {
  const d = v.settings.delivery;
  const pay = v.settings.payments;
  const cfg = { zone: v.old.delivery_zone || 'inside_dhaka', payment: v.old.payment_method || paymentOptions[0]?.[0] || 'cod', subtotal: t.subtotal, discount: t.discount, inside: Number(d.inside_dhaka), outside: Number(d.outside_dhaka), threshold: Number(d.free_delivery_threshold) };
  const field = (id: string, label: string, opts: { required?: boolean; type?: string; ph?: string; area?: boolean; span?: boolean; auto?: string; mode?: string } = {}) => (
    <div class={opts.span ? 'sm:col-span-2' : ''}>
      <label class="label" for={id}>{label}</label>
      {opts.area ? <textarea id={id} name={id} required={opts.required} maxlength={500} autocomplete={opts.auto} class="textarea">{v.old[id] ?? ''}</textarea>
        : <input id={id} name={id} value={v.old[id] ?? ''} required={opts.required} placeholder={opts.ph} autocomplete={opts.auto} inputmode={opts.mode as 'tel' | undefined} maxlength={120} class="input" />}
    </div>
  );
  return (
    <div class="container-page pb-12">
      <Breadcrumbs items={[['Home', '/'], ['Cart', '/cart'], ['Checkout', null]]} />
      <SectionTitle title="Checkout" as="h1" />
      {v.errors.length ? <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive" role="alert"><p class="font-bold">Please fix the following:</p><ul class="mt-1 list-disc pl-5">{v.errors.map(e => <li>{e}</li>)}</ul></div> : null}
      <form method="post" action="/checkout" class="grid gap-6 lg:grid-cols-[1.6fr_1fr]" x-data={`checkout(${JSON.stringify(cfg)})`} {...{ 'x-on:submit': "$refs.submit.disabled = true; $refs.submit.textContent = 'Placing order…'" }}>
        <Csrf v={v} />
        <div class="space-y-6">
          <section class="card-soft p-5">
            <h2 class="text-lg font-extrabold">Customer Information</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
              {field('customer_name', 'Full Name', { required: true, span: true, auto: 'name' })}
              {field('phone', 'Phone Number', { required: true, ph: '01XXXXXXXXX', auto: 'tel', mode: 'tel' })}
              {field('area', 'Area')}
              {field('city', 'City / District', { auto: 'address-level2' })}
              {field('address', 'Full Address', { required: true, area: true, span: true, auto: 'street-address' })}
              {field('notes', 'Order note (optional)', { area: true, span: true })}
            </div>
          </section>
          <section class="card-soft p-5">
            <h2 class="text-lg font-extrabold">Delivery</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
              {([['inside_dhaka', 'Inside Dhaka', d.inside_dhaka], ['outside_dhaka', 'Outside Dhaka', d.outside_dhaka]] as const).map(([k, l, ch]) => (
                <label class="flex h-12 cursor-pointer items-center justify-between rounded-lg border px-4 text-sm font-semibold" {...{ ':class': `zone === '${k}' ? 'border-primary bg-cream' : 'border-border'` }}>
                  <span class="flex items-center gap-2"><input type="radio" name="delivery_zone" value={k} x-model="zone" checked={cfg.zone === k} class="accent-[var(--color-primary)]" /> {l}</span><span>{bdt(ch)}</span>
                </label>
              ))}
            </div>
          </section>
          <section class="card-soft p-5">
            <h2 class="text-lg font-extrabold">Payment</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
              {paymentOptions.map(([k, l]) => (
                <label class="flex h-12 cursor-pointer items-center gap-2 rounded-lg border px-4 text-sm font-semibold" {...{ ':class': `payment === '${k}' ? 'border-primary bg-cream' : 'border-border'` }}>
                  <input type="radio" name="payment_method" value={k} x-model="payment" checked={cfg.payment === k} class="accent-[var(--color-primary)]" /> {l}
                </label>
              ))}
            </div>
            {pay.bkash_number ? <p class="mt-3 text-sm text-muted-foreground" x-show="payment === 'bkash'" x-cloak>Send payment to bKash <strong>{pay.bkash_number}</strong> and share the transaction ID on WhatsApp.</p> : null}
            {pay.nagad_number ? <p class="mt-3 text-sm text-muted-foreground" x-show="payment === 'nagad'" x-cloak>Send payment to Nagad <strong>{pay.nagad_number}</strong> and share the transaction ID on WhatsApp.</p> : null}
          </section>
        </div>
        <aside class="card-soft h-fit p-5 lg:sticky lg:top-20">
          <h2 class="text-lg font-extrabold">Order Summary</h2>
          <ul class="mt-4 space-y-3 text-sm">{lines.map(l => <li class="flex justify-between gap-3"><span>{l.product.name} <span class="text-muted-foreground">({l.variant.weight_label}) × {l.quantity}</span></span><span class="font-bold">{bdt(l.line_total)}</span></li>)}</ul>
          <dl class="mt-4 space-y-2 border-t border-border pt-4 text-sm">
            <div class="flex justify-between"><dt>Subtotal</dt><dd class="font-bold">{bdt(t.subtotal)}</dd></div>
            {t.discount > 0 ? <div class="flex justify-between text-success"><dt>Discount ({t.coupon?.code})</dt><dd class="font-bold">-{bdt(t.discount)}</dd></div> : null}
            <div class="flex justify-between"><dt>Delivery charge</dt><dd class="font-bold" x-text="deliveryCharge === 0 ? 'Free' : bdt(deliveryCharge)">{t.free_delivery ? 'Free' : bdt(t.delivery_charge)}</dd></div>
            <div class="flex justify-between border-t border-border pt-3 text-base"><dt class="font-extrabold">Total</dt><dd class="font-extrabold text-primary" x-text="bdt(total)">{bdt(t.total)}</dd></div>
          </dl>
          <button type="submit" x-ref="submit" class="btn btn-primary mt-5 h-12 w-full text-base">{v.t('placeOrder')}</button>
          <p class="mt-3 text-center text-xs text-muted-foreground">By placing this order you agree to our <a href="/terms" class="underline">terms</a>.</p>
        </aside>
      </form>
    </div>
  );
};

// ---------------- Order ----------------
export type Order = { id: number; order_number: string; customer_name: string; phone: string; address: string; area: string | null; city: string | null; delivery_zone: string; payment_method: string; coupon_code: string | null; subtotal: number; discount: number; delivery_charge: number; total: number; status: string; notes: string | null; created_at: string; items_count?: number };
export type OrderItem = { id: number; product_name: string; product_slug: string | null; variant_label: string | null; unit_price: number; quantity: number; line_total: number };
export const fullAddress = (o: Order) => [o.address, o.area, o.city].filter(Boolean).join(', ');
export const zoneLabel = (z: string) => (z === 'inside_dhaka' ? 'Inside Dhaka' : 'Outside Dhaka');

export const OrderPage = ({ v, o, items }: { v: V; o: Order; items: OrderItem[] }) => {
  const summary = [`Order ${o.order_number}`, `Name: ${o.customer_name}`, ...items.map(i => `${i.product_name} (${i.variant_label}) × ${i.quantity}`), `Total: ${bdt(o.total)}`, `Address: ${fullAddress(o)}`].join('\n');
  const wa = whatsappLink(v.settings.contact.whatsapp, summary);
  const num = (v.settings.payments as Record<string, unknown>)[`${o.payment_method}_number`] as string | undefined;
  return (
    <div class="container-page max-w-3xl py-10">
      <div class="card-soft p-6 text-center">
        <Icon name="circle-check" class="mx-auto size-14 text-success" />
        <h1 class="mt-4 text-2xl font-extrabold">Thank you, {o.customer_name}!</h1>
        <p class="mt-2 text-sm text-muted-foreground">Your order <span class="font-bold text-primary">{o.order_number}</span> was placed on {formatDate(o.created_at)}. We will call you shortly to confirm.</p>
      </div>
      <div class="card-soft mt-6 p-6">
        <h2 class="text-lg font-extrabold">Order Summary</h2>
        <ul class="mt-4 space-y-2 text-sm">{items.map(i => <li class="flex justify-between gap-3"><span>{i.product_name} <span class="text-muted-foreground">({i.variant_label}) × {i.quantity}</span></span><span class="font-bold">{bdt(i.line_total)}</span></li>)}</ul>
        <dl class="mt-4 space-y-2 border-t border-border pt-4 text-sm">
          <div class="flex justify-between"><dt>Subtotal</dt><dd>{bdt(o.subtotal)}</dd></div>
          {o.discount > 0 ? <div class="flex justify-between text-success"><dt>Discount</dt><dd>-{bdt(o.discount)}</dd></div> : null}
          <div class="flex justify-between"><dt>Delivery ({zoneLabel(o.delivery_zone)})</dt><dd>{o.delivery_charge === 0 ? 'Free' : bdt(o.delivery_charge)}</dd></div>
          <div class="flex justify-between border-t border-border pt-3 text-base font-extrabold"><dt>Total</dt><dd class="text-primary">{bdt(o.total)}</dd></div>
        </dl>
        <div class="mt-4 grid gap-1 text-sm text-muted-foreground">
          <p>Payment: {PAYMENT_LABELS[o.payment_method] ?? o.payment_method}</p>
          <p>Delivery to: {fullAddress(o)}</p>
          <p>Estimated delivery: {o.delivery_zone === 'inside_dhaka' ? '1–2 working days' : '2–4 working days'}</p>
          <p>Status: <span class="font-semibold capitalize text-foreground">{o.status}</span></p>
        </div>
        {(o.payment_method === 'bkash' || o.payment_method === 'nagad') && num ? <p class="mt-4 rounded-xl bg-cream p-3 text-sm">Send {bdt(o.total)} to {PAYMENT_LABELS[o.payment_method]} <strong>{num}</strong> with reference <strong>{o.order_number}</strong>, then share the transaction ID on WhatsApp.</p> : null}
      </div>
      <div class="mt-6 flex flex-wrap justify-center gap-3">
        {wa ? <a href={wa} target="_blank" rel="noopener" class="btn btn-primary h-11"><Icon name="message-circle" /> Share order on WhatsApp</a> : null}
        <a href="/shop" class="btn btn-outline h-11">{v.t('continueShopping')}</a>
      </div>
    </div>
  );
};

export const OrderVerifyPage = ({ v, num }: { v: V; num: string }) => (
  <div class="container-page max-w-md py-16"><div class="card-soft p-6">
    <h1 class="text-xl font-extrabold">Order {num}</h1>
    <p class="mt-2 text-sm text-muted-foreground">Enter the phone number used for this order to see its details.</p>
    <form method="post" action={`/order/${num}`} class="mt-5 space-y-3"><Csrf v={v} />
      <label class="label" for="phone">Phone Number</label><input id="phone" name="phone" required inputmode="tel" placeholder="01XXXXXXXXX" class="input" />
      <button type="submit" class="btn btn-primary h-11 w-full">View order</button></form>
  </div></div>
);

// ---------------- Blog ----------------
export const BlogIndex = ({ v, posts }: { v: V; posts: Post[] }) => (
  <div class="container-page pb-12">
    <Breadcrumbs items={[['Home', '/'], ['Pet Tips', null]]} />
    <SectionTitle title="Pet Tips" as="h1" subtitle="Care advice written for Bangladeshi pet parents." />
    {posts.length ? <div class="grid gap-4 md:grid-cols-3">{posts.map(p => <PostCard post={p} v={v} />)}</div> : <p class="py-16 text-center text-sm text-muted-foreground">No articles yet.</p>}
  </div>
);

export const BlogShow = ({ post, related, crumbs }: { post: Post; related: Post[]; crumbs: [string, string | null][] }) => (
  <article class="container-page max-w-3xl pb-12">
    <Breadcrumbs items={crumbs} />
    <h1 class="text-3xl font-extrabold">{post.title}</h1>
    <p class="mt-2 text-sm text-muted-foreground">{post.author} · <time datetime={isoDate(post.published_at)}>{formatDate(post.published_at)}</time></p>
    {post.cover_url ? <img src={post.cover_url} alt={post.title} class="mt-6 w-full rounded-2xl object-cover" /> : null}
    <div class="prose-content mt-6">{raw(renderMarkdown(post.content))}</div>
    <aside class="mt-10 rounded-2xl bg-cream p-6 text-center"><p class="text-base font-bold">Looking for food your pet will actually finish?</p><a href="/shop" class="btn btn-primary mt-4 h-11 px-8">Shop Kaniva</a></aside>
    {related.length ? <section class="mt-10"><h2 class="text-lg font-extrabold">More Pet Tips</h2><ul class="mt-3 space-y-2 text-sm">{related.map(r => <li><a href={`/blog/${r.slug}`} class="text-primary hover:underline">{r.title}</a></li>)}</ul></section> : null}
  </article>
);

// ---------------- Static pages ----------------
export const Simple = ({ heading, subtitle, children }: { heading: string; subtitle?: string; children?: Child }) => (
  <div class="container-page max-w-3xl pb-12">
    <Breadcrumbs items={[['Home', '/'], [heading, null]]} />
    <SectionTitle title={heading} as="h1" subtitle={subtitle} />
    {children}
  </div>
);

export const FAQS: [string, string][] = [
  ['How long does delivery take?', 'Inside Dhaka orders arrive in 1–2 working days. Outside Dhaka usually takes 2–4 working days.'],
  ['What are the delivery charges?', 'Delivery is charged separately for inside Dhaka and outside Dhaka, and is shown automatically at checkout. Orders above the free delivery threshold ship free.'],
  ['Which payment methods do you accept?', 'Cash on Delivery, bKash, Nagad and card payment where enabled.'],
  ['Can I return a product?', 'Yes. Unopened packs can be returned within 7 days of delivery. Damaged or wrong items are replaced free of charge.'],
  ['How do I choose the right food for my cat?', 'Match the life stage first (kitten or adult), then pick dry, wet or a mix. Message us on WhatsApp and we will recommend a portion size.'],
];

export const STATIC: Record<string, { heading: string; title: string; description: string; html: string }> = {
  about: { heading: 'About Kaniva', title: 'About Kaniva Bangladesh — Premium Pet Nutrition', description: 'Kaniva Bangladesh brings grain free, lab tested cat and dog nutrition to pet parents across the country with honest ingredients and fair prices.',
    html: '<p>Kaniva Bangladesh exists for one reason: pet parents here deserve the same quality of nutrition that is available anywhere else in the world, at a price that makes sense in Bangladesh.</p><h2>What we stand for</h2><p>Every Kaniva recipe starts with real meat, stays grain free where it matters, and is lab tested batch by batch. We publish full ingredient lists and feeding guides so you always know what is in the bowl.</p><h2>Delivery across Bangladesh</h2><p>We deliver inside Dhaka in one to two working days and across the rest of the country in two to four. Pay with cash on delivery, bKash, Nagad or card.</p><h2>Talk to us</h2><p>Not sure which food suits your cat or dog? Message us on WhatsApp and we will help you pick the right recipe and portion size.</p>' },
  'privacy-policy': { heading: 'Privacy Policy', title: 'Privacy Policy — Kaniva Bangladesh', description: 'How Kaniva Bangladesh collects, uses and protects your personal and order information.',
    html: '<h2>What we collect</h2><p>When you place an order we collect your name, phone number and delivery address. We use this only to process, deliver and support your order.</p><h2>How we store it</h2><p>Order data is stored securely and is accessible only to authorised Kaniva staff. We never sell your data to third parties.</p><h2>Payments</h2><p>Payment details entered with bKash, Nagad or a card provider are handled by that provider. We do not store card numbers or wallet PINs.</p><h2>Cookies</h2><p>We use one cookie to keep your cart and language choice. You can block cookies in your browser at any time.</p><h2>Your choices</h2><p>Contact us any time to ask what information we hold about you or to request its deletion.</p>' },
  terms: { heading: 'Terms & Conditions', title: 'Terms & Conditions — Kaniva Bangladesh', description: 'The terms that apply when you order Kaniva pet food in Bangladesh.',
    html: '<h2>Orders</h2><p>An order is confirmed once our team verifies it by phone. We may cancel an order if a product is out of stock or the delivery address cannot be reached.</p><h2>Prices</h2><p>All prices are in Bangladeshi Taka and include applicable taxes. Delivery charges are shown at checkout.</p><h2>Delivery</h2><p>Estimated delivery times are guidance, not a guarantee. Delays caused by weather, strikes or courier issues are outside our control.</p><h2>Product use</h2><p>Feeding guides are general recommendations. Consult a veterinarian for pets with medical conditions or special dietary needs.</p><h2>Contact</h2><p>Questions about these terms can be sent to our support team by phone, email or WhatsApp.</p>' },
  returns: { heading: 'Return & Refund Policy', title: 'Return & Refund Policy — Kaniva Bangladesh', description: 'How to return or exchange Kaniva pet food and how refunds are processed in Bangladesh.',
    html: '<h2>Returns</h2><p>Unopened products in original packaging can be returned within 7 days of delivery. Opened food packs cannot be returned for hygiene reasons.</p><h2>Damaged or wrong items</h2><p>If a pack arrives damaged or the wrong item was delivered, message us on WhatsApp with a photo within 48 hours and we will replace it at no cost.</p><h2>Refunds</h2><p>Approved refunds are issued through the original payment method, or by bKash/Nagad for cash on delivery orders, within 7 working days.</p><h2>Return delivery charge</h2><p>Return delivery is free when the fault is ours. For change-of-mind returns the customer covers the return courier charge.</p>' },
};

export const ContactPage = ({ v }: { v: V }) => {
  const c = v.settings.contact;
  const wa = whatsappLink(c.whatsapp, 'Hi Kaniva, I have a question.');
  const Card = ({ href, ic, label, value, color = 'text-primary' }: { href?: string; ic: string; label: string; value: string; color?: string }) => {
    const inner = <><Icon name={ic} class={`size-6 ${color}`} /><span><span class="block text-xs text-muted-foreground">{label}</span><span class="font-bold text-primary">{value}</span></span></>;
    return href ? <a href={href} target={href.startsWith('http') ? '_blank' : undefined} rel="noopener" class="card-soft flex items-center gap-3 p-5">{inner}</a> : <div class="card-soft flex items-center gap-3 p-5">{inner}</div>;
  };
  return (
    <Simple heading="Contact Us" subtitle="We reply fastest on WhatsApp, 10am–8pm daily.">
      <div class="grid gap-4 sm:grid-cols-2">
        {c.phone ? <Card href={`tel:${c.phone}`} ic="phone" label="Call us" value={c.phone} /> : null}
        {c.email ? <Card href={`mailto:${c.email}`} ic="mail" label="Email" value={c.email} /> : null}
        {c.address ? <Card ic="map-pin" label="Address" value={c.address} /> : null}
        {wa ? <Card href={wa} ic="message-circle" label="WhatsApp" value="Chat with support" color="text-success" /> : null}
      </div>
      {wa ? <a href={wa} target="_blank" rel="noopener" class="btn btn-primary mt-6 h-12 w-full sm:w-auto sm:px-10">Message us on WhatsApp</a> : null}
    </Simple>
  );
};

export const FaqPage = () => (
  <Simple heading="Frequently Asked Questions">
    <div class="divide-y divide-border border-y border-border">
      {FAQS.map(([q, a], i) => (
        <details class="group py-1" open={i === 0}>
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-left text-base font-bold [&::-webkit-details-marker]:hidden">{q}<span class="shrink-0 text-primary transition-transform group-open:rotate-45"><Icon name="plus" /></span></summary>
          <p class="pb-4 text-sm text-muted-foreground">{a}</p>
        </details>
      ))}
    </div>
  </Simple>
);

export const NotFound = ({ message = 'Page not found' }: { message?: string }) => (
  <div class="flex min-h-[60vh] items-center justify-center px-4"><div class="max-w-md text-center">
    <p class="text-7xl font-extrabold text-primary">404</p>
    <h1 class="mt-4 text-xl font-bold">{message}</h1>
    <p class="mt-2 text-sm text-muted-foreground">The page you're looking for doesn't exist or has been moved.</p>
    <div class="mt-6 flex flex-wrap justify-center gap-2"><a href="/" class="btn btn-outline h-10">Go home</a><a href="/shop" class="btn btn-primary h-10">Browse the shop</a></div>
  </div></div>
);

export const LoginPage = ({ v, setup }: { v: V; setup: boolean }) => (
  <div class="container-page flex max-w-md flex-col justify-center py-16"><div class="card-soft p-6">
    <h1 class="text-xl font-extrabold text-primary">{setup ? 'Create the first admin account' : 'Staff Sign In'}</h1>
    <p class="mt-1 text-sm text-muted-foreground">{setup ? 'No admin exists yet. This form only works once.' : 'Kaniva admin panel access only.'}</p>
    {v.errors.length ? <p class="mt-3 text-sm font-semibold text-destructive">{v.errors[0]}</p> : null}
    <form method="post" action={setup ? '/setup' : '/auth'} class="mt-5 space-y-4"><Csrf v={v} />
      {setup ? <div><label class="label" for="name">Name</label><input id="name" name="name" required class="input" /></div> : null}
      <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" value={v.old.email ?? ''} autocomplete="email" required class="input" /></div>
      <div><label class="label" for="password">Password{setup ? ' (8+ characters)' : ''}</label><input id="password" name="password" type="password" minlength={setup ? 8 : undefined} autocomplete={setup ? 'new-password' : 'current-password'} required class="input" /></div>
      <button type="submit" class="btn btn-primary h-11 w-full">{setup ? 'Create admin' : 'Sign In'}</button>
    </form>
  </div></div>
);

export { inStock };
