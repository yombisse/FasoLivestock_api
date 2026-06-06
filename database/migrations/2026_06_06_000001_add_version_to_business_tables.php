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
        // Add version field to business tables for conflict resolution
        $businessTables = [
            'animals',
            'transactions',
            'evenements',
            'lots',
            'notifications',
            'naissances',
        ];

        foreach ($businessTables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1)->after('last_modified_by');
                $table->index('version');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove version field from business tables
        $businessTables = [
            'animals',
            'transactions',
            'evenements',
            'lots',
            'notifications',
            'naissances',
        ];

        foreach ($businessTables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['version']);
                $table->dropColumn('version');
            });
        }
    }
};
