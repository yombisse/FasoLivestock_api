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
        // Supprimer l'ancien check constraint s'il existe
        $constraintExists = DB::select("
            SELECT conname
            FROM pg_constraint
            WHERE conrelid = 'animals'::regclass
            AND conname = 'animals_sexe_check'
        ");

        if (!empty($constraintExists)) {
            DB::statement("ALTER TABLE animals DROP CONSTRAINT animals_sexe_check");
        }

        // Créer le nouveau check constraint avec les valeurs correctes
        DB::statement("ALTER TABLE animals ADD CONSTRAINT animals_sexe_check CHECK (sexe IN ('M', 'F'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE animals DROP CONSTRAINT animals_sexe_check");
    }
};
