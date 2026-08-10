<?php

namespace App\Http\Controllers;

//use App\Models\Replynote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Replynote;
use App\Models\Note;

class ReplynoteController extends Controller
{
    public function replynote(Request $request, $id){
        //$replynote = Replynote::find($id);
        $note = Note::find($id);
        $request->validate([
            "description" => 'required',
        ]);
        $data = $request->all();
        if($note){
            $reply = Replynote::create(
                [
                    'description'=> $data['description'],
                    'noteID' => $note->id,
                    'userID'=> Auth->user()->id,
                ]
            );
            $reply->save();
            return response()->json(['hello'=>'hello'], 200);
        } else {
            return response()->json(['note'=> 'not found'],404);
        }

    }

    public function deleteReplynote(Request $request, $id){
        $replynote = Replynote::find($id);
        if($replynote && $replynote->userID == Auth::user()->id){
            $replynote->delete();
            return response()->json(['delete'=> 'success'],200);
        } else {
            return response()->json(['no'=> 'no'],404);
        }
    }
}
