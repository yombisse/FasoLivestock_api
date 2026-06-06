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
        // Remove sync_status and last_modified_by from reference tables
        // These tables should remain global and not be treated as offline-first
        $referenceTables = [
            'especes',
            'categories',
            'type_evenements',
        ];

        foreach ($referenceTables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['sync_status']);
                $table->dropForeign(['last_modified_by']);
                $table->dropColumn(['sync_status', 'last_modified_by']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add sync_status and last_modified_by to reference tables
        $referenceTables = [
            'especes',
            'categories',
            'type_evenements',
        ];

        foreach ($referenceTables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced')->after('description');
                $table->foreignUuid('last_modified_by')->nullable()->constrained('users')->nullOnDelete()->after('sync_status');
                $table->index('sync_status');
            });
        }
    }
};
