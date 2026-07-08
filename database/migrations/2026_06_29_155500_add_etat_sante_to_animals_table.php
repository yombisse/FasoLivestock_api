<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter la colonne etat_sante avec un check constraint pour simuler l'enum
        DB::statement("ALTER TABLE animals ADD COLUMN etat_sante VARCHAR(20)");
        DB::statement("ALTER TABLE animals ADD CONSTRAINT animals_etat_sante_check CHECK (etat_sante IN ('SAIN', 'MALADE', 'QUARANTAINE'))");
        DB::statement("ALTER TABLE animals ALTER COLUMN etat_sante SET DEFAULT 'SAIN'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE animals DROP CONSTRAINT animals_etat_sante_check");
        DB::statement("ALTER TABLE animals DROP COLUMN etat_sante");
    }
};
