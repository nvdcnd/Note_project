<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authentication;
use App\Models\Note;
use App\Models\pivot_for_note;
use App\Models\Organization;
use App\Models\mark_as_done;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\MarkAsDoneController;
use App\Http\Controllers\reply_note;
use App\Http\Controllers\pivot_for_note_controller;


Route::get('/', function () {
    if(Auth::check()){
        $all_note = Note::where("creater_id",Auth::user()->id)->orWhere("pivot_for_note.shared_with",Auth::user()->id)->where("mark_as_dones.status",false)->get();
        return view('welcome', compact("all_note"));
    }
    return view('welcome');
})->name("home");


# Authentication

Route::get('/login', function () {
    return view('login');
});

Route::post('/login', [authentication::class, "login"])->name("login");

Route::get('/signup', function () {
    return view('signup');
});

Route::post('/signup', [authentication::class, "signup"])->name("signup");

# Note
Route::get("/note/{id}", function($id){
    $note = note::find($id);
    if($note){
        $pivot = pivot_for_note::where('note_id',$note->id)->first();
        if($pivot){
            if($pivot->shared_with == Auth::user()->id){
                return view("note", compact("note"));
            }
        } else {
            return view("note")->with("error","You are not authorized to view this note!");
        }
    } else{
        return view("note")->with("error","No note found!");
    }
})->name("note");

Route::post("/create/note", [NoteController::class, "create_note"])->name("create.note");
Route::post("/share/note/{id}", [pivot_for_note_controller::class, "share_note"])->name("share.note");
Route::post("/edit/note/{id}", [NoteController::class, "edit_note"])->name("edit.note");
Route::post("/delete/note/{id}", [NoteController::class, "delete_note"])->name("delete.note");

# Reply
Route::post("/reply/note/{id}", [reply_note::class, "reply_note"])->name("reply.note");

# Mark as done
Route::post("/mark/note/{id}", [MarkAsDoneController::class, "mark_as_done"])->name("mark.done");
Route::post("/undo/note/{id}", [MarkAsDoneController::class, "undo_mark_as_done"])->name("undo.done");


# Organization
Route::get("/organization/{id}", function($id){
    $organization = Organization::find($id);
    if($organization){
        if($organization->user_id == Auth::user()->id){
            $notes = Note::where('organizationID',$organization->id)->get();
            return view("organization", compact("organization","notes"));
        }
    }
    return view("organization")->with("error","You are not authorized to view this organization!");
})->name("organization");

Route::get("/create-organization", function(){
    return view("create-organization");
})->name("create-organization");

Route::post("/create-organization", [OrganizationController::class, "create_organization"])->name("create-organization");
Route::post("/edit/organization/{id}", [OrganizationController::class, "edit_organization"])->name("edit.organization");
Route::post("/delete/organization/{id}", [OrganizationController::class, "delete_organization"])->name("delete.organization");
Route::post("/share/organization/{id}", [OrganizationController::class, "share_organization"])->name("share.organization");
Route::post("/leave/organization/{id}", [OrganizationController::class, "leave_organization"])->name("leave.organization");

# Organization_admin
Route::get("/organization/dashboard/{id}", function($id){
    $organization = Organization::find($id);
    if($organization){
        if($organization->host_id != Auth::user()->id){
            return redirect()->route('organization', $id)->with("error","You are not authorized to view this organization!");
        }
        $current_members = organizations_member::where('organizationID',$organization->id)->where('status',true)->count();
        $pending_member = organizations_member::where('organizationID',$organization->id)->where('status',false)->count();
        $all_note = Note::where('organizationID',$organization->id)->count();
        $undone_note = Note::where('organizationID',$organization->id)->where('mark_as_done.status',false)->count();
        $done_note = Note::where('organizationID',$organization->id)->where('mark_as_done.status',true)->count();
        return view("organization.dashboard", compact("organization","current_members","pending_member","all_note","undone_note","done_note"));
    }
    return view("organization")->with("error","You are not authorized to view this organization!");
})->name("organization.dashboard");

Route::get("/organization/dashboard/{id}/current/member", function($id){
    $organization = Organization::find($id);
    if($organization){
        if($organization->host_id != Auth::user()->id){
            return redirect()->route('organization', $id)->with("error","You are not authorized to view this organization!");
        }
        $current_members = organizations_member::join('users', 'organizations_member.userID', 'users.id')->where('organizationID',$organization->id)->where('status',true)->get();
        return view("organization.current_member", compact("organization","current_members"));
    }
    return view("organization")->with("error","You are not authorized to view this organization!");
})->name("organization.current_member");

Route::get("/organization/dashboard/{id}/pending/member", function($id){
    $organization = Organization::find($id);
    if($organization){
        if($organization->host_id != Auth::user()->id){
            return redirect()->route('organization', $id)->with("error","You are not authorized to view this organization!");
        }
        $pending_members = organizations_member::join('users', 'organizations_member.userID', 'users.id')->where('organizationID',$organization->id)->where('status',false)->get();
        return view("organization.pending_member", compact("organization","pending_members"));
    }
    return view("organization")->with("error","You are not authorized to view this organization!");
})->name("organization.pending_member");
