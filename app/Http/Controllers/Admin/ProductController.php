<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['variants', 'category'])
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product(['brand' => 'Kaniva', 'is_active' => true]), 'categories' => Category::orderBy('sort_order')->get()]);
    }

    public function edit(Product $product)
    {
        $product->load('variants');

        return view('admin.products.form', ['product' => $product, 'categories' => Category::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new Product);
    }

    public function update(Request $request, Product $product)
    {
        return $this->save($request, $product);
    }

    private function save(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product->id)],
            'sku' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'life_stage' => ['nullable', 'string', 'max:50'],
            'food_type' => ['nullable', 'string', 'max:50'],
            'flavor' => ['nullable', 'string', 'max:50'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'ingredients' => ['nullable', 'string'],
            'nutrition' => ['nullable', 'string'],
            'feeding_guide' => ['nullable', 'string'],
            'features_text' => ['nullable', 'string'],
            'quick_info_text' => ['nullable', 'string', 'max:2000'],
            'tags_text' => ['nullable', 'string'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'review_count' => ['nullable', 'integer', 'min:0'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'new_images' => ['nullable', 'array', 'max:8'],
            'new_images.*' => Uploads::RULE,
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.weight_label' => ['required', 'string', 'max:50'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.mrp' => ['nullable', 'numeric', 'min:0'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ], ['variants.*.weight_label.required' => 'Every size needs a weight label.', 'variants.*.price.required' => 'Every size needs a price.']);

        $fields = collect($data)->except(['features_text', 'quick_info_text', 'tags_text', 'new_images', 'variants', 'slug'])->all();
        $fields['slug'] = $data['slug'] ?: $this->uniqueSlug($data['name'], $product->id);
        $fields['brand'] = $data['brand'] ?: 'Kaniva';
        $fields['rating'] = $data['rating'] ?? 0;
        $fields['review_count'] = $data['review_count'] ?? 0;
        $fields['features'] = array_values(array_filter(array_map('trim', preg_split('/\R/', $data['features_text'] ?? ''))));
        $fields['tags'] = array_values(array_unique(array_filter(array_map(fn ($t) => Str::lower(trim($t)), explode(',', $data['tags_text'] ?? '')))));
        $fields['quick_info'] = collect(preg_split('/\R/', $data['quick_info_text'] ?? ''))
            ->map(fn ($line) => array_map('trim', explode(':', $line, 2)))
            ->filter(fn ($pair) => count($pair) === 2 && $pair[0] !== '' && $pair[1] !== '')
            ->values()->all();
        $fields['is_active'] = $request->boolean('is_active');
        $fields['is_popular'] = $request->boolean('is_popular');

        $images = $product->images ?? [];
        foreach ($request->file('new_images', []) as $file) {
            if ($path = Uploads::store($file, 'products')) {
                $images[] = $path;
            }
        }
        $fields['images'] = array_values($images);

        DB::transaction(function () use ($product, $fields, $data) {
            $product->fill($fields)->save();
            $keep = [];
            foreach (array_values($data['variants'] ?? []) as $i => $v) {
                $variant = ! empty($v['id']) ? $product->variants()->find($v['id']) : null;
                $variant ??= $product->variants()->make();
                $variant->fill([
                    'weight_label' => $v['weight_label'],
                    'sku' => $v['sku'] ?? null,
                    'mrp' => ($v['mrp'] ?? '') === '' ? null : $v['mrp'],
                    'price' => $v['price'],
                    'stock' => $v['stock'],
                    'sort_order' => $i + 1,
                ])->save();
                $keep[] = $variant->id;
            }
            $product->variants()->whereNotIn('id', $keep)->delete();
        });

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product saved');
    }

    private function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function toggle(Request $request, Product $product)
    {
        $field = $request->validate(['field' => ['required', Rule::in(['is_active', 'is_popular'])]])['field'];
        $product->update([$field => ! $product->{$field}]);

        return back()->with('success', 'Product updated');
    }

    public function removeImage(Request $request, Product $product)
    {
        $path = $request->input('path');
        $images = array_values(array_filter($product->images ?? [], fn ($i) => $i !== $path));
        if (count($images) !== count($product->images ?? [])) {
            $product->update(['images' => $images]);
            Uploads::delete($path);
        }

        return back()->with('success', 'Image removed');
    }

    public function destroy(Product $product)
    {
        if ($product->hasOrders()) {
            $product->update(['is_active' => false]);

            return redirect()->route('admin.products.index')->with('success', 'This product has orders, so it was hidden instead of deleted.');
        }
        foreach ($product->images ?? [] as $path) {
            Uploads::delete($path);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted');
    }
}
