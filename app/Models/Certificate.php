<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];
}
