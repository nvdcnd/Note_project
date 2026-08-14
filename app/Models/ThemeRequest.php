<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeRequest extends Model
{
    protected $table = 'theme_requests';

    protected $fillable = [
        'name',
        'description',
        'style',
        'drag_type',
        'price',
        'catalog_link',
        'status',
        'email',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];
}
