<?php

use App\Models\SiteSetting;
use App\Support\Icons;

if (! function_exists('bdt')) {
    /** Format an amount as Bangladeshi Taka, e.g. ৳1,850 */
    function bdt(float|int|string|null $amount): string
    {
        return '৳'.number_format(round((float) $amount));
    }
}

if (! function_exists('site_settings')) {
    /** site_settings('contact.phone') or site_settings('delivery') */
    function site_settings(?string $key = null, mixed $default = null): mixed
    {
        $all = SiteSetting::grouped();

        return $key === null ? $all : data_get($all, $key, $default);
    }
}

if (! function_exists('site_origin')) {
    /** Canonical origin used in canonical tags, JSON-LD and the sitemap. */
    function site_origin(): string
    {
        $base = site_settings('seo.canonical_base') ?: config('app.url');

        return rtrim($base, '/');
    }
}

if (! function_exists('lang')) {
    function lang(): string
    {
        return app()->getLocale() === 'bn' ? 'bn' : 'en';
    }
}

if (! function_exists('pick')) {
    /** Choose the Bangla text when the visitor picked বাংলা and it exists. */
    function pick(?string $en, ?string $bn = null): string
    {
        return lang() === 'bn' ? ($bn ?: $en ?: '') : ($en ?: '');
    }
}

if (! function_exists('icon')) {
    /** Inline SVG icon. */
    function icon(string $name, string $class = 'size-5', array $attrs = []): string
    {
        $aliases = ['paw' => 'paw-print', 'check-circle' => 'circle-check'];
        $name = $aliases[$name] ?? $name;
        $paths = Icons::PATHS[$name] ?? Icons::PATHS['paw-print'];
        $extra = '';
        foreach ($attrs as $k => $v) {
            $extra .= ' '.$k.'="'.e($v).'"';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="'.e($class).'" aria-hidden="true"'.$extra.'>'.$paths.'</svg>';
    }
}

if (! function_exists('whatsapp_link')) {
    function whatsapp_link(?string $number, ?string $message = null): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $number);
        if (! $digits) {
            return null;
        }

        return 'https://wa.me/'.$digits.($message ? '?text='.rawurlencode($message) : '');
    }
}

if (! function_exists('upload_url')) {
    /** Turn a stored path (uploads/... or images/...) or full URL into a URL. */
    function upload_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
    }
}

if (! function_exists('breadcrumb_schema')) {
    /** @param list<array{0:string,1:?string}> $crumbs */
    function breadcrumb_schema(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($c, $i) => array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $c[0],
                'item' => $c[1] ? site_origin().$c[1] : null,
            ]), $crumbs, array_keys($crumbs)),
        ];
    }
}
