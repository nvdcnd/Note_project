<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PivotForNote extends Model
{
    use SoftDeletes;

    protected $table = 'pivot_for_note';

    protected $fillable = [
        'note_id',
        'shared_with',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class, 'note_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'shared_with', 'id');
    }
}
