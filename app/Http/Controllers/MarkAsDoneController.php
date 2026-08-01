<?php

namespace App\Http\Controllers;

use App\Models\Mark_as_done;
use Illuminate\Http\Request;

class MarkAsDoneController extends Controller
{
    public function mark_as_done(Request $request, $noteID){
        $user = Auth::user();
        $note = Note::find($noteID);
        if(!$note){
            return redirect()->route('note')->with('error', 'Note not found');
        }
        $pivot = pivot_for_note::where('noteID', $noteID)->where('userID', $user->id)->first();
        if($pivot or ($note->creater_id == $user->id)){
            $mark_as_done = Mark_as_done::where('noteID', $noteID)->where('userID', $user->id)->first();
            if($mark_as_done){
                $mark_as_done->status = true;
                $mark_as_done->save();
                return redirect()->route('note')->with('success', 'Note marked as done');
            }else{
                $mark_as_done = new Mark_as_done();
                $mark_as_done->noteID = $noteID;
                $mark_as_done->userID = $user->id;
                $mark_as_done->status = true;
                $mark_as_done->save();
                return redirect()->route('note')->with('success', 'Note marked as done');
            }
        }
        else {
            return redirect()->route('note')->with('error', 'You are not authorized to mark this note as done');
        } 
    } 
}
