<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme_request extends Model
{
    protected $table = 'theme_requests';
    protected $fillable = [
        'name',
        'description',
        'style',
        'drag_type',
        'price',
    ];
}
