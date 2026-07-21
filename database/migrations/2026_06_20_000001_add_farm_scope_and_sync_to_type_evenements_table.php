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
        Schema::table('type_evenements', function (Blueprint $table) {
            // Supprimer l'index unique sur nom_type pour permettre des types par ferme
            $table->dropUnique('type_evenements_nom_type_unique');

            // Ajouter farm_id pour le scope farm
            $table->string('farm_id', 20)
                ->nullable()
                ->after('id');
            $table->foreign('farm_id')->references('id')->on('farms')->nullOnDelete();

            // Remettre les champs de synchronisation
            $table->enum('sync_status', ['pending', 'synced', 'conflict'])
                ->default('synced')
                ->after('description');
            
            $table->string('last_modified_by', 20)
                ->nullable()
                ->after('sync_status');
            $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();

            // Ajouter les index
            $table->index('farm_id');
            $table->index('sync_status');
            
            // Index composite unique pour farm_id + nom_type
            $table->unique(['farm_id', 'nom_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_evenements', function (Blueprint $table) {
            // Supprimer les index et contraintes
            $table->dropUnique(['farm_id', 'nom_type']);
            $table->dropIndex(['farm_id']);
            $table->dropIndex(['sync_status']);
            $table->dropForeign(['farm_id']);
            $table->dropForeign(['last_modified_by']);

            // Supprimer les colonnes
            $table->dropColumn(['farm_id', 'sync_status', 'last_modified_by']);

            // Remettre l'index unique sur nom_type
            $table->unique('nom_type');
        });
    }
};
