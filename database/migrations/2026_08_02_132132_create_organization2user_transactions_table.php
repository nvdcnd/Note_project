<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization2user_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizationID')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreignId('userID')->references('id')->on('users')->onDelete('cascade');
            $table->float('amount');
            $table->string('status');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->foreignId('current_hostID')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization2user_transactions');
    }
};
