<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroBanner extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
