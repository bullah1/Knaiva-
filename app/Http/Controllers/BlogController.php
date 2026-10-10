<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;

class BlogController extends Controller
{
    public function index()
    {
        return view('blog.index', [
            'posts' => BlogPost::published()->get(),
            'seo' => [
                'title' => 'Pet Tips & Cat Care Blog — Kaniva Bangladesh',
                'description' => 'Practical pet care advice for Bangladeshi cat and dog parents: feeding schedules, food comparisons and buying guides.',
                'canonical' => site_origin().'/blog',
            ],
        ]);
    }

    public function show(string $slug)
    {
        $post = BlogPost::where('slug', $slug)->where('is_published', true)->first();
        if (! $post) {
            return response()->view('errors.404', ['message' => 'Article not found'], 404);
        }

        $crumbs = [['Home', '/'], ['Pet Tips', '/blog'], [$post->title, null]];
        $url = site_origin().'/blog/'.$post->slug;
        $image = upload_url($post->og_image_url) ?: $post->coverUrl();

        return view('blog.show', [
            'post' => $post,
            'related' => BlogPost::published()->where('id', '!=', $post->id)->take(3)->get(),
            'crumbs' => $crumbs,
            'seo' => [
                'title' => $post->seo_title ?: "{$post->title} — Kaniva Bangladesh",
                'description' => $post->seo_description ?: $post->excerpt ?: 'Pet care advice from Kaniva Bangladesh.',
                'keywords' => $post->seo_keywords,
                'canonical' => $url,
                'og_type' => 'article',
                'og_image' => $image,
                'jsonld' => [breadcrumb_schema($crumbs), array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $post->title,
                    'description' => $post->excerpt,
                    'image' => $image,
                    'author' => ['@type' => 'Organization', 'name' => $post->author],
                    'publisher' => ['@type' => 'Organization', 'name' => 'Kaniva Bangladesh', 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/logo.png')]],
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'dateModified' => $post->updated_at?->toIso8601String(),
                    'mainEntityOfPage' => $url,
                ])],
            ],
        ]);
    }
}
