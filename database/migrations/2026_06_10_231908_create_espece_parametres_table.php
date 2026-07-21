<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espece_parametres', function (Blueprint $table) {
            $table->string('id', 20)->primary();

            // Une espèce a exactement un jeu de paramètres
            $table->string('espece_id', 20)->unique();
            $table->foreign('espece_id')->references('id')->on('especes')->cascadeOnDelete();

            // ─── Reproduction ─────────────────────────────────────────
            $table->integer('duree_gestation_jours')->nullable();  // bovin: 283, ovin: 150, caprin: 150
            $table->integer('age_reproduction_mois')->nullable();   // bovin: 15, ovin: 7, caprin: 6
            $table->integer('nombre_petits_typique')->default(1);   // bovin: 1, caprin: 1-3

            // ─── Santé ────────────────────────────────────────────────
            $table->integer('intervalle_vaccin_jours')->nullable(); // intervalle entre vaccins
            $table->integer('age_sevrage_jours')->nullable();       // âge de sevrage des petits

            // ─── Croissance ───────────────────────────────────────────
            $table->decimal('poids_naissance_moyen_kg', 8, 2)->nullable();
            $table->decimal('poids_adulte_moyen_kg', 8, 2)->nullable();

            $table->timestamps();

            $table->index('espece_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espece_parametres');
    }
};