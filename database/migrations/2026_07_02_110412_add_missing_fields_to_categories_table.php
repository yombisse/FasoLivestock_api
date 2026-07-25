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
        Schema::table('categories', function (Blueprint $table) {
            // Vérifier si sync_status existe déjà (de la migration originale)
            if (!Schema::hasColumn('categories', 'sync_status')) {
                $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced')->after('type');
                $table->string('last_modified_by', 20)->nullable()->after('sync_status');
                $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();
                $table->index('sync_status');
            }
            
            $table->text('description')->nullable()->after('type');
            $table->integer('version')->default(1)->after('last_modified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['description', 'version']);
            
            // Ne pas supprimer sync_status et last_modified_by car ils existent dans la migration originale
        });
    }
};
