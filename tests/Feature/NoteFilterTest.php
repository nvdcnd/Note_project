<?php

use App\Models\MarkAsDone;
use App\Models\Note;
use App\Models\PivotForNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeDoneNote(User $user, string $title): Note
{
    $note = Note::factory()->create(['creater_id' => $user->id, 'title' => $title]);
    MarkAsDone::create(['noteID' => $note->id, 'userID' => $user->id, 'status' => true]);

    return $note;
}

it('shows own and shared notes on the default (all) filter', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();
    $own = Note::factory()->create(['creater_id' => $user->id, 'title' => 'Own note']);
    $shared = Note::factory()->create(['creater_id' => $friend->id, 'title' => 'Shared note']);
    PivotForNote::create(['note_id' => $shared->id, 'shared_with' => $user->id]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Own note')
        ->assertSee('Shared note');
});

it('filters to done notes only', function () {
    $user = User::factory()->create();
    makeDoneNote($user, 'Done note');
    $open = Note::factory()->create(['creater_id' => $user->id, 'title' => 'Open note']);

    $this->actingAs($user)
        ->get(route('home', ['filter' => 'done']))
        ->assertOk()
        ->assertSee('Done note')
        ->assertDontSee('Open note');
});

it('filters to not-done notes only', function () {
    $user = User::factory()->create();
    makeDoneNote($user, 'Done note');
    $open = Note::factory()->create(['creater_id' => $user->id, 'title' => 'Open note']);

    $this->actingAs($user)
        ->get(route('home', ['filter' => 'not-done']))
        ->assertOk()
        ->assertSee('Open note')
        ->assertDontSee('Done note');
});

it('filters to notes created by me only', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();
    $own = Note::factory()->create(['creater_id' => $user->id, 'title' => 'My note']);
    $theirs = Note::factory()->create(['creater_id' => $friend->id, 'title' => 'Their note']);
    PivotForNote::create(['note_id' => $theirs->id, 'shared_with' => $user->id]);

    $this->actingAs($user)
        ->get(route('home', ['filter' => 'by-me']))
        ->assertOk()
        ->assertSee('My note')
        ->assertDontSee('Their note');
});

it('filters to notes shared with me only', function () {
    $user = User::factory()->create();
    $friend = User::factory()->create();
    $own = Note::factory()->create(['creater_id' => $user->id, 'title' => 'My note']);
    $shared = Note::factory()->create(['creater_id' => $friend->id, 'title' => 'Shared note']);
    PivotForNote::create(['note_id' => $shared->id, 'shared_with' => $user->id]);

    $this->actingAs($user)
        ->get(route('home', ['filter' => 'shared']))
        ->assertOk()
        ->assertSee('Shared note')
        ->assertDontSee('My note');
});

it('falls back to the all filter for unknown filter values', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $user->id, 'title' => 'Visible note']);

    $this->actingAs($user)
        ->get(route('home', ['filter' => 'bogus']))
        ->assertOk()
        ->assertSee('Visible note')
        ->assertSee('Tất cả ghi chú');
});
