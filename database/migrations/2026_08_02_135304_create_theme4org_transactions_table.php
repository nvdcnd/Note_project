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
        Schema::create('theme4org_transactions', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('from')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('organizationID')->references('id')->on('organizations')->onDelete('cascade');
            $table->float('amount');
            $table->string('status');
            $table->foreignID('current_hostID')->nullable()->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('theme4org_transactions');
    }
};
