<?php

use App\Mail\Mail40account;
use App\Mail\OrganizationInvitation;
use App\Models\Invitation;
use App\Models\Note;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\PivotForNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Tạo lời mời khi chia sẻ cho email chưa đăng ký
// ---------------------------------------------------------------------------

it('creates an invitation with a signup link when a note is shared with an unregistered email', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('share.note', $note->id), ['shared_with' => ['nguoimoi@example.com']])
        ->assertRedirect(route('note', $note->id));

    $invitation = Invitation::query()->where('email', 'nguoimoi@example.com')->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->invitable_type)->toBe(Note::class)
        ->and($invitation->invitable_id)->toBe($note->id)
        ->and($invitation->invited_by)->toBe($owner->id)
        ->and($invitation->isPending())->toBeTrue();

    Mail::assertQueued(Mail40account::class);
});

it('stores the invitation token hashed, never in plain text', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);

    $issued = Invitation::issueFor($note, 'ai-do@example.com', $owner->id);

    expect($issued['invitation']->token)->not->toBe($issued['token'])
        ->and($issued['invitation']->token)->toBe(hash('sha256', $issued['token']));
});

it('invites an unregistered email to the organization instead of silently skipping it', function () {
    Mail::fake();

    $host = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);

    $this->actingAs($host)
        ->post(route('share.organization', $org->id), ['user_list' => ['chuadangky@example.com']])
        ->assertRedirect(route('organization', $org->id));

    $invitation = Invitation::query()->where('email', 'chuadangky@example.com')->first();

    expect($invitation)->not->toBeNull()
        ->and($invitation->invitable_type)->toBe(Organization::class)
        ->and($invitation->invitable_id)->toBe($org->id);

    Mail::assertQueued(OrganizationInvitation::class);
});

// ---------------------------------------------------------------------------
// Nhận lời mời
// ---------------------------------------------------------------------------

it('shows the invitation signup form for a valid token', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id, 'title' => 'Ghi chú được mời']);
    $issued = Invitation::issueFor($note, 'moi@example.com', $owner->id);

    $this->get(route('invitation.show', $issued['token']))
        ->assertOk()
        ->assertSee('moi@example.com')
        ->assertSee('Ghi chú được mời');
});

it('creates the account and grants access to the shared note', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    $issued = Invitation::issueFor($note, 'moi@example.com', $owner->id);

    $this->post(route('invitation.accept', $issued['token']), [
        'name' => 'Người Mới',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
    ])->assertRedirect(route('note', $note->id));

    $newUser = User::query()->where('email', 'moi@example.com')->first();

    expect($newUser)->not->toBeNull()
        ->and($newUser->name)->toBe('Người Mới');

    $this->assertAuthenticatedAs($newUser);

    expect(PivotForNote::query()
        ->where('note_id', $note->id)
        ->where('shared_with', $newUser->id)
        ->exists())->toBeTrue();

    expect($issued['invitation']->fresh()->accepted_at)->not->toBeNull();
});

it('creates the account and joins the organization as an active member', function () {
    $host = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    $issued = Invitation::issueFor($org, 'thanhvien@example.com', $host->id);

    $this->post(route('invitation.accept', $issued['token']), [
        'name' => 'Thành Viên',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
    ])->assertRedirect(route('organization', $org->id));

    $newUser = User::query()->where('email', 'thanhvien@example.com')->first();
    $membership = OrganizationsMember::query()
        ->where('organizationID', $org->id)
        ->where('userID', $newUser->id)
        ->first();

    expect($membership)->not->toBeNull()
        ->and((bool) $membership->status)->toBeTrue();
});

it('ignores any email supplied in the form and always uses the invited email', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    $issued = Invitation::issueFor($note, 'duocmoi@example.com', $owner->id);

    $this->post(route('invitation.accept', $issued['token']), [
        'name' => 'Kẻ Giả Mạo',
        'email' => 'nanhan@example.com',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
    ]);

    expect(User::query()->where('email', 'nanhan@example.com')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'duocmoi@example.com')->exists())->toBeTrue();
});

it('applies every pending invitation for the same email at once', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    $org = Organization::factory()->create(['hostID' => $owner->id]);

    $noteInvite = Invitation::issueFor($note, 'ca-hai@example.com', $owner->id);
    Invitation::issueFor($org, 'ca-hai@example.com', $owner->id);

    $this->post(route('invitation.accept', $noteInvite['token']), [
        'name' => 'Cả Hai',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
    ]);

    $newUser = User::query()->where('email', 'ca-hai@example.com')->first();

    expect(PivotForNote::query()->where('shared_with', $newUser->id)->exists())->toBeTrue()
        ->and(OrganizationsMember::query()->where('userID', $newUser->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Từ chối lời mời không hợp lệ
// ---------------------------------------------------------------------------

it('rejects an unknown token', function () {
    $this->get(route('invitation.show', 'khong-ton-tai'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');
});

it('rejects an expired invitation', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    $issued = Invitation::issueFor($note, 'hethan@example.com', $owner->id);
    $issued['invitation']->update(['expires_at' => now()->subDay()]);

    $this->get(route('invitation.show', $issued['token']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error');

    $this->post(route('invitation.accept', $issued['token']), [
        'name' => 'Muộn',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
    ])->assertRedirect(route('login'));

    expect(User::query()->where('email', 'hethan@example.com')->exists())->toBeFalse();
});

it('rejects an invitation that was already accepted so the link cannot be reused', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    $issued = Invitation::issueFor($note, 'dungroi@example.com', $owner->id);

    $this->post(route('invitation.accept', $issued['token']), [
        'name' => 'Lần Đầu',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
    ]);

    expect(User::query()->where('email', 'dungroi@example.com')->count())->toBe(1);

    // Dùng lại đúng link đó lần thứ hai phải bị chặn.
    $this->post(route('invitation.accept', $issued['token']), [
        'name' => 'Lần Hai',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
    ])->assertRedirect(route('login'));

    expect(User::query()->where('email', 'dungroi@example.com')->count())->toBe(1);
});

it('sends an already-registered invitee to the login page instead of creating a duplicate account', function () {
    $owner = User::factory()->create();
    $existing = User::factory()->create(['email' => 'dacotaikhoan@example.com']);
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    $issued = Invitation::issueFor($note, $existing->email, $owner->id);

    $this->get(route('invitation.show', $issued['token']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('warning');

    expect(User::query()->where('email', $existing->email)->count())->toBe(1);
});

it('validates the password when accepting an invitation', function () {
    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    $issued = Invitation::issueFor($note, 'yeu@example.com', $owner->id);

    $this->post(route('invitation.accept', $issued['token']), [
        'name' => 'Mật Khẩu Yếu',
        'password' => '123',
        'password_confirmation' => '456',
    ])->assertSessionHasErrors('password');

    expect(User::query()->where('email', 'yeu@example.com')->exists())->toBeFalse();
});
