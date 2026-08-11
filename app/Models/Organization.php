<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'description',
        'logo_url',
        'banner_url',
        'balance',
        'hostID',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'hostID');
    }

    public function members()
    {
        return $this->hasMany(OrganizationsMember::class, 'organizationID');
    }

    public function activeMembers()
    {
        return $this->members()->where('status', true);
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'organizationID');
    }

    public function themeWallets()
    {
        return $this->hasMany(Theme4orgWallet::class, 'organizationID');
    }
}
