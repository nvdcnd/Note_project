<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Note extends Model{
    protected $fillable = [
        'title',
        'description',
        'creater_id',
    ];

    public function creater(){
        return $this->belongsTo(User::class,'creater_id');
    }

    public function shared_notes(){
        return $this->belongsToMany(User::class,'pivot_for_note','note_id','shared_with');
    }

    public function replies(){
        return $this->hasMany(Note::class,'note_id');
    }
}
