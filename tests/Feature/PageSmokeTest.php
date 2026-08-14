<?php

use App\Models\MarkAsDone;
use App\Models\Note;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\Theme4user;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the authenticated home page with notes', function () {
    $user = User::factory()->create();
    Note::factory()->create(['creater_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Tạo ghi chú');
});

it('renders the balance page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('balance'))
        ->assertOk()
        ->assertSee('Số dư');
});

it('renders the settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings'))
        ->assertOk()
        ->assertSee($user->name);
});

it('renders the organizations list and create pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('organizations.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('organizations.create'))
        ->assertOk()
        ->assertSee('Tạo tổ chức');
});

it('renders an organization page for the host with its notes', function () {
    $host = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $host->id, 'status' => true]);
    Note::factory()->create(['creater_id' => $host->id, 'organizationID' => $org->id]);

    $this->actingAs($host)
        ->get(route('organization', $org->id))
        ->assertOk()
        ->assertSee($org->name);

    $this->actingAs($host)
        ->get(route('organization.dashboard', $org->id))
        ->assertOk()
        ->assertSee('Bảng điều hành');
});

it('renders the theme store for the user', function () {
    $user = User::factory()->create();
    Theme4user::create(['name' => 'Mùa xuân', 'description' => 'Tươi mới', 'drag_type' => '1', 'price' => 100]);

    $this->actingAs($user)
        ->get(route('themes.index'))
        ->assertOk()
        ->assertSee('Mùa xuân');
});

it('marks a note as done and shows it as completed on home', function () {
    $user = User::factory()->create();
    $note = Note::factory()->create(['creater_id' => $user->id]);
    MarkAsDone::create(['noteID' => $note->id, 'userID' => $user->id, 'status' => false]);

    $this->actingAs($user)
        ->post(route('mark.done', $note->id))
        ->assertRedirect(route('note', $note->id));

    expect(MarkAsDone::where('noteID', $note->id)->where('userID', $user->id)->first()->status)->toBeTrue();
});

it('forbids a member from viewing the host-only organization dashboard', function () {
    $host = User::factory()->create();
    $member = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $member->id, 'status' => true]);

    $this->actingAs($member)
        ->get(route('organization.dashboard', $org->id))
        ->assertRedirect(route('organization', $org->id));
});
