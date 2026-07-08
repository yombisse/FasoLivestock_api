<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced')->after('owner_id');
            $table->foreignUuid('last_modified_by')->nullable()->constrained('users')->nullOnDelete()->after('sync_status');
            $table->integer('version')->default(1)->after('last_modified_by');
            
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropIndex(['sync_status']);
            $table->dropColumn(['sync_status', 'last_modified_by', 'version']);
        });
    }
};
