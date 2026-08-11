<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme4org extends Model
{
    protected $table = 'theme4orgs';

    protected $fillable = [
        'name',
        'description',
        'style',
        'drag_type',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'style' => 'array',
    ];

    public function styles()
    {
        return $this->hasMany(Theme4orgStyle::class, 'theme4ID');
    }

    public function wallets()
    {
        return $this->hasMany(Theme4orgWallet::class, 'theme4ID');
    }
}
