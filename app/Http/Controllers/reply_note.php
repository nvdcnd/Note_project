<?php

namespace App\Http\Controllers;
use App\Models\Note;
use Illuminate\Http\Request;

class reply_note extends Controller
{
    public function reply_note(Request $request, $noteid){
        $replied_note = Note::find($noteid);
        //$replied_note = Note::find($noterepliedid);    
        if($replied_note){
            $data = $request->all();
            $note = new Note();
            $note->title = $data['title'];
            $note->description = $data['description'];
            $note->creater_id = Auth::user()->id;
            $note->reply_note_id = $replied_note->id;
            $note->save();

            $mark_as_done = new Mark_as_done();
            $mark_as_done->noteID = $note->id;
            $mark_as_done->userID = Auth::user()->id;
            $mark_as_done->status = false;
            $mark_as_done->save();
            /*
            $note2 = Note::create([
                "note_id"=> $noteid,
                "replied_note_id"=> $noterepliedid,
            ]);
            */
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
