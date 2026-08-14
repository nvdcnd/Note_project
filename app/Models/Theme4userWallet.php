<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme4userWallet extends Model
{
    protected $table = 'theme4user_wallets';

    protected $fillable = [
        'userID',
        'theme4ID',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userID');
    }

    public function theme()
    {
        return $this->belongsTo(Theme4user::class, 'theme4ID');
    }
}
