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
        Schema::table('evenements', function (Blueprint $table) {
            // Ajouter la colonne JSON pour les métadonnées spécifiques par type d'événement
            // Permet de stocker des champs spécifiques pour:
            // - Santé: nom_vaccin, veterinaire, dosage, nom_medicament, duree, etc.
            // - Reproduction: male_id, type_saillie, duree_gestation, nombre_petits, etc.
            $table->json('metadonnees')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn('metadonnees');
        });
    }
};
