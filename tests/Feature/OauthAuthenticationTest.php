<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Đăng nhập bằng Google (OauthAuthenticationController). Socialite được mock
// toàn bộ: test không gọi ra ngoài mạng và không cần GOOGLE_* trong .env.
// ---------------------------------------------------------------------------

/** Dựng một user Socialite trả về từ provider, không cần chạm mạng. */
function makeSocialiteUser(string $id = 'google-123', string $email = 'oauth@example.com', string $name = 'OAuth User'): SocialiteUser
{
    return (new SocialiteUser)->map([
        'id' => $id,
        'email' => $email,
        'name' => $name,
    ]);
}

/** Mock Socialite::driver($provider) để trả về provider giả với user cho trước. */
function mockSocialite(string $provider, SocialiteUser $user): void
{
    $driver = Mockery::mock(SocialiteProvider::class);
    $driver->shouldReceive('user')->andReturn($user);

    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}

it('redirects to the provider consent screen for a supported provider', function () {
    $driver = Mockery::mock(SocialiteProvider::class);
    $driver->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth?client_id=x'));

    Socialite::shouldReceive('driver')->with('google')->once()->andReturn($driver);

    $this->get(route('oauth.redirect', 'google'))
        ->assertRedirect('https://accounts.google.com/o/oauth2/auth?client_id=x');
});

it('sends an unsupported provider back home with an error instead of a 500', function () {
    // {provider} nhận chuỗi tự do trên URL — giá trị lạ không được đi tới Socialite.
    $this->get(route('oauth.redirect', 'yahoo'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');

    $this->get(route('oauth.callback', 'yahoo'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

it('creates and logs in a brand-new user from the provider callback', function () {
    mockSocialite('google', makeSocialiteUser());

    $this->get(route('oauth.callback', 'google'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('success');

    $user = User::where('provider_id', 'google-123')->where('provider_name', 'google')->first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('oauth@example.com')
        ->and($user->name)->toBe('OAuth User')
        // Tài khoản OAuth không có mật khẩu cục bộ và nhận số dư mặc định (0,
        // theo migration change_balances_to_decimal) giống hệt signup thường.
        ->and($user->password)->toBeNull()
        ->and((float) $user->balance)->toBe(0.0);

    $this->assertAuthenticatedAs($user);
});

it('logs the returning provider account into its existing user instead of creating a duplicate', function () {
    $existing = User::forceCreate([
        'name' => 'Old Name',
        'email' => 'oauth@example.com',
        'provider_id' => 'google-123',
        'provider_name' => 'google',
    ]);

    // Lần đăng nhập sau: cùng provider_id, tên trên Google đã đổi.
    mockSocialite('google', makeSocialiteUser(name: 'New Name'));

    $this->get(route('oauth.callback', 'google'))
        ->assertRedirect(route('home'));

    // Từ 17-08 controller tìm theo email trước và chỉ cập nhật provider_id/
    // provider_name — tên hiển thị cục bộ được giữ nguyên, không bị tên trên
    // Google ghi đè mỗi lần đăng nhập. Điều bất biến quan trọng: không nhân bản.
    expect(User::where('provider_id', 'google-123')->count())->toBe(1)
        ->and($existing->fresh()->name)->toBe('Old Name');

    $this->assertAuthenticatedAs($existing->fresh());
});

it('regenerates the session id after an oauth login to block session fixation', function () {
    mockSocialite('google', makeSocialiteUser());

    $this->get(route('home')); // mở session trước khi đăng nhập
    $before = session()->getId();

    $this->get(route('oauth.callback', 'google'));

    expect(session()->getId())->not->toBe($before);
});

it('rate limits the oauth endpoints like the other authentication routes', function () {
    foreach (range(1, 10) as $i) {
        $this->get(route('oauth.redirect', 'yahoo'))->assertRedirect(route('home'));
    }

    // Limiter `authentication`: 10 request/phút theo IP.
    $this->get(route('oauth.redirect', 'yahoo'))->assertStatus(429);
});

it('links the provider login to an existing account that signed up with the same email', function () {
    $existing = User::factory()->create(['email' => 'oauth@example.com']);

    mockSocialite('google', makeSocialiteUser());

    $this->get(route('oauth.callback', 'google'))
        ->assertRedirect(route('home'));

    // Không được tạo user thứ hai trùng email; tài khoản cũ phải được nối provider.
    expect(User::where('email', 'oauth@example.com')->count())->toBe(1);
    $this->assertAuthenticatedAs($existing->fresh());
});
