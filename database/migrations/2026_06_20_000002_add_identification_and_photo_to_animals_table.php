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
            if (!Schema::hasColumn('animals', 'numero_identification')) {
                $table->string('numero_identification')->nullable()->after('statut');
            }
            if (!Schema::hasColumn('animals', 'photo')) {
                $table->string('photo')->nullable()->after('numero_identification');
            }

            // Index composite pour garantir l'unicité au sein d'une même ferme
            if (!Schema::hasIndex('animals', ['farm_id', 'numero_identification'])) {
                $table->unique(['farm_id', 'numero_identification']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasIndex('animals', ['farm_id', 'numero_identification'])) {
                $table->dropUnique(['farm_id', 'numero_identification']);
            }
            if (Schema::hasColumn('animals', 'numero_identification') || Schema::hasColumn('animals', 'photo')) {
                $table->dropColumn(['numero_identification', 'photo']);
            }
        });
    }
};
