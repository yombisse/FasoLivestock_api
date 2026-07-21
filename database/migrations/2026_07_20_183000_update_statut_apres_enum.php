<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer l'ancienne contrainte check
        DB::statement("ALTER TABLE evenements DROP CONSTRAINT IF EXISTS evenements_statut_apres_check");
        
        // Modifier l'enum statut_apres pour inclure les statuts temporaires
        DB::statement("ALTER TABLE evenements ALTER COLUMN statut_apres TYPE VARCHAR(50)");
        DB::statement("ALTER TABLE evenements ADD CONSTRAINT evenements_statut_apres_check CHECK (statut_apres IN ('SAIN', 'VENDU', 'MORT', 'PERDU', 'MALADE', 'EN_TRAITEMENT') OR statut_apres IS NULL)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE evenements DROP CONSTRAINT IF EXISTS evenements_statut_apres_check");
        DB::statement("ALTER TABLE evenements ALTER COLUMN statut_apres TYPE VARCHAR(50)");
        DB::statement("ALTER TABLE evenements ADD CONSTRAINT evenements_statut_apres_check CHECK (statut_apres IN ('SAIN', 'VENDU', 'MORT', 'PERDU') OR statut_apres IS NULL)");
    }
};
