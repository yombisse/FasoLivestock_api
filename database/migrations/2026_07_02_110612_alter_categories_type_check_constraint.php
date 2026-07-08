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
        // PostgreSQL : d'abord supprimer l'ancienne contrainte
        DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_type_check");
        
        // Puis mettre à jour les données existantes
        DB::statement("UPDATE categories SET type = 'REVENU' WHERE type = 'ENTREE'");
        DB::statement("UPDATE categories SET type = 'DEPENSE' WHERE type = 'SORTIE'");
        
        // Enfin créer la nouvelle contrainte
        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_type_check CHECK (type IN ('REVENU', 'DEPENSE'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // D'abord supprimer la contrainte actuelle
        DB::statement("ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_type_check");
        
        // Remettre les données à leur état original
        DB::statement("UPDATE categories SET type = 'ENTREE' WHERE type = 'REVENU'");
        DB::statement("UPDATE categories SET type = 'SORTIE' WHERE type = 'DEPENSE'");
        
        // Puis restaurer l'ancienne contrainte
        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_type_check CHECK (type IN ('ENTREE', 'SORTIE'))");
    }
};
