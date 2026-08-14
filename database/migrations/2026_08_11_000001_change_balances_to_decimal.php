<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert float money columns to decimal to avoid IEEE-754 rounding errors (BE-5 / E14).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->change();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->float('balance')->default(0)->change();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->float('balance')->default(0)->change();
        });
    }
};
