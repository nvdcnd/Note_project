<?php

namespace App\Http\Controllers;

use App\Models\MarkAsDone;
use App\Models\Note;
use App\Models\PivotForNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarkAsDoneController extends Controller
{
    public function mark_as_done(Request $request, $noteID)
    {
        $user = Auth::user();
        $note = Note::find($noteID);
        if (! $note) {
            return redirect()->route('home')->with('error', 'Không tìm thấy ghi chú.');
        }
        $pivot = PivotForNote::where('note_id', $noteID)->where('shared_with', $user->id)->first();
        if ($pivot or ($note->creater_id == $user->id)) {
            $mark_as_done = MarkAsDone::where('noteID', $noteID)->where('userID', $user->id)->first();
            if ($mark_as_done) {
                $mark_as_done->status = true;
                $mark_as_done->save();

                return redirect()->route('note', $noteID)->with('success', 'Đã đánh dấu ghi chú hoàn thành.');
            } else {
                $mark_as_done = new MarkAsDone;
                $mark_as_done->noteID = $noteID;
                $mark_as_done->userID = $user->id;
                $mark_as_done->status = true;
                $mark_as_done->save();

                return redirect()->route('note', $noteID)->with('success', 'Đã đánh dấu ghi chú hoàn thành.');
            }
        } else {
            return redirect()->route('note', $noteID)->with('error', 'Bạn không có quyền đánh dấu ghi chú này.');
        }
    }

    public function undo_mark_as_done(Request $request, $id)
    {
        $user = Auth::user();
        $note = Note::find($id);
        if (! $note) {
            return redirect()->route('home')->with('error', 'Không tìm thấy ghi chú.');
        }
        $pivot = PivotForNote::where('note_id', $id)->where('shared_with', $user->id)->first();
        if ($pivot or ($note->creater_id == $user->id)) {
            $mark_as_done = MarkAsDone::where('noteID', $id)->where('userID', $user->id)->first();
            if ($mark_as_done) {
                $mark_as_done->status = false;
                $mark_as_done->save();

                return redirect()->route('note', $id)->with('success', 'Đã bỏ đánh dấu hoàn thành.');
            } else {
                $mark_as_done = new MarkAsDone;
                $mark_as_done->noteID = $id;
                $mark_as_done->userID = $user->id;
                $mark_as_done->status = false;
                $mark_as_done->save();

                return redirect()->route('note', $id)->with('success', 'Đã bỏ đánh dấu hoàn thành.');
            }
        } else {
            return redirect()->route('note', $id)->with('error', 'Bạn không có quyền bỏ đánh dấu ghi chú này.');
        }
    }
}
