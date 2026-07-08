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
        // Supprimer l'ancienne contrainte check
        DB::statement('ALTER TABLE farms DROP CONSTRAINT IF EXISTS farms_type_elevage_check');

        // Créer une nouvelle contrainte check avec la valeur "mixte" incluse
        DB::statement("ALTER TABLE farms ADD CONSTRAINT farms_type_elevage_check CHECK (type_elevage IN ('bovin', 'ovin', 'caprin', 'porcin', 'volaille', 'cunicole', 'mixte', 'autre'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir à l'ancienne contrainte sans "mixte"
        DB::statement('ALTER TABLE farms DROP CONSTRAINT IF EXISTS farms_type_elevage_check');
        DB::statement("ALTER TABLE farms ADD CONSTRAINT farms_type_elevage_check CHECK (type_elevage IN ('bovin', 'ovin', 'caprin', 'porcin', 'volaille', 'cunicole', 'autre'))");
    }
};
