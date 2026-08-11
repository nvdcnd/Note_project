<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme4user extends Model
{
    protected $table = 'theme4users';

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
        return $this->hasMany(Theme4userStyle::class, 'theme4userID');
    }

    public function wallets()
    {
        return $this->hasMany(Theme4userWallet::class, 'theme4ID');
    }
}
