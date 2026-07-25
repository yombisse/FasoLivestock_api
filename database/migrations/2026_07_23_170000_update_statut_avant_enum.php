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
        // Supprimer l'ancienne contrainte check
        DB::statement("ALTER TABLE evenements DROP CONSTRAINT IF EXISTS evenements_statut_avant_check");
        
        // Modifier l'enum statut_avant pour inclure les statuts temporaires
        DB::statement("ALTER TABLE evenements ALTER COLUMN statut_avant TYPE VARCHAR(50)");
        DB::statement("ALTER TABLE evenements ADD CONSTRAINT evenements_statut_avant_check CHECK (statut_avant IN ('SAIN', 'VENDU', 'MORT', 'PERDU', 'MALADE', 'EN_TRAITEMENT') OR statut_avant IS NULL)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE evenements DROP CONSTRAINT IF EXISTS evenements_statut_avant_check");
        DB::statement("ALTER TABLE evenements ALTER COLUMN statut_avant TYPE VARCHAR(50)");
        // Nettoyer les statuts temporaires avant de restaurer la contrainte restrictive
        DB::statement("UPDATE evenements SET statut_avant = NULL WHERE statut_avant NOT IN ('SAIN', 'VENDU', 'MORT', 'PERDU')");
        DB::statement("ALTER TABLE evenements ADD CONSTRAINT evenements_statut_avant_check CHECK (statut_avant IN ('SAIN', 'VENDU', 'MORT', 'PERDU') OR statut_avant IS NULL)");
    }
};
