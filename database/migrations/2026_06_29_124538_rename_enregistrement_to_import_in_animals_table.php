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
        // Pour PostgreSQL: recréer la colonne avec le nouvel enum
        // 1. Ajouter une nouvelle colonne temporaire avec le nouvel enum
        DB::statement("ALTER TABLE animals ADD COLUMN origine_new VARCHAR(20)");
        
        // 2. Copier les données existantes en remplaçant 'enregistrement' par 'import'
        DB::statement("UPDATE animals SET origine_new = CASE WHEN origine = 'enregistrement' THEN 'import' ELSE origine END");
        
        // 3. Supprimer l'ancienne colonne
        DB::statement("ALTER TABLE animals DROP COLUMN origine");
        
        // 4. Renommer la nouvelle colonne
        DB::statement("ALTER TABLE animals RENAME COLUMN origine_new TO origine");
        
        // 5. Ajouter le constraint CHECK pour simuler l'enum
        DB::statement("ALTER TABLE animals ADD CONSTRAINT animals_origine_check CHECK (origine IN ('import', 'achat', 'naissance'))");
        
        // 6. Mettre la valeur par défaut
        DB::statement("ALTER TABLE animals ALTER COLUMN origine SET DEFAULT 'import'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: revenir à l'ancien enum
        // 1. Supprimer le constraint CHECK
        DB::statement("ALTER TABLE animals DROP CONSTRAINT animals_origine_check");
        
        // 2. Ajouter une colonne temporaire
        DB::statement("ALTER TABLE animals ADD COLUMN origine_new VARCHAR(20)");
        
        // 3. Copier les données en remplaçant 'import' par 'enregistrement'
        DB::statement("UPDATE animals SET origine_new = CASE WHEN origine = 'import' THEN 'enregistrement' ELSE origine END");
        
        // 4. Supprimer l'ancienne colonne
        DB::statement("ALTER TABLE animals DROP COLUMN origine");
        
        // 5. Renommer
        DB::statement("ALTER TABLE animals RENAME COLUMN origine_new TO origine");
        
        // 6. Ajouter le constraint CHECK original
        DB::statement("ALTER TABLE animals ADD CONSTRAINT animals_origine_check CHECK (origine IN ('enregistrement', 'achat', 'naissance'))");
        
        // 7. Valeur par défaut
        DB::statement("ALTER TABLE animals ALTER COLUMN origine SET DEFAULT 'enregistrement'");
    }
};
