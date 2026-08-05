<?php

namespace App\Http\Controllers;

use App\Models\MarkAsDone;
use App\Models\Note;
use App\Models\PivotForNote;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function create_note(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required'
        ]);
        $data = $request->all();
        $note = new Note;
        $note->title = $data['title'];
        $note->description = $data['description'];
        $note->creater_id = Auth::user()->id;
        $note->save();

        $mark_as_done = new MarkAsDone;
        $mark_as_done->noteID = $note->id;
        $mark_as_done->userID = Auth::user()->id;
        $mark_as_done->status = false;
        $mark_as_done->save();

        return redirect()->route('note', $note->id)->with('success', 'Note created successfully');
    }

    public function create_note_in_organization(Request $request, $organizationID)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required'
        ]);
        $data = $request->all();
        $note = new Note;
        $note->title = $data['title'];
        $note->description = $data['description'];
        $note->creater_id = Auth::user()->id;
        $note->organizationID = $organizationID;
        $note->save();

        $mark_as_done = new MarkAsDone;
        $mark_as_done->noteID = $note->id;
        $mark_as_done->userID = Auth::user()->id;
        $mark_as_done->status = false;
        $mark_as_done->save();

        return redirect()->route('note', $note->id)->with('success', 'Note created successfully');
    }


    public function delete_note_request(Request $request, $id)
    {
        $pivot = PivotForNote::find($id);
        if (! $pivot) {
            return redirect()->route('home')->with('error', 'Note record not found');
        }
        $note = Note::find($pivot->note_id);
        $pivot->delete();
        if ($note) {
            $note->delete();
        }

        return redirect()->route('home')->with('success', 'Note deleted successfully');
    }

    public function edit_note(Request $request, $id){
        $user = Auth::user();
        $note = Note::where('id',$id)->where('creatorID',$user->id)->orWhere('pivot_for_note.sharedwith',$userID)->exist();
        if (!$note) {
            return redirect()->route('note',$id)->with('error',"you cannot edit this note");
        } else {
            $request->validate([
            'title' => 'required',
            'description' => 'required'
        ]);
            $data = $request->all();
            $note->title = $data['title'];
            $note->description = $data['description'];
            $note::save();
        }
    }
}
