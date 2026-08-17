<?php

use App\Http\Controllers\User2userTransactionController;
use App\Models\MarkAsDone;
use App\Models\Note;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\PasswordChangeRequest;
use App\Models\PivotForNote;
use App\Models\User;
use App\Models\User2userTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------------

it('registers a user with hashed password and redirects home', function () {
    $response = $this->post('/signup', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertRedirect(route('home'));
    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
    expect(Hash::needsRehash(User::where('email', 'new@example.com')->first()->password))->toBeFalse();
});

it('rejects a duplicate email during signup', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $this->post('/signup', [
        'name' => 'Dup',
        'email' => 'dup@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('email');
});

it('rejects signup when passwords do not match', function () {
    $this->post('/signup', [
        'name' => 'New User',
        'email' => 'mismatch@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors('password');
});

it('logs in with valid credentials and logs out', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-password')]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'secret-password',
        'remember' => '1',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('rejects login with wrong credentials', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-password')]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertRedirect(route('login'));

    $this->assertGuest();
});

// ---------------------------------------------------------------------------
// Notes
// ---------------------------------------------------------------------------

it('lets the creator view their own note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('note', $note->id))
        ->assertOk()
        ->assertSee($note->title);
});

it('forbids a user who is not the creator nor shared from viewing a note', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);

    $this->actingAs($intruder)
        ->get(route('note', $note->id))
        ->assertForbidden();
});

it('lets a shared recipient view the note', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    PivotForNote::create(['note_id' => $note->id, 'shared_with' => $recipient->id]);

    $this->actingAs($recipient)
        ->get(route('note', $note->id))
        ->assertOk();
});

it('creates a note and the mark-as-done record', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('create.note'), [
        'title' => 'My note',
        'description' => 'Content',
    ]);

    $note = Note::where('creater_id', $user->id)->first();
    expect($note)->not->toBeNull();
    expect(MarkAsDone::where('noteID', $note->id)->where('userID', $user->id)->exists())->toBeTrue();
    $response->assertRedirect(route('note', $note->id));
});

it('only the creator can delete a note', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);

    $this->actingAs($other)
        ->delete(route('delete.note', $note->id))
        ->assertRedirect(route('note', $note->id));

    expect(Note::find($note->id))->not->toBeNull();

    $this->actingAs($owner)
        ->delete(route('delete.note', $note->id))
        ->assertRedirect(route('home'));

    expect(Note::find($note->id))->toBeNull();
});

// Chia sẻ note đã đổi mô hình: không còn "chủ note gửi email cho danh sách người
// nhận" mà là "người nhận tự bấm vào link chia sẻ" (GET share.note).

it('does not let the owner self-share a note through its own share link', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);

    // Chủ note bấm link của chính mình: không được tạo bản ghi chia sẻ nào,
    // và người dùng được đưa về home kèm thông báo thay vì lỗi 500.
    $this->actingAs($owner)
        ->get(route('share.note', $note->id))
        ->assertRedirect(route('home'));

    expect(PivotForNote::count())->toBe(0);
});

it('lets a logged-in visitor join a note through its share link', function () {
    $owner = User::factory()->create();
    $visitor = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);

    $this->actingAs($visitor)
        ->get(route('share.note', $note->id))
        ->assertRedirect(route('note', $note->id));

    expect(
        PivotForNote::where('note_id', $note->id)->where('shared_with', $visitor->id)->exists()
    )->toBeTrue();

    // Có quyền rồi thì phải mở được note.
    $this->actingAs($visitor)->get(route('note', $note->id))->assertOk();
});

// ---------------------------------------------------------------------------
// Organizations
// ---------------------------------------------------------------------------

it('creates an organization and adds the host as an active member', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->post(route('organizations.store'), [
            'name' => 'Team Alpha',
            'description' => 'A team',
        ])->assertRedirect();

    $org = Organization::where('name', 'Team Alpha')->first();
    expect($org)->not->toBeNull();
    expect(OrganizationsMember::where('organizationID', $org->id)->where('userID', $host->id)->where('status', true)->exists())->toBeTrue();
});

it('forbids creating a note in an organization when not a member', function () {
    $host = User::factory()->create();
    $outsider = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);

    $this->actingAs($outsider)
        ->post(route('create.note.organization', $org->id), [
            'title' => 'Nope',
            'description' => 'No',
        ])->assertRedirect(route('organization', $org->id));

    expect(Note::where('organizationID', $org->id)->count())->toBe(0);
});

it('lets a member create a note in the organization', function () {
    $host = User::factory()->create();
    $member = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $member->id, 'status' => true]);

    $this->actingAs($member)
        ->post(route('create.note.organization', $org->id), [
            'title' => 'Org note',
            'description' => 'Content',
        ])->assertRedirect();

    expect(Note::where('organizationID', $org->id)->count())->toBe(1);
});

it('declining a member invitation removes the pending record', function () {
    $host = User::factory()->create();
    $member = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    $pending = OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $member->id, 'status' => false]);

    $this->actingAs($member)
        ->post(route('member.decline', $pending->id))
        ->assertRedirect(route('home'));

    expect(OrganizationsMember::find($pending->id))->toBeNull();
});

it('only the host can delete an organization', function () {
    $host = User::factory()->create();
    $member = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $host->id, 'status' => true]);
    OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $member->id, 'status' => true]);

    $this->actingAs($member)
        ->delete(route('delete.organization', $org->id))
        ->assertRedirect(route('home'));

    expect(Organization::find($org->id))->not->toBeNull();

    $this->actingAs($host)
        ->delete(route('delete.organization', $org->id))
        ->assertRedirect(route('organizations.index'));

    expect(Organization::find($org->id))->toBeNull();
    expect(OrganizationsMember::where('organizationID', $org->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Transactions
// ---------------------------------------------------------------------------

it('executes a user2user transaction with a valid OTP and updates balances', function () {
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

    $response = app(User2userTransactionController::class)->user2user_transaction_verify(new Request(['passkey' => '123456']), $transaction->id);

    expect($response->isRedirect())->toBeTrue();
    expect($transaction->fresh()->status)->toBe('finished');
    expect((float) $sender->fresh()->balance)->toBe(900.0);
    expect((float) $recipient->fresh()->balance)->toBe(100.0);
});

it('locks the transaction after too many failed OTP attempts', function () {
    $sender = User::factory()->create(['balance' => 1000, 'password' => Hash::make('secret')]);
    $recipient = User::factory()->create(['balance' => 0]);

    $transaction = new User2userTransaction;
    $transaction->from = $sender->id;
    $transaction->to = $recipient->id;
    $transaction->amount = 100;
    $transaction->status = 'pending';
    $transaction->otp = Hash::make('123456');
    $transaction->expires_at = now()->addMinutes(10)->toDateTimeString();
    $transaction->attempts = 5;
    $transaction->save();

    $this->actingAs($sender);

    $response = app(User2userTransactionController::class)->user2user_transaction_verify(new Request(['passkey' => 'wrong']), $transaction->id);

    expect($transaction->fresh()->status)->toBe('failed');
    expect($response->isRedirect())->toBeTrue();
});

it('does not deduct balance when the sender has insufficient funds', function () {
    $sender = User::factory()->create(['balance' => 10, 'password' => Hash::make('secret')]);
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

    $response = app(User2userTransactionController::class)->user2user_transaction_verify(new Request(['passkey' => '123456']), $transaction->id);

    expect($transaction->fresh()->status)->toBe('pending');
    expect((float) $sender->fresh()->balance)->toBe(10.0);
    expect((float) $recipient->fresh()->balance)->toBe(0.0);
});

// ---------------------------------------------------------------------------
// Password reset
// ---------------------------------------------------------------------------

it('keeps the reset request alive on a wrong OTP and locks after max attempts', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'reset@example.com']);

    $this->post(route('password.forgot.post'), ['email' => 'reset@example.com']);

    $request = PasswordChangeRequest::where('user_id', $user->id)->first();
    expect($request)->not->toBeNull();
    expect($request->attempts)->toBe(0);

    // Wrong OTP does not destroy the request (retry-friendly).
    $this->post(route('password.reset', $request->id), [
        'passkey' => '000000',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('password.reset.view', $request->id));

    expect($request->fresh()->used)->toBeFalse();
    expect($request->fresh()->attempts)->toBe(1);

    // Exhaust remaining attempts -> request is locked.
    $request->update(['attempts' => 5]);
    $this->post(route('password.reset', $request->id), [
        'passkey' => '000000',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('password.forgot'));

    expect(PasswordChangeRequest::find($request->id))->toBeNull();
});

it('changes the password with a valid OTP', function () {
    $user = User::factory()->create(['email' => 'reset2@example.com', 'password' => Hash::make('old-password')]);

    $request = PasswordChangeRequest::create([
        'user_id' => $user->id,
        'token' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
        'used' => false,
        'attempts' => 0,
    ]);

    $this->post(route('password.reset', $request->id), [
        'passkey' => '123456',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('home'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    expect($request->fresh()->used)->toBeTrue();
});
