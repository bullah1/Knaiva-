<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index()
    {
        return view('admin.blog.index', ['posts' => BlogPost::orderByDesc('published_at')->orderByDesc('id')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.blog.form', ['post' => new BlogPost(['author' => 'Kaniva Bangladesh', 'is_published' => true, 'published_at' => now()])]);
    }

    public function edit(BlogPost $post)
    {
        return view('admin.blog.form', compact('post'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new BlogPost);
    }

    public function update(Request $request, BlogPost $post)
    {
        return $this->save($request, $post);
    }

    private function save(Request $request, BlogPost $post)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($post->id)],
            'category' => ['nullable', 'string', 'max:100'],
            'author' => ['nullable', 'string', 'max:100'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'cover' => Uploads::RULE,
        ]);
        unset($data['cover']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['author'] = $data['author'] ?: 'Kaniva Bangladesh';
        $data['published_at'] = $data['published_at'] ?? now();
        $data['is_published'] = $request->boolean('is_published');
        if ($path = Uploads::store($request->file('cover'), 'blog')) {
            Uploads::delete($post->cover_url);
            $data['cover_url'] = $path;
        }
        if ($request->boolean('remove_cover')) {
            Uploads::delete($post->cover_url);
            $data['cover_url'] = null;
        }
        $post->fill($data)->save();

        return redirect()->route('admin.blog.edit', $post)->with('success', 'Post saved');
    }

    public function destroy(BlogPost $post)
    {
        Uploads::delete($post->cover_url);
        $post->delete();

        return redirect()->route('admin.blog.index')->with('success', 'Post deleted');
    }
}
