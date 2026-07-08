<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL : supprimer la contrainte check existante
        DB::statement("ALTER TABLE animals DROP CONSTRAINT IF EXISTS animals_sexe_check");
        
        // Mettre à jour les données existantes
        DB::statement("UPDATE animals SET sexe = 'male' WHERE sexe = 'M'");
        DB::statement("UPDATE animals SET sexe = 'femelle' WHERE sexe = 'F'");
        
        // Recréer la contrainte check avec les nouvelles valeurs
        DB::statement("ALTER TABLE animals ADD CONSTRAINT animals_sexe_check CHECK (sexe IN ('male', 'femelle'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte check
        DB::statement("ALTER TABLE animals DROP CONSTRAINT IF EXISTS animals_sexe_check");
        
        // Mettre à jour les données
        DB::statement("UPDATE animals SET sexe = 'M' WHERE sexe = 'male'");
        DB::statement("UPDATE animals SET sexe = 'F' WHERE sexe = 'femelle'");
        
        // Recréer la contrainte check avec les anciennes valeurs
        DB::statement("ALTER TABLE animals ADD CONSTRAINT animals_sexe_check CHECK (sexe IN ('M', 'F'))");
    }
};
