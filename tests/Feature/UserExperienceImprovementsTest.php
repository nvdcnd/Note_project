<?php

use App\Http\Controllers\OrganizationsMemberController;
use App\Http\Controllers\PivotForNoteController;
use App\Http\Controllers\User2userTransactionController;
use App\Mail\Mail40account;
use App\Mail\UserEmail;
use App\Models\Note;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\PivotForNote;
use App\Models\User;
use App\Models\User2userTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('deduplicates shared recipients and keeps the flow resilient for partial success', function () {
    Mail::fake();

    $creator = User::factory()->create();
    $recipient = User::factory()->create(['email' => 'recipient@example.com']);
    $note = Note::create([
        'title' => 'Shared note',
        'description' => 'Test note',
        'creater_id' => $creator->id,
    ]);

    $this->actingAs($creator);

    $request = new Request(['shared_with' => [$recipient->email, $recipient->email, 'missing@example.com']]);

    $response = app(PivotForNoteController::class)->share_note($request, $note->id);

    expect($response->isRedirect())->toBeTrue();
    expect(PivotForNote::where('note_id', $note->id)->where('shared_with', $recipient->id)->count())->toBe(1);
    Mail::assertQueued(UserEmail::class, 1);
    Mail::assertQueued(Mail40account::class, 1);
});

it('adds valid members and skips missing addresses instead of failing the whole invitation batch', function () {
    Mail::fake();

    $host = User::factory()->create();
    $organization = new Organization;
    $organization->name = 'Scalable Org';
    $organization->hostID = $host->id;
    $organization->save();

    $member = User::factory()->create(['email' => 'member@example.com']);

    $this->actingAs($host);

    $request = new Request(['user_list' => ['member@example.com', 'missing@example.com']]);
    $response = app(OrganizationsMemberController::class)->add_member($request, $organization->id);

    expect($response->isRedirect())->toBeTrue();
    expect(session('success'))->toBe('Member added successfully');
    expect(OrganizationsMember::where('organizationID', $organization->id)->count())->toBe(1);
    expect(OrganizationsMember::where('organizationID', $organization->id)->where('userID', $member->id)->exists())->toBeTrue();
});

it('keeps a pending transaction alive when the passkey is wrong so the user can retry', function () {
    $sender = User::factory()->create(['balance' => 1000, 'password' => Hash::make('secret')]);
    $recipient = User::factory()->create(['balance' => 0]);

    $transaction = new User2userTransaction;
    $transaction->from = $sender->id;
    $transaction->to = $recipient->id;
    $transaction->amount = 100;
    $transaction->status = 'pending';
    $transaction->otp = Hash::make('123456');
    $transaction->expires_at = now()->addMinutes(10)->toDateTimeString();
    $transaction->attempts = 0;
    $transaction->save();

    $this->actingAs($sender);

    $response = app(User2userTransactionController::class)->user2user_transaction_verify(new Request(['passkey' => 'wrong']), $transaction->id);

    expect($response->isRedirect())->toBeTrue();
    expect($transaction->fresh()->status)->toBe('pending');
    expect($transaction->fresh()->attempts)->toBe(1);
    expect($sender->fresh()->balance)->toBe(1000.0);
    expect($recipient->fresh()->balance)->toBe(0.0);
});
