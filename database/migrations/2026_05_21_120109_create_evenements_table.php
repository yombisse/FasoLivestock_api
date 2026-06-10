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
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignUuid('type_evenement_id')
                ->constrained('type_evenements')
                ->cascadeOnDelete();
            $table->foreignUuid('animal_id')
                ->constrained('animals')
                ->cascadeOnDelete();
            $table->date('date_evenement');
            $table->string('description')->nullable();
            $table->decimal('cout', 10, 2)->nullable();

            // ─── Colonnes mouvement ───────────────────────────────────
            // Pour les transferts : ferme de destination
            $table->foreignUuid('farm_destination_id')
                ->nullable()
                ->constrained('farms')
                ->nullOnDelete();

            // Traçabilité du changement de statut de l'animal
            $table->enum('statut_avant', ['ACTIF', 'VENDU', 'MORT', 'PERDU'])
                ->nullable();
            $table->enum('statut_apres', ['ACTIF', 'VENDU', 'MORT', 'PERDU'])
                ->nullable();

            // transaction_id retiré ici — ajouté après création
            // de transactions via add_transaction_id_to_evenements_table
            // ─────────────────────────────────────────────────────────

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('farm_id');
            $table->index('type_evenement_id');
            $table->index('animal_id');
            $table->index('date_evenement');
            $table->index('sync_status');
            $table->index('farm_destination_id');
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