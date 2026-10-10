<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;

class SeoController extends Controller
{
    public function sitemap()
    {
        $o = site_origin();
        $urls = collect([
            ['/', '1.0', 'daily'], ['/shop', '0.9', 'daily'], ['/blog', '0.8', 'weekly'],
            ['/about', '0.5', 'monthly'], ['/contact', '0.5', 'monthly'], ['/faq', '0.5', 'monthly'],
            ['/privacy-policy', '0.3', 'yearly'], ['/terms', '0.3', 'yearly'], ['/returns', '0.3', 'yearly'],
        ])->map(fn ($u) => ['loc' => $o.$u[0], 'priority' => $u[1], 'changefreq' => $u[2], 'lastmod' => null, 'image' => null]);

        foreach (Category::active()->get() as $c) {
            $urls->push(['loc' => $o.'/category/'.$c->slug, 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $c->updated_at, 'image' => $c->imageUrl()]);
        }
        foreach (Product::active()->get() as $p) {
            $urls->push(['loc' => $o.'/products/'.$p->slug, 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $p->updated_at, 'image' => $p->mainImage()]);
        }
        foreach (BlogPost::published()->get() as $b) {
            $urls->push(['loc' => $o.'/blog/'.$b->slug, 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $b->updated_at, 'image' => $b->coverUrl()]);
        }

        return response()->view('sitemap', ['urls' => $urls], 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function robots()
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /auth\nDisallow: /cart\nDisallow: /checkout\nDisallow: /order/\n\nSitemap: ".site_origin()."/sitemap.xml\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
