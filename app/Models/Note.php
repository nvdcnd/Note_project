<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $table = 'note';

    protected $fillable = [
        'title',
        'description',
        'creater_id',
        'organizationID',
        'replied_note_id',
    ];

    public function creater()
    {
        return $this->belongsTo(User::class, 'creater_id');
    }

    public function shared_notes()
    {
        return $this->belongsToMany(User::class, 'PivotForNote', 'note_id', 'shared_with');
    }

    public function replies()
    {
        return $this->hasMany(Note::class, 'replied_note_id');
    }
}
