<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme4user extends Model
{
    protected $table = 'theme4users';

    protected $fillable = [
        'name',
        'description',
        'drag_type',
        'price',
    ];
}
