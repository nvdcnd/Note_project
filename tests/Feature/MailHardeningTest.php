<?php

use App\Mail\Password_change;
use App\Models\Note;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\User;
use App\Models\User2userTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Mail thông báo giờ đi qua queue thay vì chặn request bằng một vòng SMTP.
// ---------------------------------------------------------------------------

it('queues the password reset mail instead of sending it inline', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post(route('password.forgot.post'), ['email' => $user->email])
        ->assertRedirect(route('password.forgot'));

    Mail::assertQueued(Password_change::class, 1);
});

// ---------------------------------------------------------------------------
// Gửi OTP hỏng không được để lại giao dịch mồ côi: người dùng không có mã để
// xác nhận và cũng không có nút gửi lại, nên giao dịch phải bị xóa.
// ---------------------------------------------------------------------------

it('deletes the pending transaction when the OTP mail cannot be sent', function () {
    $sender = User::factory()->create(['password' => 'mat-khau-qa']);
    $recipient = User::factory()->create();

    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    $this->actingAs($sender)
        ->post(route('user2user_transaction_create'), [
            'password' => 'mat-khau-qa',
            'amount' => 10,
            'to' => $recipient->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(User2userTransaction::query()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Luồng share đã đổi từ "gửi danh sách email" sang "chia sẻ bằng link":
// GET /share/note/{id} và POST /share/organization/{id} không tỏa email nữa,
// nên trần 20 người nhận và throttle 5,1 cũ không còn đối tượng để test.
// Điều còn lại phải giữ: hai endpoint tuyệt đối không mở cho khách vãng lai,
// và không được gửi bất kỳ email nào một cách âm thầm.
// ---------------------------------------------------------------------------

it('requires login before touching the share endpoints and never sends mail from them', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);
    $org = Organization::factory()->create(['hostID' => $owner->id]);

    $this->get(route('share.note', $note->id))->assertRedirect(route('login'));
    $this->post(route('share.organization', $org->id))->assertRedirect(route('login'));

    Mail::assertNothingOutgoing();
});

it('shows the invite screen when a non-member opens an organization share link', function () {
    $host = User::factory()->create();
    $visitor = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);

    $this->actingAs($visitor)
        ->post(route('share.organization', $org->id))
        ->assertOk()
        ->assertViewIs('organizations.invite');

    // Màn hình invite đi kèm bản ghi thành viên chờ duyệt — hai nút nhận/từ chối
    // trên view trỏ vào member.accept / member.decline với id bản ghi này.
    expect(
        OrganizationsMember::where('organizationID', $org->id)
            ->where('userID', $visitor->id)
            ->where('status', false)
            ->exists()
    )->toBeTrue();
});

it('lets the invited visitor accept from the invite screen and become an active member', function () {
    $host = User::factory()->create();
    $visitor = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);

    $this->actingAs($visitor)->post(route('share.organization', $org->id))->assertOk();

    $member = OrganizationsMember::where('organizationID', $org->id)
        ->where('userID', $visitor->id)->first();

    $this->actingAs($visitor)
        ->post(route('member.accept', $member->id))
        ->assertRedirect(route('organization', $org->id));

    expect($member->fresh()->status)->toBeTrue();
});
