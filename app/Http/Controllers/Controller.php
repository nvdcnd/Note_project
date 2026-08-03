<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
//use App\Http\Requests;
//use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Note;
use Illuminate\Auth;

abstract class Controller
{
    public function user_note_fetch(Request $request){
        $offset = $request->input('offset');
        $limit = 20;

        $note_list = Note::where('user_id', auth()->user()->id)->where('PivotForNote.shared_with', auth()->user()->id)->orderBy('created_at','desc')->skip($offset)->take($limit)->get();

        return response()->json($note_list);
    }

    public function user_mark_as_done_fetch(Request $request){
        $offset = $request->input('offset');
        $limit = 20;

        $note_list = Note::where('user_id', auth()->user()->id)->where('PivotForNote.shared_with', auth()->user()->id)->where('mark_as_done.userID', auth()->user()->id)->orderBy('created_at','desc')->skip($offset)->take($limit)->get();

        return response()->json($note_list);
    }

    public function user_mark_as_undone_fetch(Request $request){
        $offset = $request->input('offset');
        $limit = 20;

        $note_list = Note::where('user_id', auth()->user()->id)->where('PivotForNote.shared_with', auth()->user()->id)->where('mark_as_done.userID', null)->orderBy('created_at','desc')->skip($offset)->take($limit)->get();

        return response()->json($note_list);
    }

    public function organization_note_fetch(Request $request, $id){
        $offset = $request->input('offset');
        $limit = 20;

        $note_list = Note::where('organization_id', $id)->where('OrganizationsMember.memberID', auth()->user()->id)->orderBy('created_at','desc')->skip($offset)->take($limit)->get();

        return response()->json($note_list);
    }

    public function organization_mark_as_done_fetch(Request $request, $id){
        $offset = $request->input('offset');
        $limit = 20;

        $note_list = Note::where('organization_id', $id)->where('organization.memberID', auth()->user()->id)->where('mark_as_done.userID', auth()->user()->id)->orderBy('created_at','desc')->skip($offset)->take($limit)->get();

        return response()->json($note_list);
    }

    public function organization_mark_as_undone_fetch(Request $request, $id){
        $offset = $request->input('offset');
        $limit = 20;

        $note_list = Note::where('organization_id', $id)->where('organization.memberID', auth()->user()->id)->where('mark_as_done.userID', null)->orderBy('created_at','desc')->skip($offset)->take($limit)->get();

        return response()->json($note_list);
    }

}
