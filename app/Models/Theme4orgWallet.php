<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme4orgWallet extends Model
{
    protected $table = 'theme4org_wallets';

    protected $fillable = [
        'organizationID',
        'theme4ID',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organizationID');
    }

    public function theme()
    {
        return $this->belongsTo(Theme4org::class, 'theme4ID');
    }
}
