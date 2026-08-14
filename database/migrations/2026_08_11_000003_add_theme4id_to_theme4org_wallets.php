<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the missing `theme4ID` column to theme4org_wallets so org theme
     * purchases can record ownership (BE-9 / E15).
     */
    public function up(): void
    {
        Schema::table('theme4org_wallets', function (Blueprint $table) {
            $table->foreignId('theme4ID')->nullable()->after('organizationID')->constrained('theme4orgs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('theme4org_wallets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('theme4ID');
        });
    }
};
