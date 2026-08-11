<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure the `org_done` column exists on the note table.
     *
     * The dev database was migrated from an older revision of the note migration
     * which predates the `org_done` column. This guard keeps both existing and
     * fresh databases in sync without throwing a duplicate-column error.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('note', 'org_done')) {
            Schema::table('note', function (Blueprint $table) {
                $table->boolean('org_done')->nullable()->after('organizationID');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('note', 'org_done')) {
            Schema::table('note', function (Blueprint $table) {
                $table->dropColumn('org_done');
            });
        }
    }
};
