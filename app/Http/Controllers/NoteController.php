<?php

namespace App\Http\Controllers;

use App\Models\MarkAsDone;
use App\Models\Note;
use App\Models\PivotForNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    public function create_note(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
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
            'description' => 'required',
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
        /** @var PivotForNote|null $pivot */
        $pivot = PivotForNote::query()->find($id);
        if (! $pivot) {
            return redirect()->route('home')->with('error', 'Note record not found');
        }
        /** @var Note|null $note */
        $note = Note::query()->find($pivot->note_id);
        PivotForNote::destroy($pivot->id);
        if ($note) {
            Note::destroy($note->id);
        }

        return redirect()->route('home')->with('success', 'Note deleted successfully');
    }

    public function edit_note(Request $request, $id)
    {
        $user = Auth::user();
        $note = Note::query()->find($id);
        $pivot = PivotForNote::query()->where('note_id', $id)->where('shared_with', $user->id)->first();

        if (! $note || ($note->creater_id != $user->id && ! $pivot)) {
            return redirect()->route('note', $id)->with('error', 'You cannot edit this note');
        }

        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        $data = $request->all();
        $note->title = $data['title'];
        $note->description = $data['description'];
        $note->save();

        return redirect()->route('note', $id)->with('success', 'Note updated successfully');
    }
}
