<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\authentication;

Route::get('/', function () {
    if(Auth::check()){
        return redirect('home');
    }
    return view('welcome');
});


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

Route::post("/create/note", [pivot_for_note::class, "create_note"])->name("create.note");
Route::post("/share/note", [pivot_for_note::class, "share_note"])->name("share.note");
Route::post("/edit/note/{id}", [pivot_for_note::class, "edit_note"])->name("edit.note");
Route::post("/delete/note/{id}", [pivot_for_note::class, "delete_note"])->name("delete.note");
Route::post("/reply/note/{id}", [pivot_for_note::class, "reply_note"])->name("reply.note");