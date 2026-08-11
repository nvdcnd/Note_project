<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lưu chủ đề đang được áp dụng cho tổ chức.
     *
     * Cột này từng được thêm bằng cách sửa trực tiếp migration
     * `create_organizations_table` (đã chạy) nên không môi trường nào có cột thật.
     * Tách ra migration riêng để mọi môi trường đều nhận được thay đổi.
     *
     * Dùng nullOnDelete() chứ KHÔNG phải cascade: xóa một chủ đề chỉ được phép
     * gỡ chủ đề khỏi tổ chức, tuyệt đối không được xóa luôn tổ chức.
     */
    public function up(): void
    {
        if (Schema::hasColumn('organizations', 'themeID')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('themeID')
                ->nullable()
                ->after('balance')
                ->constrained('theme4orgs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('organizations', 'themeID')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('themeID');
        });
    }
};
