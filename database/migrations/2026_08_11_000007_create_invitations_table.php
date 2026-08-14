<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lời mời gửi tới email CHƯA có tài khoản.
     *
     * Trước đây luồng "chia sẻ cho người chưa đăng ký" chỉ gửi email chứ không
     * lưu gì cả, nên không thể có đường quay lại: `pivot_for_note.shared_with`
     * là khóa ngoại tới `users.id` nên không thể chứa email của người chưa có
     * tài khoản. Bảng này giữ lời mời cho tới khi người đó đăng ký.
     *
     * `token` lưu dạng băm SHA-256; token nguyên bản chỉ nằm trong link gửi qua
     * email, không lưu ở đâu — lộ database cũng không dùng lại được lời mời.
     */
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id()->autoIncrement()->unique()->primary();
            $table->string('email')->index();
            $table->string('token', 64)->unique();
            $table->string('invitable_type');
            $table->unsignedBigInteger('invitable_id');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['invitable_type', 'invitable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
