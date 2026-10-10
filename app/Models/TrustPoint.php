<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustPoint extends Model
{
    public const ICONS = ['wheat-off' => 'Grain free', 'flask-conical' => 'Lab tested', 'sprout' => 'Natural', 'shield-check' => 'Quality', 'paw' => 'Paw', 'award' => 'Award'];

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
