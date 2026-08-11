<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\MarkAsDoneController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\Organization2userTransactionController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\OrganizationsMemberController;
use App\Http\Controllers\PasswordChangeRequestController;
use App\Http\Controllers\PivotChangeHostOrganizationController;
use App\Http\Controllers\PivotForNoteController;
use App\Http\Controllers\ReplyNoteController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Theme4orgController;
use App\Http\Controllers\Theme4orgWalletController;
use App\Http\Controllers\Theme4userController;
use App\Http\Controllers\Theme4userWalletController;
use App\Http\Controllers\ThemeRequestController;
use App\Http\Controllers\User2organizationTransactionController;
use App\Http\Controllers\User2userTransactionController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------------

Route::get('/', [NoteController::class, 'home'])->name('home');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::post('/login', [AuthenticationController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.post');

Route::get('/signup', function () {
    return view('signup');
})->name('signup');

Route::post('/signup', [AuthenticationController::class, 'signup'])
    ->middleware('throttle:5,1')
    ->name('signup.post');

// Password reset (public)
Route::get('/forgot-password', [PasswordChangeRequestController::class, 'forgot_password_view'])->name('password.forgot');
Route::post('/forgot-password', [PasswordChangeRequestController::class, 'forgot_password'])
    ->middleware('throttle:5,1')
    ->name('password.forgot.post');
Route::get('/reset-password/{id}', [PasswordChangeRequestController::class, 'change_password_view'])->name('password.reset.view');
Route::post('/reset-password/{id}', [PasswordChangeRequestController::class, 'change_password'])
    ->middleware('throttle:5,1')
    ->name('password.reset');

// ---------------------------------------------------------------------------
// Authenticated
// ---------------------------------------------------------------------------

Route::middleware(['auth'])->group(function () {

    // Auth
    Route::post('/logout', [AuthenticationController::class, 'logout'])->name('logout');

    // Notes
    Route::get('/note/{id}', [NoteController::class, 'show'])->name('note');
    Route::post('/create/note', [NoteController::class, 'create_note'])->name('create.note');
    Route::post('/create/note/organization/{id}', [NoteController::class, 'create_note_in_organization'])->name('create.note.organization');
    Route::post('/edit/note/{id}', [NoteController::class, 'edit_note'])->name('edit.note');
    Route::delete('/delete/note/{id}', [NoteController::class, 'delete_note'])->name('delete.note');
    Route::post('/share/note/{id}', [PivotForNoteController::class, 'share_note'])->name('share.note');
    Route::delete('/unshare/note/{id}', [PivotForNoteController::class, 'undo_shared_note'])->name('unshare.note');

    // Reply
    Route::post('/reply/note/{id}', [ReplyNoteController::class, 'reply_note'])->name('reply.note');
    Route::delete('/reply/{id}', [ReplyNoteController::class, 'delete_reply'])->name('delete.reply');

    // Mark as done
    Route::post('/mark/note/{id}', [MarkAsDoneController::class, 'mark_as_done'])->name('mark.done');
    Route::post('/undo/note/{id}', [MarkAsDoneController::class, 'undo_mark_as_done'])->name('undo.done');

    // Organizations
    Route::get('/organizations', [OrganizationsController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [OrganizationsController::class, 'create'])->name('organizations.create');
    Route::post('/organizations/create', [OrganizationsController::class, 'store'])->name('organizations.store');
    Route::get('/organization/{id}', [OrganizationsController::class, 'show'])->name('organization');
    Route::get('/organization/{id}/dashboard', [OrganizationsController::class, 'dashboard'])->name('organization.dashboard');
    Route::get('/organization/{id}/members', [OrganizationsController::class, 'members'])->name('organization.members');
    Route::get('/organization/{id}/settings', [OrganizationsController::class, 'settings'])->name('organization.settings');
    Route::get('/organization/{id}/balance', [OrganizationsController::class, 'balance'])->name('organization.balance');
    Route::post('/edit/organization/{id}', [OrganizationsController::class, 'edit_organization'])->name('edit.organization');
    Route::delete('/delete/organization/{id}', [OrganizationsController::class, 'delete_organization'])->name('delete.organization');
    Route::post('/share/organization/{id}', [OrganizationsMemberController::class, 'add_member'])->name('share.organization');
    Route::post('/leave/organization/{id}', [OrganizationsMemberController::class, 'member_leave'])->name('leave.organization');

    // Organization members
    Route::post('/member/accept/{id}', [OrganizationsMemberController::class, 'accept_member'])->name('member.accept');
    Route::post('/member/decline/{id}', [OrganizationsMemberController::class, 'decline_member'])->name('member.decline');
    Route::delete('/organization/{organizationid}/remove-member/{userID}', [OrganizationsMemberController::class, 'remove_member'])->name('member.remove');

    // Host organization management
    Route::post('/organization/{id}/change-host', [PivotChangeHostOrganizationController::class, 'change_host_for_organization'])->name('organization.change_host');
    Route::post('/organization/change-host/{id}/confirm', [PivotChangeHostOrganizationController::class, 'change_host_real'])->name('organization.change_host_real');
    Route::delete('/organization/change-host/{id}', [PivotChangeHostOrganizationController::class, 'delete_old_request'])->name('organization.delete_host_request');
    Route::post('/organization/change-host/{id}/accept', [PivotChangeHostOrganizationController::class, 'new_host_accept'])->name('organization.accept_host');
    Route::post('/organization/change-host/{id}/decline', [PivotChangeHostOrganizationController::class, 'new_host_decline'])->name('organization.decline_host');

    // User balance + settings
    Route::get('/balance', [BalanceController::class, 'index'])->name('balance');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/avatar', [SettingsController::class, 'updateAvatar'])->name('settings.avatar');
    Route::post('/settings/password', [SettingsController::class, 'changePassword'])->name('settings.password');

    // User2user transaction
    Route::get('/user2user/create/transaction', [User2userTransactionController::class, 'create_view'])->name('user2user_transaction_view');
    Route::get('/user2user/verify/transaction/{id}', [User2userTransactionController::class, 'verify_view'])->name('user2user_transaction_verify_view');
    Route::get('/user2user/{id}/transaction/history', [User2userTransactionController::class, 'history_view'])->name('user2user_transaction_history_view');
    Route::post('/user2user/create/transaction', [User2userTransactionController::class, 'user2user_transaction_create'])->name('user2user_transaction_create');
    Route::post('/user2user/verify/transaction/{id}', [User2userTransactionController::class, 'user2user_transaction_verify'])
        ->middleware('throttle:5,1')
        ->name('user2user_transaction_verify');
    Route::post('/user2user/cancel/transaction/{id}', [User2userTransactionController::class, 'user2user_transaction_cancel'])->name('user2user_transaction_cancel');

    // User2organization transaction
    Route::get('/user2organization/create/transaction', [User2organizationTransactionController::class, 'create_view'])->name('user2organization_transaction_view');
    Route::get('/user2organization/verify/transaction/{id}', [User2organizationTransactionController::class, 'verify_view'])->name('user2organization_transaction_verify_view');
    Route::get('/user2organization/{id}/transaction/history', [User2organizationTransactionController::class, 'history_view'])->name('user2organization_transaction_history_view');
    Route::post('/user2organization/create/transaction', [User2organizationTransactionController::class, 'user2organization_transaction_create'])->name('user2organization_transaction_create');
    Route::post('/user2organization/verify/transaction/{id}', [User2organizationTransactionController::class, 'user2organization_transaction_verify'])
        ->middleware('throttle:5,1')
        ->name('user2organization_transaction_verify');
    Route::post('/user2organization/cancel/transaction/{id}', [User2organizationTransactionController::class, 'user2organization_transaction_cancel'])->name('user2organization_transaction_cancel');

    // Organization2user transaction
    Route::get('/organization2user/{id}/create/transaction', [Organization2userTransactionController::class, 'create_view'])->name('organization2user_transaction_view');
    Route::get('/organization2user/verify/transaction/{id}', [Organization2userTransactionController::class, 'verify_view'])->name('organization2user_transaction_verify_view');
    Route::get('/organization2user/{id}/transaction/history', [Organization2userTransactionController::class, 'history_view'])->name('organization2user_transaction_history_view');
    Route::post('/organization2user/{id}/create/transaction', [Organization2userTransactionController::class, 'organization2user_transaction_create'])->name('organization2user_transaction_create');
    Route::post('/organization2user/{id}/verify/transaction', [Organization2userTransactionController::class, 'organization2user_transaction_verify'])
        ->middleware('throttle:5,1')
        ->name('organization2user_transaction_verify');
    Route::post('/organization2user/{id}/cancel/transaction', [Organization2userTransactionController::class, 'organization2user_transaction_cancel'])->name('organization2user_transaction_cancel');

    // Theme store
    Route::get('/themes', [Theme4userController::class, 'index'])->name('themes.index');
    Route::get('/themes/{id}', [Theme4userController::class, 'show'])->name('themes.show');
    Route::get('/themes/org', [Theme4orgController::class, 'index'])->name('themes.org.index');
    Route::get('/themes/org/{id}', [Theme4orgController::class, 'show'])->name('themes.org.show');

    // Theme create request
    Route::get('/create/theme/request', fn () => view('themes.request'))->name('create_theme_request_view');
    Route::post('/create/theme/request', [ThemeRequestController::class, 'create_theme_request'])->name('create_theme_request');
    Route::get('/create/theme/request/success/{id}', function ($id) {
        $theme = \App\Models\ThemeRequest::query()->find($id);

        return view('themes.request_success', compact('theme'));
    })->name('create_theme_request_success_view');

    // Theme buy (user)
    Route::post('/theme/user/buy/{themeID}', [Theme4userWalletController::class, 'user_buy_theme'])->name('theme.user.buy');
    Route::post('/theme/user/buy/verify/{id}', [Theme4userWalletController::class, 'user_buy_theme_verify_otp'])
        ->middleware('throttle:5,1')
        ->name('theme.user.buy.verify');

    // Theme buy (org)
    Route::post('/theme/org/buy/{id}', [Theme4orgWalletController::class, 'Organization_buy_theme'])->name('theme.org.buy');
    Route::post('/theme/org/buy/verify/{id}', [Theme4orgWalletController::class, 'Organization_buy_theme_verify_otp'])
        ->middleware('throttle:5,1')
        ->name('theme.org.buy.verify');

});
