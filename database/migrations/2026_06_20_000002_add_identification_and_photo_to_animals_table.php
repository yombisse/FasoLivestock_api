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
            $table->string('numero_identification')->nullable()->after('statut');
            $table->string('photo')->nullable()->after('numero_identification');

            // Index composite pour garantir l'unicité au sein d'une même ferme
            $table->unique(['farm_id', 'numero_identification']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'numero_identification']);
            $table->dropColumn(['numero_identification', 'photo']);
        });
    }
};
