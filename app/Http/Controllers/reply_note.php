<?php

namespace App\Http\Controllers;
use App\Models\Note;
use Illuminate\Http\Request;

class reply_note extends Controller
{
    public function reply_note(Request $request, $noteid, $noterepliedid){
        $note = Note::find($noteid);
        $replied_note = Note::find($noterepliedid);
        if($note && $replied_note){
            $note2 = Note::create([
                "note_id"=> $noteid,
                "replied_note_id"=> $noterepliedid,
            ]);
            return redirect('note')->with('success', 'Note replied successfully');
        }else{
            return redirect('note')->with('error', 'Note not replied');
        }
    }

    public function unreply_note(Request $request, $id){
        $note = Note::find($id);
        if($note->creater_id != Auth::user()->id){
            return redirect()->route('note', $note->id)->with('error', 'You are not authorized to delete this note');
        }
        $note->delete();
        return redirect()->route('note', $note->id)->with('success', 'Note deleted successfully');
    }
}
