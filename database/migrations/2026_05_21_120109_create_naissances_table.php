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
        Schema::create('naissances', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('farm_id', 20);
            $table->foreign('farm_id')->references('id')->on('farms')->cascadeOnDelete();
            $table->string('mother_id', 20);
            $table->foreign('mother_id')->references('id')->on('animals')->cascadeOnDelete();
            $table->date('date_naissance');
            $table->integer('nombre_petits')->unsigned()->default(0);
            $table->decimal('poids_naissance', 10, 2)->nullable();
            $table->text('observation')->nullable();

            $table->string('evenement_id', 20)->nullable();
            $table->foreign('evenement_id')->references('id')->on('evenements')->nullOnDelete();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->string('last_modified_by', 20)->nullable();
            $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('farm_id');
            $table->index('mother_id');
            $table->index('evenement_id');
            $table->index('date_naissance');
            $table->index('sync_status');

            // ─── Business key pour déduplication sync naissances ─────────────────
            // Permet de détecter les doublons lors du sync offline
            // Clé métier : mere_id + date_naissance (une mère ne peut pas mettre bas deux fois le même jour)
            $table->index(['mother_id', 'date_naissance'],
                          'naissances_business_key_idx');
            // ─────────────────────────────────────────────────────────
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('naissances');
    }
};