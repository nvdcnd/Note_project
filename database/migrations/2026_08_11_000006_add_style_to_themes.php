<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng chủ đề vốn có cột `style` nhưng bị comment trong migration gốc, nên
     * chủ đề không mang theo dữ liệu giao diện nào — áp dụng chủ đề không đổi
     * được gì trên màn hình. Bật lại cột này để chủ đề thực sự có hiệu lực.
     *
     * Nội dung là JSON dạng {"yellow": "#FACC15", "sticky": "#FFE86E", ...},
     * ánh xạ sang các biến CSS --nk-* mà `public/css/noteket.css` đang dùng.
     */
    public function up(): void
    {
        foreach (['theme4users', 'theme4orgs'] as $table) {
            if (Schema::hasColumn($table, 'style')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->json('style')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        foreach (['theme4users', 'theme4orgs'] as $table) {
            if (! Schema::hasColumn($table, 'style')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('style');
            });
        }
    }
};
