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

    public function share_note(Request $request, $id)
    {
        $note = Note::find($id);
        if (! $note) {
            return redirect()->route('home')->with('error', 'Note not found');
        }
        $data = $request->all();
        $email = $data['email'];
        $note->shared_with = $email;
        $note->save();

        return redirect()->route('note', $note->id)->with('success', 'Note shared successfully');
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
}
