<?php

use App\Mail\Password_change;
use App\Models\Note;
use App\Models\Organization;
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
// Hai endpoint share tỏa email theo danh sách người nhận, nên phải có trần
// số người nhận và rate limit như các route giao dịch.
// ---------------------------------------------------------------------------

it('rejects sharing a note with more than 20 recipients', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);

    $emails = collect(range(1, 21))->map(fn ($i) => "nguoi{$i}@example.com")->all();

    $this->actingAs($owner)
        ->post(route('share.note', $note->id), ['shared_with' => $emails])
        ->assertSessionHasErrors('shared_with');

    Mail::assertNothingOutgoing();
});

it('rejects inviting more than 20 emails to an organization via the text input', function () {
    Mail::fake();

    $host = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);

    $emails = collect(range(1, 21))->map(fn ($i) => "nguoi{$i}@example.com")->implode(',');

    $this->actingAs($host)
        ->post(route('share.organization', $org->id), ['user_list_text' => $emails])
        ->assertRedirect(route('organization', $org->id))
        ->assertSessionHas('error');

    Mail::assertNothingOutgoing();
});

it('rate limits note sharing after 5 requests in a minute', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $owner->id]);

    foreach (range(1, 5) as $i) {
        $this->actingAs($owner)
            ->post(route('share.note', $note->id), ['shared_with' => ["nguoi{$i}@example.com"]])
            ->assertRedirect();
    }

    $this->actingAs($owner)
        ->post(route('share.note', $note->id), ['shared_with' => ['nguoi6@example.com']])
        ->assertStatus(429);
});
