<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkAsDone extends Model
{
    protected $table = 'mark_as_dones';

    protected $fillable = [
        'noteID',
        'userID',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class, 'noteID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userID');
    }
}
