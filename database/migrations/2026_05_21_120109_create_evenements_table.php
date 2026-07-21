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
        Schema::create('evenements', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('farm_id', 20);
            $table->foreign('farm_id')->references('id')->on('farms')->cascadeOnDelete();
            $table->string('type_evenement_id', 20);
            $table->foreign('type_evenement_id')->references('id')->on('type_evenements')->cascadeOnDelete();
            $table->string('animal_id', 20);
            $table->foreign('animal_id')->references('id')->on('animals')->cascadeOnDelete();
            $table->date('date_evenement');
            $table->string('description')->nullable();
            $table->decimal('cout', 10, 2)->nullable();

            // ─── Colonnes mouvement ───────────────────────────────────
            // Pour les transferts : ferme de destination
            $table->string('farm_destination_id', 20)->nullable();
            $table->foreign('farm_destination_id')->references('id')->on('farms')->nullOnDelete();

            // Traçabilité du changement de statut de l'animal
            $table->enum('statut_avant', ['SAIN', 'VENDU', 'MORT', 'PERDU'])
                ->nullable();
            $table->enum('statut_apres', ['SAIN', 'VENDU', 'MORT', 'PERDU'])
                ->nullable();

            // transaction_id retiré ici — ajouté après création
            // de transactions via add_transaction_id_to_evenements_table
            // ─────────────────────────────────────────────────────────

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->string('last_modified_by', 20)->nullable();
            $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('farm_id');
            $table->index('type_evenement_id');
            $table->index('animal_id');
            $table->index('date_evenement');
            $table->index('sync_status');
            $table->index('farm_destination_id');

            // ─── Business key pour déduplication sync événements critiques ─────────────────
            // Permet de détecter les doublons lors du sync offline pour saillie, gestation, décès
            // Clé métier : animal_id + type_evenement_id + date_evenement
            $table->index(['animal_id', 'type_evenement_id', 'date_evenement'],
                          'evenements_business_key_idx');
            // ─────────────────────────────────────────────────────────
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};