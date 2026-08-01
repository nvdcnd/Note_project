<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\UserEmail;
use Illuminate\Support\Facades\Mail;
use App\Models\Note;
use App\Models\User;
use App\Models\pivot_for_note;
use App\Models\mark_as_done;
use App\Models\reply_note;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;


class pivot_for_note_controller extends Controller
{
    function mail_for_no_account(Request $request, $users, $noteid){
        foreach($users as $user){
            Mail::to($user)->send(new Mail40account($user, $noteid));
        }
    }

    public function share_note(Request $request, $noteid){
        $data = $request->all();
        $sharedwith = $data['shared_with'];
        $noteID = Note::find($noteid);
        $no_account = [];
        if(!$noteID){
            return redirect('note')->with('error', 'Note not found');
        }
        foreach($sharedwith as $user){
            $userID = User::where('email',$user)->first();
            if($userID){
                $note = pivot_for_note::create([
                    "note_id"=> $noteID->id,
                    "shared_with"=> $userID->id,
                ]);
                Mail::to($userID->email)->send(new UserEmail($note));
            } else {
                $no_account[] = $user;
            }
        }
        if(count($no_account) == 0){
            pivot_for_note::mail_for_no_account($no_account, $noteid);
            return redirect()->route('note', $noteID->id)->with('error', 'Note shared successfully with ' . count($no_account) . ' users');
        } else {
            return redirect()->route('note', $noteID->id)->with('success', 'Note shared successfully');
        }
    }

    public function undo_shared_note(Request $request, $id){
        $pivot = pivot_for_note::find($id);
        $note = Note::find($pivot->note_id);
        if($note->creater_id != Auth::user()->id){
            return redirect()->route('note', $note->id)->with('error', 'You are not authorized to delete this note');
        }
        $pivot->delete();
        return redirect()->route('note', $note->id)->with('success', 'Note deleted successfully');
    }
}
