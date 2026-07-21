<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            // Ajouter le champ statut pour les événements reproductifs
            $table->string('statut')->nullable()->after('description');
            
            // Ajouter le champ date_fin pour les événements reproductifs
            $table->date('date_fin')->nullable()->after('statut');
            
            // Index composite pour les requêtes de validation reproductives
            $table->index(['animal_id', 'type_evenement_id', 'statut']);
        });

        // Ajouter la contrainte check pour le statut (PostgreSQL)
        DB::statement("ALTER TABLE evenements ADD CONSTRAINT evenements_statut_check CHECK (statut IN ('EN_COURS', 'TERMINE', 'ANNULE') OR statut IS NULL)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte check
        DB::statement("ALTER TABLE evenements DROP CONSTRAINT IF EXISTS evenements_statut_check");

        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn(['statut', 'date_fin']);
        });
    }
};
