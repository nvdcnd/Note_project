<?php

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PayOS\Models\Webhooks\WebhookData;
use PayOS\PayOS;
use PayOS\Resources\V2\PaymentRequests\PaymentRequests;
use PayOS\Resources\Webhooks\Webhooks;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Nạp point qua PayOS (PaymentController). SDK PayOS được mock và bind đè
// singleton trong container: test không gọi ra ngoài mạng, không cần PAYOS_*.
// ---------------------------------------------------------------------------

/**
 * Bind một PayOS giả vào container. Trả về mock để test khai expectation
 * trên $payos->paymentRequests và $payos->webhooks.
 */
function fakePayOS(): PayOS
{
    $payos = Mockery::mock(PayOS::class);
    $payos->paymentRequests = Mockery::mock(PaymentRequests::class);
    $payos->webhooks = Mockery::mock(Webhooks::class);

    app()->instance(PayOS::class, $payos);

    return $payos;
}

/** Payment mẫu, forceCreate để chủ động điền mọi cột NOT NULL. */
function makePayment(User $user, array $overrides = []): Payment
{
    return Payment::forceCreate(array_merge([
        'orderCode' => 900000 + $user->id,
        'amount' => 10000,
        'point' => 10,
        'status' => 'Pending',
        'userID' => $user->id,
    ], $overrides));
}

/** Payload webhook đã "verify thành công" — đúng kiểu WebhookData mà SDK trả về. */
function makeWebhookData(int $orderCode, string $code = '00'): WebhookData
{
    return new WebhookData(
        orderCode: $orderCode,
        amount: 10000,
        description: 'Noteket test topup',
        accountNumber: '0123456789',
        reference: 'FT-TEST-001',
        transactionDateTime: '2026-08-17 10:00:00',
        currency: 'VND',
        paymentLinkId: 'plink-test-1',
        code: $code,
        desc: 'success',
    );
}

beforeEach(function () {
    $this->payos = fakePayOS();
});

// ---------------------------------------------------------------------------
// Cổng vào. Route verify nằm NGOÀI auth vì bên gọi là server PayOS, không phải
// người dùng: URL cố định, không {id}, tra payment bằng orderCode trong payload.
// ---------------------------------------------------------------------------

it('keeps the user-facing payment endpoints behind authentication', function () {
    $this->get(route('point.history'))->assertRedirect(route('login'));
    $this->post(route('point.payment.create'), ['points' => 10])->assertRedirect(route('login'));
});

it('rejects an unauthenticated webhook with a bad signature without crashing', function () {
    $user = User::factory()->create(['balance' => 0]);
    $payment = makePayment($user);

    $this->payos->webhooks
        ->shouldReceive('verify')
        ->andThrow(new Exception('bad signature'));

    // Server PayOS gọi không kèm session: middleware không được 500, và payload
    // sai chữ ký phải bị từ chối bằng 400 trước khi đụng dữ liệu.
    $this->postJson(route('point.payment.verify'), ['forged' => 'payload'])
        ->assertStatus(400);

    expect($payment->fresh()->status)->toBe('Pending')
        ->and((float) $user->fresh()->balance)->toBe(0.0);
});

// ---------------------------------------------------------------------------
// Lịch sử nạp
// ---------------------------------------------------------------------------

it('shows only the current user\'s topup history', function () {
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();

    makePayment($me, ['amount' => 111000, 'point' => 111]);
    makePayment($someoneElse, ['amount' => 999000, 'point' => 999]);

    $this->actingAs($me)
        ->get(route('point.history'))
        ->assertOk()
        ->assertViewIs('payment.history')
        ->assertSee('111.000')
        ->assertDontSee('999.000');
});

// ---------------------------------------------------------------------------
// Tạo yêu cầu nạp
// ---------------------------------------------------------------------------

it('rejects invalid point amounts before anything is saved or sent to PayOS', function (mixed $points) {
    $user = User::factory()->create();

    $payload = $points === null ? [] : ['points' => $points];

    $this->actingAs($user)
        ->from(route('balance'))
        ->post(route('point.payment.create'), $payload)
        ->assertRedirect(route('balance'))
        ->assertSessionHasErrors('points');

    // Không được để lại bản ghi Pending mồ côi khi input sai.
    expect(Payment::count())->toBe(0);
})->with([
    'zero' => 0,
    'negative' => -5,
    'not a number' => 'abc',
    'missing' => null,
]);

it('creates a pending payment and redirects the user to the PayOS checkout page', function () {
    $user = User::factory()->create();

    $this->payos->paymentRequests
        ->shouldReceive('create')
        ->once()
        ->andReturn(['checkoutUrl' => 'https://pay.payos.vn/web/abc123']);

    $this->actingAs($user)
        ->post(route('point.payment.create'), ['points' => 10])
        ->assertRedirect('https://pay.payos.vn/web/abc123');

    $payment = Payment::where('userID', $user->id)->first();

    expect($payment)->not->toBeNull()
        ->and((float) $payment->amount)->toBe(10000.0)   // 10 point x 1.000đ
        ->and((float) $payment->point)->toBe(10.0)
        ->and($payment->status)->toBe('Pending')
        ->and($payment->orderCode)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Webhook xác nhận thanh toán — chỗ duy nhất cộng tiền, đáng test nhất dự án.
// Mọi request giả lập đúng vai server PayOS: không đăng nhập, chỉ có payload.
// ---------------------------------------------------------------------------

it('credits the points and marks the payment finished on a valid webhook', function () {
    $user = User::factory()->create(['balance' => 0]);
    $payment = makePayment($user);

    $this->payos->webhooks
        ->shouldReceive('verify')
        ->andReturn(makeWebhookData($payment->orderCode));

    $this->postJson(route('point.payment.verify'))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($payment->fresh()->status)->toBe('Finished')
        ->and((float) $user->fresh()->balance)->toBe(10.0);
});

it('ignores a replayed webhook for an already finished payment', function () {
    $user = User::factory()->create(['balance' => 10]);
    $payment = makePayment($user, ['status' => 'Finished']);

    $this->payos->webhooks
        ->shouldReceive('verify')
        ->andReturn(makeWebhookData($payment->orderCode));

    // Trả 200 để PayOS ngừng gửi lại, nhưng tuyệt đối không cộng lần hai.
    $this->postJson(route('point.payment.verify'))
        ->assertOk();

    expect((float) $user->fresh()->balance)->toBe(10.0)
        ->and($payment->fresh()->status)->toBe('Finished');
});

it('does not credit points when PayOS reports a non-success code', function () {
    $user = User::factory()->create(['balance' => 0]);
    $payment = makePayment($user);

    $this->payos->webhooks
        ->shouldReceive('verify')
        ->andReturn(makeWebhookData($payment->orderCode, code: '01'));

    $this->postJson(route('point.payment.verify'))
        ->assertOk();

    expect($payment->fresh()->status)->toBe('Pending')
        ->and((float) $user->fresh()->balance)->toBe(0.0);
});

it('returns 404 for a webhook about an order code that does not exist', function () {
    $this->payos->webhooks
        ->shouldReceive('verify')
        ->andReturn(makeWebhookData(123456789));

    $this->postJson(route('point.payment.verify'))
        ->assertNotFound();
});
