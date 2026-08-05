<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\MarkAsDoneController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\Organization2userTransactionController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\OrganizationsMemberController;
use App\Http\Controllers\PivotForNoteController;
use App\Http\Controllers\ReplyNoteController;
use App\Http\Controllers\ThemeRequestController;
use App\Http\Controllers\User2organizationTransactionController;
use App\Http\Controllers\User2userTransactionController;
use App\Models\Note;
use App\Models\Organization2userTransaction;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\PivotForNote;
use App\Models\ThemeRequest;
use App\Models\User2organizationTransaction;
use App\Models\User2userTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate;

Route::get('/', function () {
    if (Auth::check()) {
        $all_note = Note::where('creater_id', Auth::user()->id)->take(5)->get();

         return view('welcome', compact('all_note'));
    }

    return view('welcome');
})->name('home');

// Authentication

Route::get('/login', function () {
    return view('login');
});

Route::post('/login', [AuthenticationController::class, 'login'])->name('login');

Route::get('/signup', function () {
    return view('signup');
});

Route::post('/signup', [AuthenticationController::class, 'signup'])->name('signup');

Route::Middleware(['auth']->group(function(){

    // Note
    Route::get('/note/{id}', function ($id) {
        $note = Note::find($id);
        if ($note) {
            $pivot = PivotForNote::where('note_id', $note->id)->first();
            if ($pivot) {
                if ($pivot->shared_with == Auth::user()->id) {
                    return view('note', compact('note'));
                }
            } else {
                return view('note')->with('error', 'You are not authorized to view this note!');
            }
        } else {
            return view('note')->with('error', 'No note found!');
        }
    })->name('note');


    // Reply
    Route::post('/reply/note/{id}', [ReplyNoteController::class, 'reply_note'])->name('reply.note');

    // Mark as done
    Route::post('/mark/note/{id}', [MarkAsDoneController::class, 'mark_as_done'])->name('mark.done');
    Route::post('/undo/note/{id}', [MarkAsDoneController::class, 'undo_mark_as_done'])->name('undo.done');

    // Organization
    Route::get('/organization/{id}', function ($id) {
        $organization = Organization::find($id);
        if ($organization) {
            if ($organization->hostID == Auth::user()->id) {
                $notes = Note::where('organizationID', $organization->id)->take(5)->get();

                return view('organization', compact('organization', 'notes'));
            }
        }

        return view('organization')->with('error', 'You are not authorized to view this organization!');
    })->name('organization');

    Route::get('/create-organization', function () {
        return view('create-organization');
    })->name('create-organization');

    Route::post('/create-organization', [OrganizationsController::class, 'create_organization'])->name('create-organization');
    Route::post('/edit/organization/{id}', [OrganizationsController::class, 'edit_organization'])->name('edit.organization');
    Route::post('/delete/organization/{id}', [OrganizationsController::class, 'delete_organization'])->name('delete.organization');
    Route::post('/share/organization/{id}', [OrganizationsMemberController::class, 'add_member'])->name('share.organization');
    Route::post('/leave/organization/{id}', [OrganizationsMemberController::class, 'member_leave'])->name('leave.organization');

    // Organization_admin
    Route::get('/organization/dashboard/{id}', function ($id) {
        $organization = Organization::find($id);
        if ($organization) {
            if ($organization->hostID != Auth::user()->id) {
                return redirect()->route('organization', $id)->with('error', 'You are not authorized to view this organization!');
            }
            $current_members = OrganizationsMember::where('organizationID', $organization->id)->where('status', true)->count();
            $pending_member = OrganizationsMember::where('organizationID', $organization->id)->where('status', false)->count();
            $all_note = Note::where('organizationID', $organization->id)->count();
            $undone_note = Note::where('organizationID', $organization->id)->count();
            $done_note = 0;

            return view('organization.dashboard', compact('organization', 'current_members', 'pending_member', 'all_note', 'undone_note', 'done_note'));
        }

        return view('organization')->with('error', 'You are not authorized to view this organization!');
    })->name('organization.dashboard');

    Route::get('/organization/dashboard/{id}/current/member', function ($id) {
        $organization = Organization::find($id);
        if ($organization) {
            if ($organization->hostID != Auth::user()->id) {
                return redirect()->route('organization', $id)->with('error', 'You are not authorized to view this organization!');
            }
            $current_members = OrganizationsMember::join('users', 'OrganizationsMember.userID', 'users.id')->where('organizationID', $organization->id)->where('status', true)->get();

            return view('organization.current_member', compact('organization', 'current_members'));
        }

        return view('organization')->with('error', 'You are not authorized to view this organization!');
    })->name('organization.current_member');

    Route::get('/organization/dashboard/{id}/pending/member', function ($id) {
        $organization = Organization::find($id);
        if ($organization) {
            if ($organization->hostID != Auth::user()->id) {
                return redirect()->route('organization', $id)->with('error', 'You are not authorized to view this organization!');
            }
            $pending_members = OrganizationsMember::join('users', 'OrganizationsMember.userID', 'users.id')->where('organizationID', $organization->id)->where('status', false)->get();

            return view('organization.pending_member', compact('organization', 'pending_members'));
        }

        return view('organization')->with('error', 'You are not authorized to view this organization!');
    })->name('organization.pending_member');

    // User2user transaction
    Route::get('user2user/create/transaction', function () {
        return view('User2userTransaction');
    })->name('user2user_transaction_view');

    Route::get('user2user/verify/transaction/{id}', function ($id) {
        $User2userTransaction = User2userTransaction::where('id', $id)->first();
        if ($User2userTransaction) {
            if (Auth::user()->id == $User2userTransaction->from || Auth::user()->id == $User2userTransaction->to) {
                return view('user2user_transaction_verify', compact('User2userTransaction'));
            } else {
                return view('User2userTransaction')->with('error', 'You are not authorized to verify this transaction!');
            }
        } else {
            return view('User2userTransaction')->with('error', 'Invalid transaction ID!');
        }
    })->name('user2user_transaction_verify_view');

    Route::get('user2user/{id}/transaction/history', function () {
        $user2user_all_transactions = User2userTransaction::where('from', Auth::user()->id)->orWhere('to', Auth::user()->id)->get();
        $user2user_from_transactions = User2userTransaction::where('from', Auth::user()->id)->get();
        $user2user_to_transactions = User2userTransaction::where('to', Auth::user()->id)->get();

        return view('user2user_transaction_history', compact('user2user_all_transactions', 'user2user_from_transactions', 'user2user_to_transactions'));
    })->name('user2user_transaction_history_view');

    Route::post('user2user/create/transaction', [User2userTransactionController::class, 'user2user_transaction_create'])->name('user2user_transaction_create');
    Route::post('user2user/verify/transaction', [User2userTransactionController::class, 'user2user_transaction_verify'])->name('user2user_transaction_verify');
    Route::post('user2user/cancel/transaction', [User2userTransactionController::class, 'user2user_transaction_cancel'])->name('user2user_transaction_cancel');

    // User2organization transaction
    Route::get('user2organization/create/transaction', function () {
        return view('User2organizationTransaction');
    })->name('user2organization_transaction_view');

    Route::get('user2organization/verify/transaction/{id}', function ($id) {
        $User2organizationTransaction = User2organizationTransaction::where('id', $id)->first();
        if ($User2organizationTransaction) {
            if (Auth::user()->id == $User2organizationTransaction->from || Auth::user()->id == $User2organizationTransaction->to) {
                return view('user2organization_transaction_verify', compact('User2organizationTransaction'));
            } else {
                return view('User2organizationTransaction')->with('error', 'You are not authorized to verify this transaction!');
            }
        } else {
            return view('User2organizationTransaction')->with('error', 'Invalid transaction ID!');
        }
    })->name('user2organization_transaction_verify_view');

    Route::get('user2organization/{id}/transaction/history', function () {
        $user2organization_all_transactions = User2organizationTransaction::where('from', Auth::user()->id)->orWhere('to', Auth::user()->id)->get();
        $user2organization_from_transactions = User2organizationTransaction::where('from', Auth::user()->id)->get();
        $user2organization_to_transactions = User2organizationTransaction::where('to', Auth::user()->id)->get();

        return view('user2organization_transaction_history', compact('user2organization_all_transactions', 'user2organization_from_transactions', 'user2organization_to_transactions'));
    })->name('user2organization_transaction_history_view');

    Route::post('user2organization/create/transaction', [User2organizationTransactionController::class, 'user2organization_transaction_create'])->name('user2organization_transaction_create');
    Route::post('user2organization/verify/transaction', [User2organizationTransactionController::class, 'user2organization_transaction_verify'])->name('user2organization_transaction_verify');
    Route::post('user2organization/cancel/transaction', [User2organizationTransactionController::class, 'user2organization_transaction_cancel'])->name('user2organization_transaction_cancel');

    // Organization2user transaction
    Route::get('organization2user/{id}/create/transaction', function () {
        return view('Organization2userTransaction');
    })->name('organization2user_transaction_view');

    Route::get('organization2user/verify/transaction/{id}', function ($id) {
        $Organization2userTransaction = Organization2userTransaction::where('id', $id)->first();
        if ($Organization2userTransaction) {
            if (Auth::user()->id == $Organization2userTransaction->from || Auth::user()->id == $Organization2userTransaction->to) {
                return view('organization2user_transaction_verify', compact('Organization2userTransaction'));
            } else {
                return view('Organization2userTransaction')->with('error', 'You are not authorized to verify this transaction!');
            }
        } else {
            return view('Organization2userTransaction')->with('error', 'Invalid transaction ID!');
        }
    })->name('organization2user_transaction_verify_view');

    Route::get('organization2user/{id}/transaction/history', function ($id) {
        $organization2user_all_transactions = Organization2userTransaction::where('from', Auth::user()->id)->orWhere('to', Auth::user()->id)->get();
        $organization2user_from_transactions = Organization2userTransaction::where('from', Auth::user()->id)->get();
        $organization2user_to_transactions = Organization2userTransaction::where('to', Auth::user()->id)->get();

        return view('organization2user_transaction_history', compact('organization2user_all_transactions', 'organization2user_from_transactions', 'organization2user_to_transactions'));
    })->name('organization2user_transaction_history_view');

    Route::post('organization2user/{id}/create/transaction', [Organization2userTransactionController::class, 'organization2user_transaction_create'])->name('organization2user_transaction_create');
    Route::post('organization2user/{id}/verify/transaction', [Organization2userTransactionController::class, 'organization2user_transaction_verify'])->name('organization2user_transaction_verify');
    Route::post('organization2user/{id}/cancel/transaction', [Organization2userTransactionController::class, 'organization2user_transaction_cancel'])->name('organization2user_transaction_cancel');

    // Theme create request
    Route::get('create/theme/request', function () {
        return view('create_theme_request');
    })->name('create_theme_request_view');

    Route::post('create/theme/request', [ThemeRequestController::class, 'create_theme_request'])->name('create_theme_request');

    Route::get('create/theme/request/success/{id}', function ($id) {
        $theme = ThemeRequest::where('id', $id)->first();
        if ($theme) {
            return view('create_theme_request_success', compact('theme'));
        } else {
            return view('create_theme_request')->with('error','Invalid theme ID!');
        }
    })->name('create_theme_request_success_view');

}))

