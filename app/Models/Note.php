<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'note';

    protected $fillable = [
        'title',
        'description',
        'creater_id',
        'organizationID',
        'org_done',
        'replied_note_id',
    ];

    protected $casts = [
        'org_done' => 'boolean',
    ];

    public function creater()
    {
        return $this->belongsTo(User::class, 'creater_id');
    }

    public function shared_notes()
    {
        return $this->belongsToMany(User::class, 'pivot_for_note', 'note_id', 'shared_with');
    }

    public function shares()
    {
        return $this->hasMany(PivotForNote::class, 'note_id');
    }

    public function replies()
    {
        return $this->hasMany(Note::class, 'replied_note_id');
    }

    public function replynotes()
    {
        return $this->hasMany(Replynote::class, 'noteID');
    }

    public function markAsDone()
    {
        return $this->hasMany(MarkAsDone::class, 'noteID');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organizationID');
    }

    public function scopeVisibleTo($query, $userId)
    {
        return $query->where('creater_id', $userId)
            ->orWhereIn('id', PivotForNote::query()->where('shared_with', $userId)->select('note_id'));
    }
}
