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
        Schema::create('theme4org_wallets', function (Blueprint $table) {
            $table->id()->autoIncrement()->unique()->primary();
            $table->foreignId('organizationID')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreignId('theme3orgID')->references('id')->on('organizations')->onDelete('cascade');
            // $table->float('balance')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme4org_wallets');
    }
};
