<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PivotForNote extends Model
{
    protected $table = 'pivot_for_note';

    protected $fillable = [
        'note_id',
        'shared_with',
    ];
}
