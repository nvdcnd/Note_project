<?php

use App\Mail\change_host_organization;
use App\Mail\Mail40account;
use App\Mail\organization2user_trans_otp;
use App\Mail\OrganizationInvitation;
use App\Mail\Password_change;
use App\Mail\Theme4org_trans_otp;
use App\Mail\user2organization_trans_otp;
use App\Mail\user2theme4_trans_otp;
use App\Mail\user2user_trans_otp;
use App\Mail\user_accept_host_organization;
use App\Mail\user_accept_organization;
use App\Mail\UserEmail;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Organization2userTransaction;
use App\Models\Theme4orgTransaction;
use App\Models\User;
use App\Models\User2organizationTransaction;
use App\Models\User2theme4Transaction;
use App\Models\User2userTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SendQueuedMailable;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Mail đưa vào queue phải sống sót qua đúng đường đi của worker:
// serialize payload -> unserialize -> render. Mail::fake() không render mail,
// nên chỉ lớp test này mới bắt được lỗi kiểu "view không nhận được biến"
// (thuộc tính private trên mailable) — lỗi chỉ nổ trong process worker.
// ---------------------------------------------------------------------------

it('renders every queued mailable through the worker path: serialize, unserialize, render', function () {
    $sender = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $sender->id]);
    $organization = Organization::factory()->create(['hostID' => $sender->id]);
    $token = str_repeat('a', 64);

    $queued = [
        new UserEmail($sender, $note),
        new Mail40account('chuadangky@example.com', $note, $token),
        new OrganizationInvitation($organization, 'chuadangky@example.com', $token),
    ];

    foreach ($queued as $mailable) {
        $job = unserialize(serialize(new SendQueuedMailable($mailable->to('qa@example.com'))));

        expect($job->mailable->render())->toBeString()->not->toBeEmpty();
    }
});

it('shows the sharer name and the note link in the shared-note email', function () {
    $sender = User::factory()->create(['name' => 'Nguoi gui QA']);
    $note = Note::factory()->create(['creater_id' => $sender->id]);

    $job = unserialize(serialize(
        new SendQueuedMailable((new UserEmail($sender, $note))->to('qa@example.com'))
    ));

    expect($job->mailable->render())
        ->toContain('Nguoi gui QA')
        ->toContain(route('note', ['id' => $note->id]));
});

// ---------------------------------------------------------------------------
// Mail gửi đồng bộ không đi qua serialize, chỉ cần render không nổ.
// View transaction_notification chỉ đọc $transaction->id nên model chưa lưu
// là đủ; các mail còn lại nhận id / chuỗi qua tham số with.
// ---------------------------------------------------------------------------

it('renders every synchronous mailable', function () {
    $mailables = [
        new user2user_trans_otp(new User2userTransaction, '123456'),
        new user2organization_trans_otp(new User2organizationTransaction, '123456'),
        new organization2user_trans_otp(new Organization2userTransaction, '123456'),
        new user2theme4_trans_otp(new User2theme4Transaction, '123456'),
        new Theme4org_trans_otp(new Theme4orgTransaction, '123456'),
        new Password_change('ma-doi-mat-khau'),
        new user_accept_organization(1),
        new change_host_organization(1),
        new user_accept_host_organization(1),
    ];

    foreach ($mailables as $mailable) {
        expect($mailable->render())->toBeString()->not->toBeEmpty();
    }
});
