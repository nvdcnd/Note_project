<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationsMember extends Model
{
    protected $table = 'organizations_member';

    protected $fillable = [
        'organizationID',
        'userID',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organizationID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userID');
    }
}
