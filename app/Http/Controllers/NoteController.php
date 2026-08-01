<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use App\Models\pivot_for_note;
use App\Models\mark_as_done;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class NoteController extends Controller
{
    public function create_note(Request $request){
        $data = $request->all();
        $note = new Note();
        $note->title = $data['title'];
        $note->description = $data['description'];
        $note->creater_id = Auth::user()->id;
        $note->save();

        $mark_as_done = new Mark_as_done();
        $mark_as_done->noteID = $note->id;
        $mark_as_done->userID = Auth::user()->id;
        $mark_as_done->status = false;
        $mark_as_done->save();

        //$check4reply = $data["reply"]


        return redirect('note')->with('success', 'Note created successfully');
    }

    public function create_note_in_organization(Request $request, $organizationID){
        $data = $request->all();
        $note = new Note();
        $note->title = $data['title'];
        $note->description = $data['description'];
        $note->creater_id = Auth::user()->id;
        $note->organizationID = $organizationID;
        $note->save();

        $mark_as_done = new Mark_as_done();
        $mark_as_done->noteID = $note->id;
        $mark_as_done->userID = Auth::user()->id;
        $mark_as_done->status = false;
        $mark_as_done->save();

        //$check4reply = $data["reply"]


        return redirect('note')->with('success', 'Note created successfully');
    }

    public function share_note(Request $request,$id){
        $note = Note::find($id);
        $data = $request->all();
        $email = $data['email'];
        $note->shared_with = $email;
        $note->save();

        return redirect('note')->with('success', 'Note shared successfully');
    }

    public function delete_note_request(Request $request, $id){
        $pivot = pivot_for_note::find($id);
        $note = Note::find($pivot->note_id);
        if(!$pivot){
            return redirect('note')->with('error', 'Note not found');
        }
        $pivot->delete();
        $note->delete();
        return redirect('note')->with('success', 'Note deleted successfully');
    }

}
