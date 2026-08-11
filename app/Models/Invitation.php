<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Lời mời gửi tới một email chưa có tài khoản Noteket.
 *
 * `invitable` là thứ người được mời sẽ nhận sau khi đăng ký: một Note (được
 * chia sẻ) hoặc một Organization (được vào làm thành viên).
 */
class Invitation extends Model
{
    /** Lời mời hết hạn sau 7 ngày. */
    public const TTL_DAYS = 7;

    protected $table = 'invitations';

    protected $fillable = [
        'email',
        'token',
        'invitable_type',
        'invitable_id',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function invitable(): MorphTo
    {
        return $this->morphTo();
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Tạo lời mời và trả về token nguyên bản để nhúng vào link email.
     * Token nguyên bản KHÔNG được lưu lại ở bất kỳ đâu.
     *
     * @return array{invitation: self, token: string}
     */
    public static function issueFor(Model $invitable, string $email, ?int $invitedBy = null): array
    {
        $plainToken = Str::random(64);

        $invitation = self::create([
            'email' => strtolower(trim($email)),
            'token' => self::hashToken($plainToken),
            'invitable_type' => $invitable->getMorphClass(),
            'invitable_id' => $invitable->getKey(),
            'invited_by' => $invitedBy,
            'expires_at' => now()->addDays(self::TTL_DAYS),
        ]);

        return ['invitation' => $invitation, 'token' => $plainToken];
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Id của mọi lời mời còn hiệu lực gửi tới một email.
     *
     * @return array<int, int>
     */
    public static function pendingIdsFor(string $email): array
    {
        return self::query()
            ->where('email', strtolower(trim($email)))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Nạp một lời mời theo id, trả về null nếu không có.
     */
    public static function findById(int $id): ?self
    {
        return self::query()->find($id);
    }

    /**
     * Tìm lời mời theo token nguyên bản lấy từ URL.
     */
    public static function findByToken(string $plainToken): ?self
    {
        return self::query()->where('token', self::hashToken($plainToken))->first();
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function markAccepted(): void
    {
        $this->accepted_at = now();
        $this->save();
    }
}
