import { raw } from 'hono/html';
import type { Child } from 'hono/jsx';
import type { Category } from '../lib/env';
import { whatsappLink } from '../lib/util';
import type { Seo, V } from './ctx';
import { Icon } from './ui';

const NAV: [string, 'home' | 'shop' | 'blog' | 'about' | 'contact'][] = [['/', 'home'], ['/shop', 'shop'], ['/blog', 'blog'], ['/about', 'about'], ['/contact', 'contact']];

export const Toasts = ({ v }: { v: V }) => (
  <div class="pointer-events-none fixed inset-x-0 top-20 z-[60] flex flex-col items-center gap-2 px-4" aria-live="polite">
    {v.flash ? (
      <div data-toast class={`pointer-events-auto flex max-w-md items-center gap-3 rounded-xl border bg-background px-4 py-3 text-sm font-semibold shadow-card ${v.flash.type === 'success' ? 'border-success/30 text-success' : 'border-destructive/30 text-destructive'}`}>
        <Icon name={v.flash.type === 'success' ? 'circle-check' : 'x'} class="size-5 shrink-0" />
        <span class="text-foreground">{v.flash.text}</span>
        {v.flash.cart ? <a href="/cart" class="ml-1 whitespace-nowrap text-primary underline">View cart</a> : null}
      </div>
    ) : null}
  </div>
);

export const Layout = ({ v, seo, categories, children }: { v: V; seo: Seo; categories: Category[]; children?: Child }) => {
  const s = v.settings;
  const siteName = s.seo.site_name || 'Kaniva Bangladesh';
  const title = seo.title || s.seo.default_title;
  const description = seo.description || s.seo.default_description || 'Buy Kaniva cat food and dog food online in Bangladesh.';
  const ogImage = seo.ogImage || `${v.origin}/images/hero-kaniva-1600.webp`;
  const c = s.contact;
  const wa = whatsappLink(c.whatsapp, 'Hi Kaniva, I need help with an order.');
  const navActive = (href: string) => (href === '/' ? v.path === '/' : v.path.startsWith(href) || (href === '/shop' && v.path.startsWith('/category')));
  return (
    <>
      {raw('<!DOCTYPE html>')}
      <html lang={v.lang}>
        <head>
          <meta charset="utf-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
          <title>{title}</title>
          <meta name="description" content={description} />
          {seo.keywords ? <meta name="keywords" content={seo.keywords} /> : null}
          <meta name="robots" content={seo.robots ?? 'index, follow'} />
          {seo.canonical ? <link rel="canonical" href={seo.canonical} /> : null}
          <meta name="theme-color" content="#8B2E1C" />
          <meta property="og:site_name" content={siteName} />
          <meta property="og:type" content={seo.ogType ?? 'website'} />
          <meta property="og:title" content={title} />
          <meta property="og:description" content={description} />
          <meta property="og:image" content={ogImage} />
          {seo.canonical ? <meta property="og:url" content={seo.canonical} /> : null}
          <meta property="og:locale" content={v.lang === 'bn' ? 'bn_BD' : 'en_US'} />
          <meta name="twitter:card" content="summary_large_image" />
          <meta name="twitter:title" content={title} />
          <meta name="twitter:description" content={description} />
          <meta name="twitter:image" content={ogImage} />
          <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
          <link rel="preconnect" href="https://fonts.googleapis.com" />
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
          <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Nunito:ital,wght@0,400;0,600;0,700;0,800;1,400&display=swap" />
          {seo.preload ? raw(seo.preload) : null}
          <link rel="stylesheet" href="/assets/app.css" />
          <script src="/assets/app.js" defer></script>
          {(seo.jsonld ?? []).map(j => raw(`<script type="application/ld+json">${JSON.stringify(j).replace(/</g, '\\u003c')}</script>`))}
        </head>
        <body class="min-h-screen">
          <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-[60] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-primary-foreground">Skip to content</a>
          <div class="flex min-h-screen flex-col">
            <header class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur" x-data="{ search: false, menu: false }" {...{ '@keydown.escape.window': 'menu = false; search = false' }}>
              <div class="container-page flex h-16 items-center gap-2">
                <a href="/" class="mr-auto flex items-center gap-2 text-primary" aria-label="Kaniva home">
                  <Icon name="paw-print" class="size-7" /><span class="text-lg font-extrabold tracking-tight md:text-xl">KANIVA</span>
                </a>
                <nav class="hidden items-center gap-6 lg:flex" aria-label="Main">
                  {NAV.map(([href, key]) => (
                    <a href={href} class={`text-sm font-semibold transition-colors hover:text-primary ${navActive(href) ? 'text-primary' : 'text-foreground'}`} aria-current={navActive(href) ? 'page' : undefined}>{v.t(key)}</a>
                  ))}
                </nav>
                <div class="ml-2 flex items-center gap-1">
                  <div class="flex items-center rounded-full border border-border p-0.5 text-xs font-bold">
                    <a href="/lang/en" rel="nofollow" class={`rounded-full px-2 py-1 ${v.lang === 'en' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}>EN</a>
                    <a href="/lang/bn" rel="nofollow" class={`rounded-full px-2 py-1 ${v.lang === 'bn' ? 'bg-primary text-primary-foreground' : 'text-muted-foreground'}`}>বাংলা</a>
                  </div>
                  <button type="button" class="btn-icon" aria-label={v.t('search')} {...{ '@click': 'search = !search; $nextTick(() => search && $refs.q.focus())' }}>
                    <span x-show="!search"><Icon name="search" /></span>
                    <span x-show="search" x-cloak><Icon name="x" /></span>
                  </button>
                  <a href="/cart" class="btn-icon" aria-label={v.t('cart')}>
                    <Icon name="shopping-cart" />
                    {v.cartCount > 0 ? <span class="absolute -right-0.5 -top-0.5 flex size-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground">{v.cartCount}</span> : null}
                  </a>
                  <button type="button" class="btn-icon lg:hidden" aria-label="Menu" {...{ '@click': 'menu = true' }}><Icon name="menu" /></button>
                </div>
              </div>
              <div x-show="search" x-cloak class="border-t border-border bg-cream">
                <form action="/shop" method="get" class="container-page flex gap-2 py-3" role="search">
                  <input x-ref="q" type="search" name="q" value={v.query('q') ?? ''} placeholder={v.t('search')} class="input h-10" aria-label={v.t('search')} />
                  <button type="submit" class="btn btn-primary h-10">{v.t('search')}</button>
                </form>
              </div>
              <div x-show="menu" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-black/40" {...{ '@click': 'menu = false' }}></div>
                <div class="absolute inset-y-0 right-0 w-72 bg-background shadow-xl">
                  <div class="flex items-center justify-between px-4 pt-4">
                    <span class="text-lg font-extrabold text-primary">KANIVA</span>
                    <button type="button" class="btn-icon" aria-label="Close menu" {...{ '@click': 'menu = false' }}><Icon name="x" /></button>
                  </div>
                  <nav class="mt-4 flex flex-col px-2" aria-label="Mobile">
                    {[...NAV, ['/faq', 'faq'] as const, ['/cart', 'cart'] as const].map(([href, key]) => (
                      <a href={href} class="rounded-lg px-4 py-3 text-base font-semibold text-foreground hover:bg-cream">{v.t(key)}</a>
                    ))}
                  </nav>
                </div>
              </div>
            </header>

            <main id="main" class="flex-1">{children}</main>

            <footer class="mt-12 border-t border-border bg-cream">
              <div class="container-page grid gap-8 py-12 md:grid-cols-4">
                <div>
                  <div class="flex items-center gap-2 text-primary"><Icon name="paw-print" class="size-6" /><span class="text-lg font-extrabold">KANIVA BANGLADESH</span></div>
                  <p class="mt-3 text-sm text-muted-foreground">Premium, grain free nutrition for Bangladeshi cats and dogs. Nationwide delivery.</p>
                  <div class="mt-4 space-y-2 text-sm">
                    {c.address ? <p class="flex items-start gap-2"><Icon name="map-pin" class="mt-0.5 size-4 shrink-0 text-primary" /> {c.address}</p> : null}
                    {c.phone ? <a href={`tel:${c.phone}`} class="flex items-center gap-2 text-primary hover:underline"><Icon name="phone" class="size-4" /> {c.phone}</a> : null}
                    {c.email ? <a href={`mailto:${c.email}`} class="flex items-center gap-2 text-primary hover:underline"><Icon name="mail" class="size-4" /> {c.email}</a> : null}
                  </div>
                </div>
                <div>
                  <h2 class="text-sm font-extrabold uppercase">Quick Links</h2>
                  <ul class="mt-3 space-y-2 text-sm">
                    {[['/', 'Home'], ['/shop', 'Shop'], ['/blog', 'Pet Tips'], ['/about', 'About Us'], ['/contact', 'Contact'], ['/faq', 'FAQ']].map(([h, l]) => <li><a href={h} class="hover:text-primary">{l}</a></li>)}
                  </ul>
                </div>
                <div>
                  <h2 class="text-sm font-extrabold uppercase">Shop</h2>
                  <ul class="mt-3 space-y-2 text-sm">
                    {categories.map(cat => <li><a href={`/category/${cat.slug}`} class="hover:text-primary">{v.pick(cat.name, cat.name_bn)}</a></li>)}
                  </ul>
                </div>
                <div>
                  <h2 class="text-sm font-extrabold uppercase">Policies</h2>
                  <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="/privacy-policy" class="hover:text-primary">Privacy Policy</a></li>
                    <li><a href="/terms" class="hover:text-primary">Terms &amp; Conditions</a></li>
                    <li><a href="/returns" class="hover:text-primary">Return &amp; Refund Policy</a></li>
                  </ul>
                  <div class="mt-4 flex gap-3">
                    {(['facebook', 'instagram', 'youtube'] as const).map(k => (c[k] ? <a href={c[k]} aria-label={k} class="text-primary hover:opacity-80" target="_blank" rel="noopener"><Icon name={k} /></a> : null))}
                  </div>
                </div>
              </div>
              <div class="border-t border-border py-4 text-center text-xs text-muted-foreground">© {new Date().getFullYear()} Kaniva Bangladesh. All rights reserved.</div>
            </footer>
          </div>
          {wa && !v.path.startsWith('/products/') ? (
            <a href={wa} target="_blank" rel="noopener" aria-label="Chat on WhatsApp" class="fixed bottom-5 right-4 z-40 flex size-13 items-center justify-center rounded-full bg-success text-success-foreground shadow-card transition-transform hover:scale-105">
              <Icon name="message-circle" class="size-6" />
            </a>
          ) : null}
          <Toasts v={v} />
        </body>
      </html>
    </>
  );
};
