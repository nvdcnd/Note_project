<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Replynote extends Model
{
    protected $table = 'replynotes';

    protected $fillable = [
        'noteID',
        'userID',
        'description',
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
