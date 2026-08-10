<?php

namespace App\Http\Controllers;

use App\Mail\Mail40account;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Mail\UserEmail;
use App\Models\Note;
use App\Models\PivotForNote;
use App\Models\User;
//use Illuminate\Http\Request;
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
        $sharedwith = collect($data['shared_with'] ?? [])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => strtolower(trim($value)))
            ->unique()
            ->values()
            ->all();
        $noteModel = Note::find($noteid);
        $newShares = [];
        $unregisteredEmails = [];
        $skippedExistingShares = 0;

        if (! $noteModel) {
            return redirect()->route('home')->with('error', 'Note not found');
        }

        foreach ($sharedwith as $userEmail) {
            $userModel = User::where('email', $userEmail)->first();

            if ($userModel) {
                $alreadyShared = PivotForNote::query()
                    ->where('note_id', $noteModel->id)
                    ->where('shared_with', $userModel->id)
                    ->exists();

                if ($alreadyShared) {
                    $skippedExistingShares++;

                    continue;
                }

                PivotForNote::create([
                    'note_id' => $noteModel->id,
                    'shared_with' => $userModel->id,
                ]);
                $newShares[] = $userModel;
            } else {
                $unregisteredEmails[] = $userEmail;
            }
        }

        foreach ($newShares as $userModel) {
            Mail::to($userModel->email)->queue(new UserEmail($userModel, $noteModel));
        }

        if (count($unregisteredEmails) > 0) {
            $this->mail_for_no_account($unregisteredEmails, $noteModel);
        }

        if (count($newShares) === 0 && count($unregisteredEmails) === 0) {
            return redirect()->route('note', $noteModel->id)->with('warning', 'No valid recipients were provided');
        }

        $message = 'Note shared successfully';
        if ($skippedExistingShares > 0) {
            $message = 'Note shared successfully. Skipped '.$skippedExistingShares.' recipient(s) that were already shared.';
        }

        //return redirect()->route('note', $noteModel->id)->with('success', $message);
        return response()->json(['message'=> $message],200);
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
