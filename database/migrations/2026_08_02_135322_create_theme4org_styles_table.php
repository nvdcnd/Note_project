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
        Schema::create('theme4org_styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme4ID')->references('id')->on('theme4org')->onDelete('cascade');
            $table->json('homepage_style');
            $table->json('note_page_style');
            $table->json("dashboard_page_style");
            $table->json("transaction_page_style");
            $table->json("transaction_history_style");
            $table->json("transaction_details_style");
            $table->json("member_list_page_style");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme4org_styles');
    }
};
