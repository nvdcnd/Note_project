<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    public function user_note_fetch(Request $request)
    {
        $offset = intval($request->input('offset', 0));
        $limit = 20;

        $note_list = Note::query()
            ->leftJoin('pivot_for_note', 'note.id', '=', 'pivot_for_note.note_id')
            ->where('note.user_id', '=', Auth::id(), 'and')
            ->where('pivot_for_note.shared_with', '=', Auth::id(), 'and')
            ->orderBy('note.created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get(['note.*']);

        return response()->json($note_list);
    }

    public function user_mark_as_done_fetch(Request $request)
    {
        $offset = intval($request->input('offset', 0));
        $limit = 20;

        $note_list = Note::query()
            ->leftJoin('pivot_for_note', 'note.id', '=', 'pivot_for_note.note_id')
            ->leftJoin('mark_as_dones', 'note.id', '=', 'mark_as_dones.noteID')
            ->where('note.user_id', '=', Auth::id(), 'and')
            ->where('pivot_for_note.shared_with', '=', Auth::id(), 'and')
            ->where('mark_as_dones.userID', '=', Auth::id(), 'and')
            ->orderBy('note.created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get(['note.*']);

        return response()->json($note_list);
    }

    public function user_mark_as_undone_fetch(Request $request)
    {
        $offset = intval($request->input('offset', 0));
        $limit = 20;

        $note_list = Note::query()
            ->leftJoin('pivot_for_note', 'note.id', '=', 'pivot_for_note.note_id')
            ->leftJoin('mark_as_dones', 'note.id', '=', 'mark_as_dones.noteID')
            ->where('note.user_id', '=', Auth::id(), 'and')
            ->where('pivot_for_note.shared_with', '=', Auth::id(), 'and')
            ->whereNull('mark_as_dones.userID', 'and', false)
            ->orderBy('note.created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get(['note.*']);

        return response()->json($note_list);
    }

    public function organization_note_fetch(Request $request, $id)
    {
        $offset = intval($request->input('offset', 0));
        $limit = 20;

        $note_list = Note::query()
            ->leftJoin('organizations_member', 'note.organizationID', '=', 'organizations_member.organizationID')
            ->where('note.organizationID', '=', $id, 'and')
            ->where('organizations_member.userID', '=', Auth::id(), 'and')
            ->orderBy('note.created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get(['note.*']);

        return response()->json($note_list);
    }

    public function organization_mark_as_done_fetch(Request $request, $id)
    {
        $offset = intval($request->input('offset', 0));
        $limit = 20;

        $note_list = Note::query()
            ->leftJoin('organizations_member', 'note.organizationID', '=', 'organizations_member.organizationID')
            ->leftJoin('mark_as_dones', 'note.id', '=', 'mark_as_dones.noteID')
            ->where('note.organizationID', '=', $id, 'and')
            ->where('organizations_member.userID', '=', Auth::id(), 'and')
            ->where('mark_as_dones.userID', '=', Auth::id(), 'and')
            ->orderBy('note.created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get(['note.*']);

        return response()->json($note_list);
    }

    public function organization_mark_as_undone_fetch(Request $request, $id)
    {
        $offset = intval($request->input('offset', 0));
        $limit = 20;

        $note_list = Note::query()
            ->leftJoin('organizations_member', 'note.organizationID', '=', 'organizations_member.organizationID')
            ->leftJoin('mark_as_dones', 'note.id', '=', 'mark_as_dones.noteID')
            ->where('note.organizationID', '=', $id, 'and')
            ->where('organizations_member.userID', '=', Auth::id(), 'and')
            ->whereNull('mark_as_dones.userID', 'and', false)
            ->orderBy('note.created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get(['note.*']);

        return response()->json($note_list);
    }
}
