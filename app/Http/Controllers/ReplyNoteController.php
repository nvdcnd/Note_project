<?php

namespace App\Http\Controllers;
use App\Models\Note;
use App\Models\Mark_as_done;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReplyNoteController extends Controller
{
    public function reply_note(Request $request, $noteid){
        $replied_note = Note::find($noteid);
        if($replied_note){
            $data = $request->all();
            $note = new Note();
            $note->title = $data['title'];
            $note->description = $data['description'];
            $note->creater_id = Auth::user()->id;
            $note->replied_note_id = $replied_note->id;
            $note->save();

            $mark_as_done = new Mark_as_done();
            $mark_as_done->noteID = $note->id;
            $mark_as_done->userID = Auth::user()->id;
            $mark_as_done->status = false;
            $mark_as_done->save();

            return redirect()->route('note', $replied_note->id)->with('success', 'Note replied successfully');
        }else{
            return redirect()->route('home')->with('error', 'Note not replied');
        }
    }

    public function unreply_note(Request $request, $id){
        $note = Note::find($id);
        if(!$note || $note->creater_id != Auth::user()->id){
            return redirect()->route('home')->with('error', 'You are not authorized to delete this note');
        }
        $note->delete();
        return redirect()->route('home')->with('success', 'Note deleted successfully');
    }
}
