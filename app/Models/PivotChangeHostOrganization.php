<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PivotChangeHostOrganization extends Model
{
    protected $table = 'pivot_change_host_organizations';

    protected $fillable = [
        'organizationID',
        'current_host_ID',
        'new_host_ID',
        'new_host_acceptance_status',
    ];

    protected $casts = [
        'new_host_acceptance_status' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organizationID');
    }

    public function currentHost()
    {
        return $this->belongsTo(User::class, 'current_host_ID');
    }

    public function newHost()
    {
        return $this->belongsTo(User::class, 'new_host_ID');
    }
}
