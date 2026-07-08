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
        // PostgreSQL : modifier l'enum pour ajouter AUTRE
        DB::statement("ALTER TABLE evenements DROP CONSTRAINT IF EXISTS evenements_categorie_check");
        DB::statement("ALTER TABLE evenements ADD CONSTRAINT evenements_categorie_check CHECK (categorie IN ('MOUVEMENT', 'REPRODUCTION', 'SANITAIRE', 'AUTRE') OR categorie IS NULL)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PostgreSQL : retirer AUTRE de l'enum
        DB::statement("ALTER TABLE evenements DROP CONSTRAINT IF EXISTS evenements_categorie_check");
        DB::statement("ALTER TABLE evenements ADD CONSTRAINT evenements_categorie_check CHECK (categorie IN ('MOUVEMENT', 'REPRODUCTION', 'SANITAIRE') OR categorie IS NULL)");
    }
};
