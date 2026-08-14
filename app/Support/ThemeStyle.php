<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;

/**
 * Chuyển dữ liệu `style` của chủ đề thành các biến CSS --nk-* mà
 * `public/css/noteket.css` đang dùng, và quyết định chủ đề nào đang có hiệu lực.
 *
 * Quy tắc áp dụng:
 *  - Trong phạm vi một tổ chức: dùng chủ đề của tổ chức nếu tổ chức đã áp dụng,
 *    nếu chưa thì rơi về chủ đề cá nhân của người đang xem.
 *  - Ngoài phạm vi tổ chức: dùng chủ đề cá nhân.
 *  - Không có chủ đề nào: dùng bộ màu mặc định của Noteket.
 */
class ThemeStyle
{
    /**
     * Bộ biến CSS mặc định — phải khớp với khối :root trong noteket.css.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'yellow' => '#FACC15',
        'yellow-dark' => '#EAB308',
        'sticky' => '#FFE86E',
        'pink' => '#FFC0CB',
        'ink' => '#111827',
        'slate' => '#475569',
        'page' => '#fdf8e3',
    ];

    /** Kiểu kéo thả mặc định khi chủ đề không khai báo. */
    public const DEFAULT_DRAG_TYPE = 1;

    /**
     * Chỉ giữ lại các khóa hợp lệ và giá trị đúng định dạng mã màu hex.
     *
     * Đây là lớp chặn CSS injection: giá trị đi thẳng vào thẻ <style> nên tuyệt
     * đối không được tin dữ liệu trong DB. Giá trị lạ bị bỏ qua, không làm vỡ trang.
     *
     * @return array<string, string>
     */
    public static function sanitize(mixed $style): array
    {
        if (! is_array($style)) {
            return [];
        }

        $clean = [];

        foreach (self::DEFAULTS as $key => $default) {
            $value = $style[$key] ?? null;

            if (is_string($value) && preg_match('/^#(?:[0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value) === 1) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * Gộp style của chủ đề lên trên bộ mặc định.
     *
     * @return array<string, string>
     */
    public static function merge(mixed $style): array
    {
        return array_merge(self::DEFAULTS, self::sanitize($style));
    }

    /**
     * Kết xuất thành nội dung cho thẻ <style> đặt sau noteket.css để ghi đè :root.
     */
    public static function toCssVariables(mixed $style): string
    {
        $lines = [];

        foreach (self::merge($style) as $key => $value) {
            $lines[] = sprintf('--nk-%s: %s;', $key, $value);
        }

        return ':root{'.implode('', $lines).'}';
    }

    /**
     * Xác định chủ đề đang có hiệu lực cho lần render này.
     *
     * @return array{style: array<string, string>, drag_type: int, name: ?string}
     */
    public static function resolveFor(?User $user, ?Organization $organization = null): array
    {
        $theme = null;

        if ($organization !== null && $organization->themeID !== null) {
            $theme = $organization->appliedTheme;
        }

        if ($theme === null && $user !== null && $user->theme4_id !== null) {
            $theme = $user->appliedTheme;
        }

        return [
            'style' => self::merge($theme?->style),
            'drag_type' => self::normalizeDragType($theme?->drag_type),
            'name' => $theme?->name,
        ];
    }

    /**
     * drag_type chỉ nhận 1 (cổ điển), 2 (xoay nhẹ), 3 (bay); giá trị lạ về mặc định.
     */
    public static function normalizeDragType(mixed $dragType): int
    {
        $value = is_numeric($dragType) ? (int) $dragType : self::DEFAULT_DRAG_TYPE;

        return in_array($value, [1, 2, 3], true) ? $value : self::DEFAULT_DRAG_TYPE;
    }
}
