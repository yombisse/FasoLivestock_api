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
            $table->string('id', 20)->primary();
            $table->string('farm_id', 20);
            $table->foreign('farm_id')->references('id')->on('farms')->cascadeOnDelete();
            $table->enum('type_transaction', ['ENTREE', 'SORTIE', 'TRANSFERT', 'AJUSTEMENT'])->default('ENTREE');
            $table->decimal('montant', 12, 2)->unsigned();
            $table->date('date_transaction');
            $table->string('user_id', 20);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('animal_id', 20)->nullable();
            $table->foreign('animal_id')->references('id')->on('animals')->nullOnDelete();
            $table->string('categorie_id', 20);
            $table->foreign('categorie_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->string('description')->nullable();

            // ─── Nouveau champ ────────────────────────────────────────
            // Lien vers l'événement déclencheur (vente, achat...)
            // nullable car toutes les transactions ne viennent pas
            // d'un événement (ex: achat d'aliments, frais vétérinaires)
            $table->string('evenement_id', 20)->nullable()->after('description');
            $table->foreign('evenement_id')->references('id')->on('evenements')->nullOnDelete();
            // ─────────────────────────────────────────────────────────

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->string('last_modified_by', 20)->nullable();
            $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();

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

            // ─── Business key pour déduplication sync ─────────────────
            // Permet de détecter les doublons lors du sync offline
            $table->index(['animal_id', 'evenement_id', 'date_transaction', 'type_transaction'], 
                          'transactions_business_key_idx');
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