<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an `attempts` counter to every OTP-protected table so we can
     * lock a request after too many failed tries (BE-8 / E12).
     */
    public function up(): void
    {
        foreach ([
            'user2user_transactions',
            'user2organization_transactions',
            'organization2user_transactions',
            'user2theme4_transactions',
            'theme4org_transactions',
            'password_change_requests',
        ] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedTinyInteger('attempts')->default(0);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'user2user_transactions',
            'user2organization_transactions',
            'organization2user_transactions',
            'user2theme4_transactions',
            'theme4org_transactions',
            'password_change_requests',
        ] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('attempts');
            });
        }
    }
};
