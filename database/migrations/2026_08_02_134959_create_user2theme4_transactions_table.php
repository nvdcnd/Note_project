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
        Schema::create('user2theme4_transactions', function (Blueprint $table) {
            $table->id()->autoIncrement()->unique()->primary();
            $table->foreignId('userID')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('theme4ID')->references('id')->on('theme4orgs')->onDelete('cascade');
            $table->foreignId('current_hostID')->references('id')->on('users')->onDelete('cascade');
            $table->float('amount');
            $table->string('status');
            $table->string('otp');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user2theme4_transactions');
    }
};
