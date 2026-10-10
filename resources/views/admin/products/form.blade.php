@extends('admin.layout')
@section('title', $product->exists ? 'Edit product' : 'New product')
@section('actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-ghost h-10">{!! icon('arrow-left') !!} All products</a>
    @if ($product->exists)
        <a href="{{ $product->url() }}" target="_blank" class="btn btn-outline h-10">{!! icon('eye') !!} View</a>
    @endif
@endsection

@section('content')
@php
    $variants = old('variants', $product->exists ? $product->variants->map(fn ($v) => $v->only(['id', 'weight_label', 'sku', 'mrp', 'price', 'stock']))->all() : [['id' => null, 'weight_label' => '1kg', 'sku' => '', 'mrp' => '', 'price' => '', 'stock' => 0]]);
    $v = fn ($key, $default = null) => old($key, $product->{$key} ?? $default);
@endphp
<form method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="grid gap-6 lg:grid-cols-[1fr_320px]">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="space-y-6">
        <section class="card-soft grid gap-4 p-5 md:grid-cols-2">
            <h2 class="font-extrabold md:col-span-2">Basic info</h2>
            <div><label class="label">Name (English) *</label><input name="name" value="{{ $v('name') }}" required class="input"></div>
            <div><label class="label">Name (বাংলা)</label><input name="name_bn" value="{{ $v('name_bn') }}" class="input"></div>
            <div><label class="label">URL slug</label><input name="slug" value="{{ $v('slug') }}" placeholder="auto from name" class="input"></div>
            <div><label class="label">SKU</label><input name="sku" value="{{ $v('sku') }}" class="input"></div>
            <div>
                <label class="label">Category</label>
                <select name="category_id" class="input">
                    <option value="">None</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected($v('category_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="label">Brand</label><input name="brand" value="{{ $v('brand', 'Kaniva') }}" class="input"></div>
            <div><label class="label">Life stage</label><input name="life_stage" value="{{ $v('life_stage') }}" placeholder="kitten, adult, all" class="input"></div>
            <div><label class="label">Food type</label><input name="food_type" value="{{ $v('food_type') }}" placeholder="dry, wet, supplement" class="input"></div>
            <div><label class="label">Flavour</label><input name="flavor" value="{{ $v('flavor') }}" class="input"></div>
            <div><label class="label">Tags (comma separated)</label><input name="tags_text" value="{{ old('tags_text', implode(', ', $product->tags ?? [])) }}" placeholder="cat, dry, adult" class="input"><p class="mt-1 text-xs text-muted-foreground">Used by shop filters: cat, dog, kitten, adult, dry, wet, supplement</p></div>
            <div class="md:col-span-2"><label class="label">Short description</label><textarea name="short_description" rows="2" class="textarea">{{ $v('short_description') }}</textarea></div>
            <div class="md:col-span-2">
                <label class="label">এক নজরে box <span class="font-normal text-muted-foreground">(one per line, “label: value”)</span></label>
                <textarea name="quick_info_text" rows="4" class="textarea" placeholder="কার জন্য: ৪ মাসের বেশি বয়সী বিড়াল&#10;ধরন: ড্রাই ফুড">{{ old('quick_info_text', collect($product->quick_info ?? [])->map(fn ($p) => $p[0].': '.$p[1])->implode("\n")) }}</textarea>
            </div>
            <div class="md:col-span-2"><label class="label">Features (one per line)</label><textarea name="features_text" rows="4" class="textarea">{{ old('features_text', implode("\n", $product->features ?? [])) }}</textarea></div>
        </section>

        <section class="card-soft p-5" x-data="{ rows: @js(array_values($variants)) }">
            <div class="flex items-center justify-between">
                <h2 class="font-extrabold">Sizes &amp; prices</h2>
                <button type="button" class="btn btn-outline h-9" @click="rows.push({ id: null, weight_label: '', sku: '', mrp: '', price: '', stock: 0 })">{!! icon('plus') !!} Add size</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="grid grid-cols-2 gap-2 rounded-xl bg-cream p-3 sm:grid-cols-[1fr_1fr_.8fr_.8fr_.7fr_auto] sm:items-end">
                        <input type="hidden" :name="`variants[${i}][id]`" :value="row.id">
                        <div><label class="label text-xs">Weight *</label><input class="input h-10" :name="`variants[${i}][weight_label]`" x-model="row.weight_label" required placeholder="1kg"></div>
                        <div><label class="label text-xs">SKU</label><input class="input h-10" :name="`variants[${i}][sku]`" x-model="row.sku"></div>
                        <div><label class="label text-xs">MRP ৳</label><input class="input h-10" type="number" step="0.01" min="0" :name="`variants[${i}][mrp]`" x-model="row.mrp"></div>
                        <div><label class="label text-xs">Price ৳ *</label><input class="input h-10" type="number" step="0.01" min="0" :name="`variants[${i}][price]`" x-model="row.price" required></div>
                        <div><label class="label text-xs">Stock *</label><input class="input h-10" type="number" min="0" :name="`variants[${i}][stock]`" x-model="row.stock" required></div>
                        <button type="button" class="btn btn-danger h-10" @click="rows.splice(i, 1)" aria-label="Remove size">{!! icon('trash-2') !!}</button>
                    </div>
                </template>
                <p x-show="rows.length === 0" class="text-sm text-muted-foreground">No sizes — customers cannot buy this product until you add one.</p>
            </div>
        </section>

        <section class="card-soft grid gap-4 p-5">
            <h2 class="font-extrabold">Details</h2>
            <div><label class="label">Description (English)</label><textarea name="description" rows="5" class="textarea">{{ $v('description') }}</textarea></div>
            <div><label class="label">Description (বাংলা)</label><textarea name="description_bn" rows="5" class="textarea">{{ $v('description_bn') }}</textarea></div>
            <div><label class="label">Ingredients</label><textarea name="ingredients" rows="3" class="textarea">{{ $v('ingredients') }}</textarea></div>
            <div><label class="label">Nutrition</label><textarea name="nutrition" rows="3" class="textarea">{{ $v('nutrition') }}</textarea></div>
            <div><label class="label">Feeding guide</label><textarea name="feeding_guide" rows="3" class="textarea">{{ $v('feeding_guide') }}</textarea></div>
        </section>

        <section class="card-soft grid gap-4 p-5 md:grid-cols-2">
            <h2 class="font-extrabold md:col-span-2">SEO</h2>
            <div class="md:col-span-2"><label class="label">SEO title <span class="font-normal text-muted-foreground">(50–60 characters)</span></label><input name="seo_title" value="{{ $v('seo_title') }}" maxlength="255" class="input"></div>
            <div class="md:col-span-2"><label class="label">SEO description <span class="font-normal text-muted-foreground">(150–160 characters)</span></label><textarea name="seo_description" rows="2" maxlength="500" class="textarea">{{ $v('seo_description') }}</textarea></div>
            <div><label class="label">SEO keywords</label><input name="seo_keywords" value="{{ $v('seo_keywords') }}" class="input"></div>
            <div><label class="label">Canonical URL (optional)</label><input name="canonical_url" value="{{ $v('canonical_url') }}" type="url" class="input"></div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="card-soft space-y-3 p-5 lg:sticky lg:top-6">
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="size-4 accent-[var(--color-primary)]"> Active (visible in shop)</label>
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_popular" value="1" @checked(old('is_popular', $product->is_popular)) class="size-4 accent-[var(--color-primary)]"> Popular (show on homepage)</label>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="label text-xs">Rating</label><input name="rating" type="number" step="0.1" min="0" max="5" value="{{ $v('rating', 0) }}" class="input h-10"></div>
                <div><label class="label text-xs">Review count</label><input name="review_count" type="number" min="0" value="{{ $v('review_count', 0) }}" class="input h-10"></div>
            </div>
            <button type="submit" class="btn btn-primary h-11 w-full">Save product</button>
        </section>

        <section class="card-soft p-5">
            <h2 class="font-extrabold">Images</h2>
            <p class="mt-1 text-xs text-muted-foreground">The first image is the main photo. JPG, PNG or WebP up to 4 MB.</p>
            @if (!empty($product->images))
                <div class="mt-3 grid grid-cols-3 gap-2">
                    @foreach ($product->images as $img)
                        <div class="relative">
                            <img src="{{ upload_url($img) }}" alt="" class="aspect-square w-full rounded-lg object-cover">
                            <button type="submit" form="remove-img-{{ $loop->index }}" class="absolute right-1 top-1 rounded-full bg-background/90 p-1 text-destructive shadow" aria-label="Remove image">{!! icon('x', 'size-3.5') !!}</button>
                        </div>
                    @endforeach
                </div>
            @elseif ($product->exists)
                <img src="{{ $product->mainImage() }}" alt="" class="mt-3 aspect-square w-24 rounded-lg object-cover opacity-70"><p class="text-xs text-muted-foreground">Default artwork</p>
            @endif
            <input type="file" name="new_images[]" multiple accept="image/*" class="mt-3 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-cream file:px-3 file:py-2 file:font-semibold file:text-primary">
        </section>
    </div>
</form>

@if ($product->exists)
    @foreach ($product->images ?? [] as $img)
        <form id="remove-img-{{ $loop->index }}" method="post" action="{{ route('admin.products.images.remove', $product) }}" onsubmit="return confirm('Remove this image?')">
            @csrf @method('DELETE')<input type="hidden" name="path" value="{{ $img }}">
        </form>
    @endforeach
    <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="mt-8" onsubmit="return confirm('Delete this product?')">
        @csrf @method('DELETE')
        <button class="btn btn-danger h-10">{!! icon('trash-2') !!} Delete product</button>
    </form>
@endif
@endsection
