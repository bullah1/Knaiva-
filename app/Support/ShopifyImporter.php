<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Imports a Shopify product export (CSV) into the Laravel catalogue.
 * Products are matched by Handle, so running it again updates instead of duplicating.
 */
class ShopifyImporter
{
    /** Shopify "Type" => category slug */
    public const TYPE_TO_CATEGORY = [
        'Cat Dry Food' => 'cat-dry-food',
        'Cat Wet Food' => 'cat-wet-food',
        'Cat Treats' => 'cat-treats',
        'Cat Litter' => 'cat-litter',
        'Dog Dry Food' => 'dog-food',
        'Pet Wipes' => 'pet-wipes',
        'Pet Accessories' => 'pet-accessories',
    ];

    public array $log = [];

    public function import(string $path, bool $downloadImages = false): int
    {
        $rows = $this->readCsv($path);
        $groups = [];
        foreach ($rows as $row) {
            if (! empty($row['Handle'])) {
                $groups[$row['Handle']][] = $row;
            }
        }
        $categories = Category::pluck('id', 'slug');
        $count = 0;

        foreach ($groups as $handle => $productRows) {
            $first = $productRows[0];
            if (empty($first['Title'])) {
                $this->log[] = "skip {$handle}: no title";

                continue;
            }
            $parsed = $this->parseBody($first['Body (HTML)'] ?? '');
            $tags = array_values(array_filter(array_map('trim', explode(',', $first['Tags'] ?? ''))));
            $type = trim($first['Type'] ?? '');

            $images = [];
            foreach ($productRows as $r) {
                if (! empty($r['Image Src'])) {
                    $images[(int) ($r['Image Position'] ?: count($images) + 1)] = $r['Image Src'];
                }
            }
            ksort($images);
            $images = array_values(array_unique($images));
            if ($downloadImages) {
                $images = array_map(fn ($url) => $this->download($url, $handle), $images);
            }

            $variants = [];
            foreach ($productRows as $r) {
                if (($r['Variant Price'] ?? '') === '' && ($r['Option1 Value'] ?? '') === '') {
                    continue;
                }
                $label = trim($r['Option1 Value'] ?? '');
                if ($label === '' || $label === 'Default Title') {
                    $label = $this->singleLabel($first['Title'], $type);
                }
                $variants[] = [
                    'weight_label' => self::cleanSize($label),
                    'sku' => $r['Variant SKU'] ?: null,
                    'price' => (float) ($r['Variant Price'] ?: 0),
                    'mrp' => ($r['Variant Compare At Price'] ?? '') !== '' ? (float) $r['Variant Compare At Price'] : null,
                ];
            }

            $low = array_map('strtolower', $tags);
            $active = strtolower($first['Status'] ?? 'active') === 'active' && strtoupper($first['Published'] ?? 'TRUE') !== 'FALSE'
                && collect($variants)->contains(fn ($v) => $v['price'] > 0);

            DB::transaction(function () use ($handle, $first, $parsed, $tags, $low, $type, $images, $variants, $categories, $active) {
                $product = Product::firstOrNew(['slug' => $handle]);
                $product->fill([
                    'name' => $first['Title'],
                    'short_description' => Str::limit($parsed['intro'], 300),
                    'description' => trim($parsed['intro']."\n\n".($parsed['best_for'] ? 'Best for: '.implode(', ', $parsed['best_for']).'.' : '')),
                    'feeding_guide' => $parsed['how'] ?: null,
                    'features' => $parsed['benefits'],
                    'quick_info' => $parsed['quick_info'],
                    'images' => $images,
                    'tags' => $low,
                    'brand' => $first['Vendor'] && $first['Vendor'] !== 'Kaniva' ? $first['Vendor'] : ucfirst(strtok($first['Title'], ' ')),
                    'sku' => $variants ? preg_replace('/-\d+$/', '', (string) $variants[0]['sku']) : null,
                    'category_id' => $categories[self::TYPE_TO_CATEGORY[$type] ?? ''] ?? null,
                    'life_stage' => collect(['Kitten', 'Senior', 'Adult', 'All Life Stages'])->first(fn ($s) => in_array(strtolower($s), $low)),
                    'food_type' => match (true) { str_contains($type, 'Dry') => 'dry', str_contains($type, 'Wet') => 'wet', str_contains($type, 'Treat') => 'treat', default => Str::slug($type) },
                    'flavor' => collect(['Chicken', 'Tuna', 'Salmon', 'Lamb', 'Crab', 'Scallop', 'Shrimp', 'Lobster', 'Squid', 'Cod', 'Fish'])->filter(fn ($f) => in_array(strtolower($f), $low))->implode(', ') ?: null,
                    'seo_title' => $first['SEO Title'] ?: null,
                    'seo_description' => $first['SEO Description'] ?: null,
                    'seo_keywords' => implode(', ', array_slice($tags, 0, 8)),
                    'is_active' => $active,
                ]);
                if (! $product->exists) {
                    $product->rating = 0;
                    $product->review_count = 0;
                }
                $product->save();

                // Keep stock of existing sizes; new sizes start at 100 until you set real stock.
                $existing = $product->variants()->get()->keyBy('weight_label');
                $keep = [];
                foreach ($variants as $i => $v) {
                    $variant = $existing[$v['weight_label']] ?? $product->variants()->make(['stock' => 100]);
                    $variant->fill($v + ['sort_order' => $i + 1])->save();
                    $keep[] = $variant->id;
                }
                $product->variants()->whereNotIn('id', $keep)->delete();
            });
            $count++;
            if (! $active) {
                $this->log[] = "hidden {$handle}: draft or no price";
            }
        }

        return $count;
    }

    /** "500 gm" → "500g", "1.3 kg" → "1.3kg" */
    public static function cleanSize(string $text): string
    {
        return preg_replace_callback('/(\d(?:[\d.]*))\s*(gm|g|kg|ml|l)\b/i', fn ($m) => $m[1].(strtolower($m[2]) === 'gm' ? 'g' : (strtolower($m[2]) === 'l' ? 'L' : strtolower($m[2]))), $text);
    }

    private function singleLabel(string $title, string $type): string
    {
        if (preg_match('/(\d+(?:\.\d+)?\s?(?:L|kg|g|ml))\b/i', $title, $m)) {
            return str_replace(' ', '', $m[1]);
        }
        if (preg_match('/(\d+)\s*Sheets/i', $title, $m)) {
            return $m[1].' Sheets';
        }

        return match ($type) {
            'Cat Wet Food' => '1 Pouch',
            default => '1 Pc',
        };
    }

    /** Pulls the structured parts out of the description HTML. */
    private function parseBody(string $html): array
    {
        $out = ['intro' => '', 'benefits' => [], 'best_for' => [], 'how' => '', 'quick_info' => []];
        if (preg_match('#<div[^>]*kaniva-quick-info[^>]*>(.*?)</div>#s', $html, $m)) {
            preg_match_all('#<li><strong>(.*?):</strong>\s*(.*?)</li>#s', $m[1], $items, PREG_SET_ORDER);
            foreach ($items as $it) {
                $out['quick_info'][] = [html_entity_decode(strip_tags($it[1])), self::cleanSize(html_entity_decode(strip_tags($it[2])))];
            }
            $html = str_replace($m[0], '', $html);
        }
        if (preg_match('#<p>(.*?)</p>#s', $html, $m)) {
            $out['intro'] = html_entity_decode(strip_tags($m[1]));
        }
        $section = function (string $heading) use ($html) {
            if (preg_match('#<h3>'.preg_quote($heading, '#').'</h3>\s*<ul>(.*?)</ul>#s', $html, $m)) {
                preg_match_all('#<li>(.*?)</li>#s', $m[1], $li);

                return array_map(fn ($x) => html_entity_decode(strip_tags($x)), $li[1]);
            }

            return [];
        };
        $out['benefits'] = $section('Key Benefits') ?: $section('Key Features');
        $out['best_for'] = $section('Best For');
        if (preg_match('#<h3>How to Use</h3>\s*<p>(.*?)</p>#s', $html, $m)) {
            $out['how'] = html_entity_decode(strip_tags($m[1]));
        }

        return $out;
    }

    private function download(string $url, string $handle): string
    {
        try {
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                return $url;
            }
            $name = $handle.'-'.substr(md5($url), 0, 8).'.'.$ext;
            $dir = public_path('uploads/products');
            File::ensureDirectoryExists($dir);
            if (! File::exists("{$dir}/{$name}")) {
                $res = Http::timeout(30)->get($url);
                if (! $res->successful()) {
                    $this->log[] = "image failed ({$res->status()}): {$url}";

                    return $url;
                }
                File::put("{$dir}/{$name}", $res->body());
            }

            return "uploads/products/{$name}";
        } catch (\Throwable $e) {
            $this->log[] = "image failed: {$url} — {$e->getMessage()}";

            return $url;
        }
    }

    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fh);
        }
        $header = fgetcsv($fh, 0, ',', '"', '');
        $rows = [];
        while (($data = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            if (count($data) === count($header)) {
                $rows[] = array_combine($header, $data);
            }
        }
        fclose($fh);

        return $rows;
    }
}
