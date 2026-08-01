<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class note extends Controller
{
    public function create_note(Request $request){
        $data = $request->all();
        $note = new Note();
        $note->title = $data['title'];
        $note->description = $data['description'];
        $note->creater_id = Auth::user()->id;
        $note->save();

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
        if(!$pivot){
            return redirect('note')->with('error', 'Note not found');
        }
        $pivot->delete();
        return redirect('note')->with('success', 'Note deleted successfully');
    }

}
