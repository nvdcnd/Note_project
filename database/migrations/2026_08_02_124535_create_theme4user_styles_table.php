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
        Schema::create('theme4user_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme4userID')->references('id')->on('theme4users')->onDelete('cascade');
            $table->json('home_page_style');
            $table->json('note_page_style');
            $table->json('account_page_style');
            $table->json("transaction_page_style");
            $table->json("transaction_history_style");
            $table->json("transaction_details_style");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme4user_styles');
    }
};
