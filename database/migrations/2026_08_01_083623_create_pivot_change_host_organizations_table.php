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
        Schema::create('pivot_change_host_organizations', function (Blueprint $table) {
            $table->id()->autoIncrement()->unique();
            $table->foreignId('organizationID')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('current_host_ID')->constrained('users')->onDelete('cascade');
            $table->foreignId('new_host_ID')->nullable()->constrained('users')->onDelete('cascade');
            $table->boolean('new_host_acceptance_status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pivot_change_host_organizations');
    }
};
