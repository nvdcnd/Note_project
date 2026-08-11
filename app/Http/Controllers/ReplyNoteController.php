<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\PivotForNote;
use App\Models\Replynote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReplyNoteController extends Controller
{
    public function reply_note(Request $request, $id)
    {
        $note = Note::find($id);
        if (! $note) {
            return redirect()->route('home')->with('error', 'Note not found');
        }

        $canReply = $note->creater_id === Auth::id()
            || PivotForNote::query()
                ->where('note_id', $note->id)
                ->where('shared_with', Auth::id())
                ->exists();

        if (! $canReply) {
            return redirect()->route('note', $note->id)->with('error', 'You are not authorized to reply to this note');
        }

        $validated = $request->validate([
            'description' => ['required', 'string'],
        ]);

        Replynote::create([
            'description' => $validated['description'],
            'noteID' => $note->id,
            'userID' => Auth::id(),
        ]);

        return redirect()->route('note', $note->id)->with('success', 'Reply added successfully');
    }

    public function delete_reply(Request $request, $id)
    {
        $reply = Replynote::find($id);
        if (! $reply) {
            return redirect()->route('home')->with('error', 'Reply not found');
        }

        if ($reply->userID !== Auth::id()) {
            return redirect()->route('note', $reply->noteID)->with('error', 'Only the author can delete this reply');
        }

        $reply->delete();

        return redirect()->route('note', $reply->noteID)->with('success', 'Reply deleted successfully');
    }
}
