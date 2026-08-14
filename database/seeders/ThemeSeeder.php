<?php

namespace Database\Seeders;

use App\Models\Theme4org;
use App\Models\Theme4user;
use Illuminate\Database\Seeder;

/**
 * Chủ đề mẫu để cửa hàng chủ đề có dữ liệu thật ngay sau khi cài đặt.
 *
 * Khóa trong `style` phải khớp với App\Support\ThemeStyle::DEFAULTS, vì chúng
 * được đổ thẳng thành các biến CSS --nk-* mà noteket.css đang dùng.
 */
class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $userThemes = [
            [
                'name' => 'Giấy nhớ cổ điển',
                'description' => 'Bộ màu vàng nguyên bản của Noteket.',
                'drag_type' => 1,
                'price' => 0,
                'style' => [
                    'yellow' => '#FACC15',
                    'yellow-dark' => '#EAB308',
                    'sticky' => '#FFE86E',
                    'pink' => '#FFC0CB',
                    'ink' => '#111827',
                    'slate' => '#475569',
                    'page' => '#fdf8e3',
                ],
            ],
            [
                'name' => 'Bạc hà',
                'description' => 'Xanh bạc hà dịu mắt, hợp làm việc ban ngày.',
                'drag_type' => 2,
                'price' => 120,
                'style' => [
                    'yellow' => '#34D399',
                    'yellow-dark' => '#059669',
                    'sticky' => '#A7F3D0',
                    'pink' => '#BAE6FD',
                    'ink' => '#064E3B',
                    'slate' => '#047857',
                    'page' => '#ECFDF5',
                ],
            ],
            [
                'name' => 'Hoàng hôn',
                'description' => 'Cam hồng ấm, kiểu kéo thả bay bổng.',
                'drag_type' => 3,
                'price' => 250,
                'style' => [
                    'yellow' => '#FB7185',
                    'yellow-dark' => '#E11D48',
                    'sticky' => '#FECDD3',
                    'pink' => '#FDBA74',
                    'ink' => '#4C0519',
                    'slate' => '#9F1239',
                    'page' => '#FFF1F2',
                ],
            ],
        ];

        foreach ($userThemes as $theme) {
            Theme4user::query()->firstOrCreate(['name' => $theme['name']], $theme);
        }

        $orgThemes = [
            [
                'name' => 'Tổ chức — Cổ điển',
                'description' => 'Bộ màu mặc định cho không gian tổ chức.',
                'drag_type' => 1,
                'price' => 0,
                'style' => [
                    'yellow' => '#FACC15',
                    'yellow-dark' => '#EAB308',
                    'sticky' => '#FFE86E',
                    'pink' => '#FFC0CB',
                    'ink' => '#111827',
                    'slate' => '#475569',
                    'page' => '#fdf8e3',
                ],
            ],
            [
                'name' => 'Tổ chức — Xanh biển',
                'description' => 'Tông xanh trung tính, hợp môi trường làm việc nhóm.',
                'drag_type' => 2,
                'price' => 300,
                'style' => [
                    'yellow' => '#60A5FA',
                    'yellow-dark' => '#2563EB',
                    'sticky' => '#BFDBFE',
                    'pink' => '#C7D2FE',
                    'ink' => '#0C1E3E',
                    'slate' => '#1E40AF',
                    'page' => '#EFF6FF',
                ],
            ],
        ];

        foreach ($orgThemes as $theme) {
            Theme4org::query()->firstOrCreate(['name' => $theme['name']], $theme);
        }
    }
}
