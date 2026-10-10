<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', ['categories' => Category::withCount('products')->orderBy('sort_order')->get()]);
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true, 'sort_order' => Category::max('sort_order') + 1])]);
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', compact('category'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new Category);
    }

    public function update(Request $request, Category $category)
    {
        return $this->save($request, $category);
    }

    private function save(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'description_bn' => ['nullable', 'string', 'max:1000'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'sort_order' => ['nullable', 'integer'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'image' => Uploads::RULE,
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);
        if ($path = Uploads::store($request->file('image'), 'categories')) {
            Uploads::delete($category->image_url);
            $data['image_url'] = $path;
        }
        if ($request->boolean('remove_image')) {
            Uploads::delete($category->image_url);
            $data['image_url'] = null;
        }
        $category->fill($data)->save();

        return redirect()->route('admin.categories.index')->with('success', 'Category saved');
    }

    public function destroy(Category $category)
    {
        Uploads::delete($category->image_url);
        $category->delete();

        return back()->with('success', 'Category deleted. Its products are now uncategorised.');
    }
}
