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
use App\Http\Controllers\User2userTransactionController;
use App\Http\Controllers\User2organizationTransactionController;
use App\Models\user2user_transaction;
use App\Models\organization2user;
use App\Http\Controllers\Organization2userTransactionController;
use App\Models\Theme_request;
use App\Http\Controllers\ThemeRequestController;



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


# User2user transaction
Route::get("user2user/create/transaction", function(){
    return view("user2user_transaction");
})->name("user2user_transaction_view");

Route::get("user2user/verify/transaction/{id}", function($id){
    $user2user_transaction = user2user_transaction::where('id', $id)->first();
    if($user2user_transaction){
        if(Auth::user()->id == $user2user_transaction->from || Auth::user()->id == $user2user_transaction->to){
            return view("user2user_transaction_verify", compact("user2user_transaction"));
        }else{
            return view("user2user_transaction")->with("error","You are not authorized to verify this transaction!");
        }
    }else{
        return view("user2user_transaction")->with("error","Invalid transaction ID!");
    }
})->name("user2user_transaction_verify_view");

Route::get("user2user/{id}/transaction/history", function(){
    $user2user_all_transactions = user2user_transaction::where('from', Auth::user()->id)->orWhere('to', Auth::user()->id)->get();
    $user2user_from_transactions = user2user_transaction::where('from', Auth::user()->id)->get();
    $user2user_to_transactions = user2user_transaction::where('to', Auth::user()->id)->get();
    $user2user_success_transaction = user2user_transaction::where('from', Auth::user()->id)->where('status',true)->orWhere('to', Auth::user()->id)->where('status',true)->get();
    $user2user_pending_transaction = user2user_transaction::where('from', Auth::user()->id)->where('status',false)->orWhere('to', Auth::user()->id)->where('status',false)->get();
    $user2user_failed_transaction = user2user_transaction::where('from', Auth::user()->id)->where('status',false)->orWhere('to', Auth::user()->id)->where('status',false)->get();
    return view("user2user_transaction_history", compact("user2user_all_transactions","user2user_from_transactions","user2user_to_transactions"));
})->name("user2user_transaction_history_view");

Route::post("user2user/create/transaction", [User2userTransactionController::class, "create_transaction"])->name("user2user_transaction_create");
Route::post("user2user/verify/transaction", [User2userTransactionController::class, "verify_transaction"])->name("user2user_transaction_verify");
Route::post("user2user/cancel/transaction", [User2userTransactionController::class, "cancel_transaction"])->name("user2user_transaction_cancel");

# User2organization transaction
Route::get("user2organization/create/transaction", function(){
    return view("user2organization_transaction");
})->name("user2organization_transaction_view");

Route::get("user2organization/verify/transaction/{id}", function($id){
    $user2organization_transaction = user2organization_transaction::where('id', $id)->first();
    if($user2organization_transaction){
        if(Auth::user()->id == $user2organization_transaction->from || Auth::user()->id == $user2organization_transaction->to){
            return view("user2organization_transaction_verify", compact("user2organization_transaction"));
        }else{
            return view("user2organization_transaction")->with("error","You are not authorized to verify this transaction!");
        }
    }else{
        return view("user2organization_transaction")->with("error","Invalid transaction ID!");
    }
})->name("user2organization_transaction_verify_view");

Route::get("user2organization/{id}/transaction/history", function(){
    $user2organization_all_transactions = user2organization_transaction::where('from', Auth::user()->id)->orWhere('to', Auth::user()->id)->get();
    $user2organization_from_transactions = user2organization_transaction::where('from', Auth::user()->id)->get();
    $user2organization_to_transactions = user2organization_transaction::where('to', Auth::user()->id)->get();
    $user2organization_success_transaction = user2organization_transaction::where('from', Auth::user()->id)->where('status',true)->orWhere('to', Auth::user()->id)->where('status',true)->get();
    $user2organization_pending_transaction = user2organization_transaction::where('from', Auth::user()->id)->where('status',false)->orWhere('to', Auth::user()->id)->where('status',false)->get();
    $user2organization_failed_transaction = user2organization_transaction::where('from', Auth::user()->id)->where('status',false)->orWhere('to', Auth::user()->id)->where('status',false)->get();
    return view("user2organization_transaction_history", compact("user2organization_all_transactions","user2organization_from_transactions","user2organization_to_transactions","user2organization_success_transaction","user2organization_pending_transaction","user2organization_failed_transaction"));
})->name("user2organization_transaction_history_view");

Route::post("user2organization/create/transaction", [User2organizationTransactionController::class, "create_transaction"])->name("user2organization_transaction_create");
Route::post("user2organization/verify/transaction", [User2organizationTransactionController::class, "verify_transaction"])->name("user2organization_transaction_verify");
Route::post("user2organization/cancel/transaction", [User2organizationTransactionController::class, "cancel_transaction"])->name("user2organization_transaction_cancel");

# Organization2user transaction
Route::get("organization2user/{id}/create/transaction", function(){
    return view("organization2user_transaction");
})->name("organization2user_transaction_view");

Route::get("organization2user/verify/transaction/{id}", function($id){
    $organization2user_transaction = organization2user_transaction::where('id', $id)->first();
    if($organization2user_transaction){
        if(Auth::user()->id == $organization2user_transaction->from || Auth::user()->id == $organization2user_transaction->to){
            return view("organization2user_transaction_verify", compact("organization2user_transaction"));
        }else{
            return view("organization2user_transaction")->with("error","You are not authorized to verify this transaction!");
        }
    }else{
        return view("organization2user_transaction")->with("error","Invalid transaction ID!");
    }
})->name("organization2user_transaction_verify_view");

Route::get("organization2user/{id}/transaction/history", function($id){
    $organization2user_all_transactions = organization2user_transaction::where('from', Auth::user()->id)->orWhere('to', Auth::user()->id)->get();
    $organization2user_from_transactions = organization2user_transaction::where('from', Auth::user()->id)->get();
    $organization2user_to_transactions = organization2user_transaction::where('to', Auth::user()->id)->get();
    $organization2user_success_transaction = organization2user_transaction::where('from', Auth::user()->id)->where('status',true)->orWhere('to', Auth::user()->id)->where('status',true)->get();
    $organization2user_pending_transaction = organization2user_transaction::where('from', Auth::user()->id)->where('status',false)->orWhere('to', Auth::user()->id)->where('status',false)->get();
    $organization2user_failed_transaction = organization2user_transaction::where('from', Auth::user()->id)->where('status',false)->orWhere('to', Auth::user()->id)->where('status',false)->get();
    return view("organization2user_transaction_history", compact("organization2user_all_transactions","organization2user_from_transactions","organization2user_to_transactions","organization2user_success_transaction","organization2user_pending_transaction","organization2user_failed_transaction"));
})->name("organization2user_transaction_history_view");

Route::post("organization2user/{id}/crete/transaction", [User2organizationTransactionController::class, "create_transaction"])->name("organization2user_transaction_create");
Route::post("organization2user/{id}/verify/transaction", [User2organizationTransactionController::class, "verify_transaction"])->name("organization2user_transaction_verify");
Route::post("organization2user/{id}/cancel/transaction", [User2organizationTransactionController::class, "cancel_transaction"])->name("organization2user_transaction_cancel");

# Theme create request
Route::get("create/theme/request", function(){
    return view("create_theme_request");
})->name("create_theme_request_view");

Route::post("create/theme/request", [ThemeController::class, "create_theme_request"])->name("create_theme_request");

Route::get("create/theme/request/success/{id}", function($id){
    $theme = Theme_request::where('id', $id)->first();
    if($theme){
        return view("create_theme_request_success", compact("theme"));
    }else{
        return view("create_theme_request")->with("error","Invalid theme ID!");
    }
})->name("create_theme_request_success_view");