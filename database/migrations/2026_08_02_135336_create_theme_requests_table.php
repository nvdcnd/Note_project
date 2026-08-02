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
        Schema::create('theme_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('style');
            $table->string('drag_type');
            $table->float('price');
            $table->string('catalog_link');
            $table->string('status');
            $table->string('email');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_requests');
    }
};
