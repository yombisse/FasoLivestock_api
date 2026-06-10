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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->enum('type_transaction', ['ENTREE', 'SORTIE', 'TRANSFERT', 'AJUSTEMENT'])->default('ENTREE');
            $table->decimal('montant', 12, 2)->unsigned();
            $table->date('date_transaction');
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignUuid('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();
            $table->foreignUuid('categorie_id')
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->string('description')->nullable();

            // ─── Nouveau champ ────────────────────────────────────────
            // Lien vers l'événement déclencheur (vente, achat...)
            // nullable car toutes les transactions ne viennent pas
            // d'un événement (ex: achat d'aliments, frais vétérinaires)
            $table->foreignUuid('evenement_id')
                ->nullable()
                ->constrained('evenements')
                ->nullOnDelete()
                ->after('description');
            // ─────────────────────────────────────────────────────────

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indices pour performance
            $table->index('farm_id');
            $table->index('user_id');
            $table->index('animal_id');
            $table->index('categorie_id');
            $table->index('date_transaction');
            $table->index(['user_id', 'date_transaction']);
            $table->index('sync_status');

            // ─── Nouvel indice ────────────────────────────────────────
            $table->index('evenement_id');
            // ─────────────────────────────────────────────────────────
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};