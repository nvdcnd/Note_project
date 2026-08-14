<?php

use App\Models\Organization;
use App\Models\OrganizationsMember;
use App\Models\Theme4org;
use App\Models\Theme4orgWallet;
use App\Models\Theme4user;
use App\Models\Theme4userWallet;
use App\Models\User;
use App\Support\ThemeStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeUserTheme(array $overrides = []): Theme4user
{
    return Theme4user::create(array_merge([
        'name' => 'Bạc hà',
        'description' => 'Xanh dịu',
        'drag_type' => 2,
        'price' => 100,
        'style' => ['yellow' => '#34D399', 'sticky' => '#A7F3D0', 'page' => '#ECFDF5'],
    ], $overrides));
}

function makeOrgTheme(array $overrides = []): Theme4org
{
    return Theme4org::create(array_merge([
        'name' => 'Xanh biển',
        'description' => 'Tông xanh',
        'drag_type' => 3,
        'price' => 200,
        'style' => ['yellow' => '#60A5FA', 'sticky' => '#BFDBFE', 'page' => '#EFF6FF'],
    ], $overrides));
}

// ---------------------------------------------------------------------------
// E-A1..E-A3 — áp dụng chủ đề cá nhân
// ---------------------------------------------------------------------------

it('applies a theme the user owns and stores the theme id, not the wallet row id', function () {
    $user = User::factory()->create();
    $theme = makeUserTheme();

    // Tạo thêm một dòng ví khác trước để id ví lệch hẳn id chủ đề — nếu code lưu
    // nhầm id ví (lỗi cũ) thì assert bên dưới sẽ bắt được.
    $otherUser = User::factory()->create();
    Theme4userWallet::create(['userID' => $otherUser->id, 'theme4ID' => $theme->id]);
    $wallet = Theme4userWallet::create(['userID' => $user->id, 'theme4ID' => $theme->id]);

    expect($wallet->id)->not->toBe($theme->id);

    $this->actingAs($user)
        ->post(route('themes.apply', $theme->id))
        ->assertRedirect(route('themes.show', $theme->id))
        ->assertSessionHas('success');

    expect($user->fresh()->theme4_id)->toBe($theme->id);
});

it('refuses to apply a theme the user does not own', function () {
    $user = User::factory()->create();
    $theme = makeUserTheme();

    $this->actingAs($user)
        ->post(route('themes.apply', $theme->id))
        ->assertRedirect(route('themes.show', $theme->id))
        ->assertSessionHas('error');

    expect($user->fresh()->theme4_id)->toBeNull();
});

it('returns 404 when applying a theme that does not exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('themes.apply', 999999))
        ->assertNotFound();
});

it('resets back to the default look', function () {
    $theme = makeUserTheme();
    $user = User::factory()->create(['theme4_id' => $theme->id]);
    Theme4userWallet::create(['userID' => $user->id, 'theme4ID' => $theme->id]);

    $this->actingAs($user)
        ->post(route('themes.reset'))
        ->assertRedirect(route('themes.index'));

    expect($user->fresh()->theme4_id)->toBeNull();
});

it('requires authentication to apply a theme', function () {
    $theme = makeUserTheme();

    $this->post(route('themes.apply', $theme->id))->assertRedirect(route('login'));
});

// ---------------------------------------------------------------------------
// E-A6 — chủ đề phải thực sự đổi giao diện
// ---------------------------------------------------------------------------

it('renders the applied theme colours as css variables in the layout', function () {
    $theme = makeUserTheme();
    $user = User::factory()->create(['theme4_id' => $theme->id]);

    $response = $this->actingAs($user)->get(route('home'))->assertOk();

    expect($response->getContent())
        ->toContain('--nk-yellow: #34D399;')
        ->toContain('--nk-sticky: #A7F3D0;')
        ->toContain('--nk-page: #ECFDF5;');
});

it('falls back to the default palette when no theme is applied', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home'))->assertOk();

    expect($response->getContent())->toContain('--nk-yellow: #FACC15;');
});

it('exposes the theme drag type on the body element', function () {
    $theme = makeUserTheme(['drag_type' => 3]);
    $user = User::factory()->create(['theme4_id' => $theme->id]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('data-drag-type="3"', false);
});

it('lets the organization theme win over the personal theme inside that organization', function () {
    $userTheme = makeUserTheme();
    $orgTheme = makeOrgTheme();

    $host = User::factory()->create(['theme4_id' => $userTheme->id]);
    $org = Organization::factory()->create(['hostID' => $host->id, 'themeID' => $orgTheme->id]);
    OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $host->id, 'status' => true]);

    $response = $this->actingAs($host)->get(route('organization', $org->id))->assertOk();

    expect($response->getContent())
        ->toContain('--nk-yellow: #60A5FA;')
        ->not->toContain('--nk-yellow: #34D399;');
});

// ---------------------------------------------------------------------------
// Bảo mật — style đến từ DB nên không được tin
// ---------------------------------------------------------------------------

it('drops style values that are not plain hex colours so css cannot be injected', function () {
    $theme = makeUserTheme(['style' => [
        'yellow' => 'red; } body { display: none; } :root { --x: #fff',
        'sticky' => '#A7F3D0',
    ]]);
    $user = User::factory()->create(['theme4_id' => $theme->id]);

    $response = $this->actingAs($user)->get(route('home'))->assertOk();

    expect($response->getContent())
        ->not->toContain('display: none')
        ->toContain('--nk-yellow: #FACC15;')   // giá trị bẩn bị bỏ, quay về mặc định
        ->toContain('--nk-sticky: #A7F3D0;');  // giá trị hợp lệ vẫn được giữ
});

it('normalises an out-of-range drag type to the default', function () {
    expect(ThemeStyle::normalizeDragType(99))->toBe(1)
        ->and(ThemeStyle::normalizeDragType(null))->toBe(1)
        ->and(ThemeStyle::normalizeDragType('2'))->toBe(2);
});

// ---------------------------------------------------------------------------
// E-A4/E-A5 — áp dụng chủ đề cho tổ chức
// ---------------------------------------------------------------------------

it('lets the organization host apply an owned organization theme', function () {
    $host = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    $theme = makeOrgTheme();
    Theme4orgWallet::create(['organizationID' => $org->id, 'theme4ID' => $theme->id]);

    $this->actingAs($host)
        ->post(route('themes.org.apply', ['organizationID' => $org->id, 'themeID' => $theme->id]))
        ->assertRedirect(route('organization.settings', $org->id))
        ->assertSessionHas('success');

    expect($org->fresh()->themeID)->toBe($theme->id);
});

it('forbids a non-host member from applying an organization theme', function () {
    $host = User::factory()->create();
    $member = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    OrganizationsMember::create(['organizationID' => $org->id, 'userID' => $member->id, 'status' => true]);
    $theme = makeOrgTheme();
    Theme4orgWallet::create(['organizationID' => $org->id, 'theme4ID' => $theme->id]);

    $this->actingAs($member)
        ->post(route('themes.org.apply', ['organizationID' => $org->id, 'themeID' => $theme->id]))
        ->assertRedirect(route('organization', $org->id))
        ->assertSessionHas('error');

    expect($org->fresh()->themeID)->toBeNull();
});

it('refuses to apply an organization theme the organization has not bought', function () {
    $host = User::factory()->create();
    $org = Organization::factory()->create(['hostID' => $host->id]);
    $theme = makeOrgTheme();

    $this->actingAs($host)
        ->post(route('themes.org.apply', ['organizationID' => $org->id, 'themeID' => $theme->id]))
        ->assertSessionHas('error');

    expect($org->fresh()->themeID)->toBeNull();
});

it('resets the organization theme back to default', function () {
    $host = User::factory()->create();
    $theme = makeOrgTheme();
    $org = Organization::factory()->create(['hostID' => $host->id, 'themeID' => $theme->id]);

    $this->actingAs($host)
        ->post(route('themes.org.reset', $org->id))
        ->assertRedirect(route('organization.settings', $org->id));

    expect($org->fresh()->themeID)->toBeNull();
});

it('keeps the organization alive when its applied theme is deleted', function () {
    $host = User::factory()->create();
    $theme = makeOrgTheme();
    $org = Organization::factory()->create(['hostID' => $host->id, 'themeID' => $theme->id]);

    $theme->delete();

    // Khóa ngoại phải là nullOnDelete, tuyệt đối không phải cascade — nếu là
    // cascade thì xóa một chủ đề sẽ xóa luôn cả tổ chức.
    $org = $org->fresh();
    expect($org)->not->toBeNull()
        ->and($org->themeID)->toBeNull();
});
