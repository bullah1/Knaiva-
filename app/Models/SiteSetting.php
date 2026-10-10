<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    public const DEFAULTS = [
        'delivery' => ['inside_dhaka' => 60, 'outside_dhaka' => 120, 'free_delivery_threshold' => 2000],
        'payments' => ['cod' => true, 'bkash' => true, 'nagad' => true, 'card' => false, 'bkash_number' => '', 'nagad_number' => ''],
        'contact' => ['phone' => '', 'whatsapp' => '', 'email' => '', 'address' => 'Dhaka, Bangladesh', 'facebook' => '', 'instagram' => '', 'youtube' => ''],
        'seo' => ['site_name' => 'Kaniva Bangladesh', 'default_title' => 'Kaniva Bangladesh — Premium Cat & Dog Food', 'default_description' => '', 'canonical_base' => ''],
    ];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['value' => 'array'];

    /** All settings groups merged over defaults, cached for one request cycle + 10 min. */
    public static function grouped(): array
    {
        return Cache::remember('site_settings', 600, function () {
            $rows = static::query()->pluck('value', 'key')->map(fn ($v) => is_array($v) ? $v : json_decode($v, true));
            $out = [];
            foreach (self::DEFAULTS as $key => $defaults) {
                $out[$key] = array_merge($defaults, $rows[$key] ?? []);
            }

            return $out;
        });
    }

    public static function put(string $key, array $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('site_settings');
    }
}
