<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /** Filter chips: url key => [tag stored on products, label]. Only chips that match products are shown. */
    public const FILTERS = [
        'kitten' => ['kitten', 'Kitten'],
        'adult' => ['adult', 'Adult'],
        'senior' => ['senior', 'Senior 7+'],
        'indoor' => ['indoor', 'Indoor'],
        'urinary' => ['urinary care', 'Urinary Care'],
        'skin-coat' => ['skin & coat', 'Skin & Coat'],
        'hairball' => ['hairball', 'Hairball'],
        'grain-free' => ['grain free', 'Grain Free'],
        'immunity' => ['immunity', 'Immunity'],
        'jelly' => ['jelly', 'In Jelly'],
        'gravy' => ['gravy', 'In Gravy'],
        'chicken' => ['chicken', 'Chicken'],
        'tuna' => ['tuna', 'Tuna'],
        'salmon' => ['salmon', 'Salmon'],
        'lamb' => ['lamb', 'Lamb'],
    ];

    public const SORTS = ['popular' => 'Popular', 'newest' => 'Newest', 'price-asc' => 'Price: Low → High', 'price-desc' => 'Price: High → Low', 'best-selling' => 'Best Selling'];

    private const PAGE_SIZE = 12;

    public function __invoke(Request $request, ?string $slug = null)
    {
        // Old ?category= links move permanently to the clean category URL.
        if (! $slug && $request->filled('category')) {
            return redirect()->route('category', array_merge(['slug' => $request->query('category')], $request->except('category')), 301);
        }

        $categories = Category::active()->get();
        $active = null;
        if ($slug) {
            $active = $categories->firstWhere('slug', $slug);
            if (! $active) {
                return response()->view('errors.404', ['message' => 'Category not found'], 404);
            }
        }

        $q = trim((string) $request->query('q', ''));
        $tag = array_key_exists((string) $request->query('tag'), self::FILTERS) ? $request->query('tag') : null;
        $sort = array_key_exists((string) $request->query('sort'), self::SORTS) ? $request->query('sort') : 'popular';

        $products = Product::active()->with('variants')->get();
        if ($active) {
            $products = $products->where('category_id', $active->id);
        }
        // Chips relevant to this listing (before the chip itself filters it).
        $available = collect(self::FILTERS)->filter(fn ($f) => $products->contains(fn ($p) => in_array($f[0], $p->tags ?? [], true)));
        if ($available->count() < 2) {
            $available = collect();
        }

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $products = $products->filter(fn ($p) => str_contains(
                mb_strtolower(implode(' ', [$p->name, $p->name_bn, $p->sku, $p->brand, $p->short_description, implode(' ', $p->tags ?? [])])),
                $needle
            ));
        }
        if ($tag) {
            $products = $products->filter(fn ($p) => in_array(self::FILTERS[$tag][0], $p->tags ?? [], true));
        }

        $products = match ($sort) {
            'price-asc' => $products->sortBy(fn ($p) => $p->minPrice()),
            'price-desc' => $products->sortByDesc(fn ($p) => $p->minPrice()),
            'newest' => $products->sortByDesc('created_at'),
            'best-selling' => $products->sortByDesc('sold_count'),
            default => $products->sortByDesc(fn ($p) => [(int) $p->is_popular, $p->sold_count, -$p->id]),
        };

        $total = $products->count();
        $page = max(1, (int) $request->query('page', 1));
        $visible = $products->values()->take($page * self::PAGE_SIZE);

        // Category pages are indexable with their own canonical; search/sort/filter combinations are not.
        $isFiltered = $q !== '' || $tag || $request->has('sort') || $page > 1;
        $title = $active
            ? ($active->seo_title ?: "{$active->name} Price in Bangladesh — Kaniva")
            : 'Shop Cat Food, Dog Food & Pet Supplies — Kaniva Bangladesh';
        $description = $active
            ? ($active->seo_description ?: $active->description)
            : 'Browse Kaniva dry and wet cat food, treats, tofu litter, pet wipes and dog food. Filter by age and need, with prices in BDT and home delivery.';
        $crumbs = [['Home', '/'], ['Shop', $active ? '/shop' : null]];
        if ($active) {
            $crumbs[] = [$active->name, null];
        }
        $listUrl = $active ? $active->url() : route('shop');

        $jsonld = [breadcrumb_schema($crumbs), [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $active?->name ?? 'All products',
            'numberOfItems' => $total,
            'itemListElement' => $products->values()->take(20)->map(fn ($p, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'url' => site_origin().'/products/'.$p->slug, 'name' => $p->name,
            ])->all(),
        ]];

        return view('shop', [
            'products' => $visible,
            'total' => $total,
            'hasMore' => $visible->count() < $total,
            'nextPage' => $page + 1,
            'categories' => $categories,
            'activeCategory' => $active,
            'filters' => $available,
            'listUrl' => $listUrl,
            'crumbs' => $crumbs,
            'q' => $q,
            'tag' => $tag,
            'sort' => $sort,
            'seo' => [
                'title' => $title,
                'description' => $description,
                'keywords' => $active?->seo_keywords,
                'canonical' => $active ? site_origin().'/category/'.$active->slug : site_origin().'/shop',
                'robots' => $isFiltered ? 'noindex, follow' : 'index, follow',
                'og_image' => $active?->imageUrl(),
                'jsonld' => $jsonld,
            ],
        ]);
    }
}
