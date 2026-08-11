<?php

use App\Http\Controllers\NoteController;
use App\Models\Note;
use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\Theme4org;
use App\Models\Theme4user;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

/**
 * Tạo $count note cho tổ chức, note sau mới hơn note trước.
 */
function seedOrganizationNotes(Organization $org, User $author, int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        Note::factory()->create([
            'creater_id' => $author->id,
            'organizationID' => $org->id,
            'title' => "Ghi chú tổ chức {$i}",
            'created_at' => now()->addMinutes($i),
        ]);
    }
}

// ---------------------------------------------------------------------------
// E-B1 — thứ tự route: /themes/org không được rơi vào /themes/{id}
// ---------------------------------------------------------------------------

it('resolves /themes/org to the organization theme store, not the user theme show route', function () {
    $route = app('router')->getRoutes()->match(Request::create('/themes/org', 'GET'));

    expect($route->getName())->toBe('themes.org.index');
});

it('renders the organization theme store instead of 404', function () {
    $user = User::factory()->create();
    Theme4org::create(['name' => 'Chủ đề tổ chức', 'description' => 'Mô tả', 'drag_type' => 1, 'price' => 50]);

    $this->actingAs($user)
        ->get(route('themes.org.index'))
        ->assertOk()
        ->assertSee('Chủ đề tổ chức');
});

it('rejects a non-numeric user theme id instead of querying with a string', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/themes/khong-phai-so')
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// E-B2 / E3 — phân trang: note mới nhất không được biến mất
// ---------------------------------------------------------------------------

it('still exposes the newest organization note when the organization has more than one page', function () {
    $host = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $host->id, 'status' => true]);

    $perPage = NoteController::NOTES_PER_PAGE;
    seedOrganizationNotes($org, $host, $perPage + 5);

    // Trang 1 giữ đúng thứ tự hàng đợi: note cũ nhất đứng đầu.
    $this->actingAs($host)
        ->get(route('organization', $org->id))
        ->assertOk()
        ->assertSee('Ghi chú tổ chức 1')
        ->assertDontSee('Ghi chú tổ chức '.($perPage + 5));

    // Trang 2 phải chứa note mới nhất — trước đây oldest()->take(20) làm nó biến mất.
    $this->actingAs($host)
        ->get(route('organization', ['id' => $org->id, 'page' => 2]))
        ->assertOk()
        ->assertSee('Ghi chú tổ chức '.($perPage + 5));
});

it('paginates the home page and keeps the newest note reachable', function () {
    $user = User::factory()->create();
    $perPage = NoteController::NOTES_PER_PAGE;

    for ($i = 1; $i <= $perPage + 3; $i++) {
        Note::factory()->create([
            'creater_id' => $user->id,
            'title' => "Ghi chú cá nhân {$i}",
            'created_at' => now()->addMinutes($i),
        ]);
    }

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Ghi chú cá nhân 1');

    $this->actingAs($user)
        ->get(route('home', ['page' => 2]))
        ->assertOk()
        ->assertSee('Ghi chú cá nhân '.($perPage + 3));
});

it('keeps the active filter when moving to the next page', function () {
    $user = User::factory()->create();
    $perPage = NoteController::NOTES_PER_PAGE;

    for ($i = 1; $i <= $perPage + 2; $i++) {
        Note::factory()->create([
            'creater_id' => $user->id,
            'title' => "Ghi chú chưa xong {$i}",
            'created_at' => now()->addMinutes($i),
        ]);
    }

    $response = $this->actingAs($user)
        ->get(route('home', ['filter' => 'not-done']))
        ->assertOk();

    // withQueryString() phải giữ ?filter=not-done trong link phân trang.
    expect($response->getContent())->toContain('filter=not-done');
});

it('does not paginate when there are fewer notes than one page', function () {
    $user = User::factory()->create();
    Note::factory()->create(['creater_id' => $user->id, 'title' => 'Ghi chú duy nhất']);

    $response = $this->actingAs($user)->get(route('home'))->assertOk();

    expect($response->getContent())->not->toContain('page=2');
});

it('renders the user theme store detail page for a numeric id', function () {
    $user = User::factory()->create();
    $theme = Theme4user::create(['name' => 'Mùa hè', 'description' => 'Rực rỡ', 'drag_type' => 1, 'price' => 10]);

    $this->actingAs($user)
        ->get(route('themes.show', $theme->id))
        ->assertOk()
        ->assertSee('Mùa hè');
});
