<?php

namespace App\Http\Controllers;

use App\Mail\Mail40account;
use App\Mail\UserEmail;
use App\Models\Note;
use App\Models\PivotForNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

// use App\Mail;

class PivotForNoteController extends Controller
{
    public function mail_for_no_account($users, $noteid)
    {
        // $users is an array of email strings, $noteid is a Note model
        foreach ($users as $email) {
            Mail::to($email)->queue(new Mail40account($email, $noteid));
        }
    }

    public function share_note(Request $request, $noteid)
    {
        $request->validate([
            'shared_with' => 'required',
        ]);
        $data = $request->all();
        $sharedwith = $data['shared_with'] ?? [];
        $noteModel = Note::find($noteid);
        $no_account = [];
        if (! $noteModel) {
            return redirect()->route('home')->with('error', 'Note not found');
        }
        foreach ($sharedwith as $userEmail) {
            $userModel = User::where('email', $userEmail)->first();
            if ($userModel) {
                $pivot = PivotForNote::create([
                    'note_id' => $noteModel->id,
                    'shared_with' => $userModel->id,
                ]);
                // queue email to registered user
                Mail::to($userModel->email)->queue(new UserEmail($userModel, $noteModel));
            } else {
                $no_account[] = $userEmail;
            }
        }

        if (count($no_account) > 0) {
            // queue invitations for unregistered emails
            $this->mail_for_no_account($no_account, $noteModel);

            return redirect()->route('note', $noteModel->id)->with('success', 'Invitation sent to '.count($no_account).' unregistered users');
        }

        return redirect()->route('note', $noteModel->id)->with('success', 'Note shared successfully');
    }

    public function undo_shared_note(Request $request, $id)
    {
        $pivot = PivotForNote::find($id);
        if (! $pivot) {
            return redirect()->route('home')->with('error', 'Shared note record not found');
        }
        $note = Note::find($pivot->note_id);
        if (! $note || $note->creater_id != Auth::user()->id) {
            return redirect()->route('home')->with('error', 'You are not authorized to unshare this note');
        }
        $pivot->delete();

        return redirect()->route('note', $note->id)->with('success', 'Unshared note successfully');
    }
}
