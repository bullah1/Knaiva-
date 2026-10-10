<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::active()->where('slug', $slug)->with(['variants', 'category'])->first();
        if (! $product) {
            return response()->view('errors.404', ['message' => 'Product not found'], 404);
        }

        $reviews = $product->reviews()->where('is_approved', true)->latest()->get();
        $related = Product::active()->with('variants')
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->take(4)->get();

        $images = $product->imageUrls();
        $url = site_origin().'/products/'.$product->slug;
        $title = $product->seo_title ?: "{$product->name} — Kaniva Bangladesh";
        $description = $product->seo_description ?: $product->short_description ?: "Buy {$product->name} online in Bangladesh.";
        $crumbs = [['Home', '/'], ['Shop', '/shop']];
        if ($product->category) {
            $crumbs[] = [$product->category->name, '/category/'.$product->category->slug];
        }
        $crumbs[] = [$product->name, null];

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'brand' => ['@type' => 'Brand', 'name' => $product->brand],
            'description' => $product->short_description ?: $product->description,
            'image' => $images,
            'offers' => $product->variants->map(fn ($v) => [
                '@type' => 'Offer',
                'name' => $v->weight_label,
                'price' => number_format($v->price, 2, '.', ''),
                'priceCurrency' => 'BDT',
                'sku' => $v->sku,
                'availability' => $v->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
                'url' => $url,
            ])->values()->all(),
        ];
        if ($product->review_count > 0) {
            $schema['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $product->rating, 'reviewCount' => $product->review_count];
        }
        if ($reviews->isNotEmpty()) {
            $schema['review'] = $reviews->take(5)->map(fn ($r) => [
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $r->customer_name],
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $r->rating, 'bestRating' => 5],
                'reviewBody' => $r->body,
            ])->values()->all();
        }

        return view('product', [
            'product' => $product,
            'images' => $images,
            'reviews' => $reviews,
            'related' => $related,
            'crumbs' => $crumbs,
            'seo' => [
                'title' => $title,
                'description' => $description,
                'keywords' => $product->seo_keywords,
                'canonical' => $product->canonical_url ?: $url,
                'og_type' => 'product',
                'og_image' => $images[0],
                'jsonld' => [breadcrumb_schema($crumbs), $schema],
            ],
        ]);
    }
}
