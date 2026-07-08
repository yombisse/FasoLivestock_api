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
        Schema::table('animals', function (Blueprint $table) {
            // Rendre nom nullable
            $table->string('nom')->nullable()->change();
        });
        
        // Rendre numero_identification required (not null)
        // D'abord, mettre à jour les enregistrements existants sans numéro
        // Utiliser LEFT() au lieu de SUBSTRING pour les UUIDs PostgreSQL
        DB::statement("UPDATE animals SET numero_identification = CONCAT('ANI-', LEFT(id::text, 8)) WHERE numero_identification IS NULL OR numero_identification = ''");
        
        Schema::table('animals', function (Blueprint $table) {
            // Puis modifier la colonne
            $table->string('numero_identification')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            // Revenir à l'état original
            $table->string('nom')->nullable(false)->change();
            $table->string('numero_identification')->nullable()->change();
        });
    }
};
