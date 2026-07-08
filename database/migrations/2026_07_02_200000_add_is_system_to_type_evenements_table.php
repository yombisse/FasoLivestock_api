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
        Schema::table('type_evenements', function (Blueprint $table) {
            // Ajouter la colonne is_system pour protéger les types système
            $table->boolean('is_system')->default(false)->after('categorie');
            
            // Index pour les requêtes fréquentes
            $table->index('is_system');
        });

        // Marquer les types existants comme système par défaut
        DB::statement("UPDATE type_evenements SET is_system = true WHERE farm_id IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_evenements', function (Blueprint $table) {
            $table->dropIndex(['is_system']);
            $table->dropColumn('is_system');
        });
    }
};
