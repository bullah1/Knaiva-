import { raw } from 'hono/html';
import type { Child } from 'hono/jsx';
import { bdt, icon } from '../lib/util';
import { discountPercent, inStock, mainImage, minPrice } from '../lib/db';
import type { Product } from '../lib/env';
import type { V } from './ctx';

export const Icon = ({ name, class: cls = 'size-5' }: { name: string; class?: string }) => raw(icon(name, cls));

export const Csrf = ({ v }: { v: V }) => <input type="hidden" name="_token" value={v.csrf} />;

export const SectionTitle = ({ title, subtitle, as = 'h2', major = true }: { title: string; subtitle?: string | null; as?: 'h1' | 'h2'; major?: boolean }) => {
  const cls = `text-2xl md:text-3xl ${major ? 'heading-major' : ''}`;
  return (
    <div class="mb-8 text-center">
      {as === 'h1' ? <h1 class={cls}>{title}</h1> : <h2 class={cls}>{title}</h2>}
      <div class="mt-3 flex items-center justify-center gap-3" aria-hidden="true">
        <span class="h-px w-16 border-t border-dotted border-primary/50 md:w-24"></span>
        <Icon name="paw-print" class="size-5 text-primary" />
        <span class="h-px w-16 border-t border-dotted border-primary/50 md:w-24"></span>
      </div>
      {subtitle ? <p class="mx-auto mt-3 max-w-xl text-sm text-muted-foreground">{subtitle}</p> : null}
    </div>
  );
};

export const Stars = ({ rating, size = 'size-3.5' }: { rating: number; size?: string }) => (
  <span class="inline-flex items-center gap-0.5" role="img" aria-label={`${rating} out of 5`}>
    {[1, 2, 3, 4, 5].map(i => <Icon name="star" class={`${size} ${i <= Math.round(rating) ? 'fill-primary text-primary' : 'text-border'}`} />)}
  </span>
);

export const Breadcrumbs = ({ items }: { items: [string, string | null][] }) => (
  <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1 py-4 text-xs text-muted-foreground">
    {items.map(([label, url], i) => (
      <span class="flex items-center gap-1">
        {url ? <a href={url} class="hover:text-primary">{label}</a> : <span class="font-semibold text-foreground" aria-current="page">{label}</span>}
        {i < items.length - 1 ? <Icon name="chevron-right" class="size-3" /> : null}
      </span>
    ))}
  </nav>
);

export const ProductCard = ({ p, v }: { p: Product; v: V }) => {
  const first = p.variants[0];
  const min = minPrice(p);
  const available = inStock(p);
  const discount = discountPercent(p);
  const url = `/products/${p.slug}`;
  const sizes = p.variants.length;
  return (
    <article class="card-soft flex flex-col overflow-hidden">
      <a href={url} class="relative block bg-white">
        <img src={mainImage(p)} alt={`${p.name} pet food pack`} loading="lazy" width="912" height="912" class="aspect-square w-full bg-white object-contain" />
        {discount > 0 ? <span class="absolute left-2 top-2 rounded-full bg-primary px-2 py-1 text-[11px] font-bold text-primary-foreground">-{discount}%</span> : null}
      </a>
      <div class="flex flex-1 flex-col gap-1.5 p-3">
        <h3 class="text-sm font-bold md:text-base"><a href={url} class="line-clamp-2 text-primary hover:underline">{v.pick(p.name, p.name_bn)}</a></h3>
        <p class="text-xs text-muted-foreground">{first?.weight_label}{sizes > 1 ? ` · +${sizes - 1} ${sizes - 1 === 1 ? 'size' : 'sizes'}` : ''}</p>
        {p.review_count > 0 ? (
          <div class="flex items-center gap-1 text-xs text-muted-foreground"><Stars rating={p.rating} /><span>({p.review_count})</span></div>
        ) : null}
        <div class="flex items-baseline gap-2">
          <span class="text-base font-extrabold">{sizes > 1 ? <span class="text-xs font-semibold text-muted-foreground">From </span> : null}{bdt(min)}</span>
          {first?.mrp && first.mrp > min ? <span class="text-xs text-muted-foreground line-through">{bdt(first.mrp)}</span> : null}
        </div>
        <p class={`text-xs font-semibold ${available ? 'text-success' : 'text-destructive'}`}>{available ? v.t('inStock') : v.t('outOfStock')}</p>
        <div class="mt-auto grid grid-cols-2 gap-2 pt-2">
          <a href={url} class="btn btn-outline h-10 px-2 text-xs sm:text-sm">{v.t('details')}</a>
          <form method="post" action="/cart/add">
            <Csrf v={v} />
            <input type="hidden" name="variant_id" value={String(first?.id ?? '')} />
            <button type="submit" class="btn btn-primary h-10 w-full px-2 text-xs sm:text-sm" disabled={!available || !first}>{v.t('addToCart')}</button>
          </form>
        </div>
      </div>
    </article>
  );
};

export const Grid = ({ children }: { children?: Child }) => <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">{children}</div>;
