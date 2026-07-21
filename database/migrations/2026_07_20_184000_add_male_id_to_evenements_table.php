<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            // Ajouter male_id pour les saillies naturelles
            $table->string('male_id', 20)->nullable()->after('animal_id');
            $table->foreign('male_id')->references('id')->on('animals')->nullOnDelete();
            
            // Index pour les requêtes sur male_id
            $table->index('male_id');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropForeign(['male_id']);
            $table->dropIndex(['male_id']);
            $table->dropColumn('male_id');
        });
    }
};
