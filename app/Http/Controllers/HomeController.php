<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\HeroBanner;
use App\Models\Product;
use App\Models\Review;
use App\Models\TrustPoint;

class HomeController extends Controller
{
    public function __invoke()
    {
        $title = 'Kaniva Bangladesh — Premium Cat & Dog Food Online';
        $description = 'Grain free, lab tested Kaniva cat food, dog food and supplements delivered across Bangladesh. Cash on delivery, bKash and Nagad accepted.';

        return view('home', [
            'banner' => HeroBanner::where('is_active', true)->orderBy('sort_order')->first(),
            'categories' => Category::active()->get(),
            'popular' => Product::active()->where('is_popular', true)->with('variants')->latest()->take(8)->get(),
            'trustPoints' => TrustPoint::where('is_active', true)->orderBy('sort_order')->get(),
            'certificates' => Certificate::where('is_active', true)->orderBy('sort_order')->get(),
            'reviews' => Review::where('is_approved', true)->where('is_featured', true)->latest()->take(6)->get(),
            'posts' => BlogPost::published()->take(3)->get(),
            'seo' => [
                'title' => $title,
                'description' => $description,
                'canonical' => site_origin().'/',
                'jsonld' => [[
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => 'Kaniva Bangladesh',
                    'url' => site_origin(),
                    'logo' => asset('images/logo.png'),
                    'description' => $description,
                    'areaServed' => 'BD',
                ], [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => 'Kaniva Bangladesh',
                    'url' => site_origin(),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => site_origin().'/shop?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ]],
            ],
        ]);
    }
}
