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
        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('farm_id', 20)->nullable();
            $table->foreign('farm_id')->references('id')->on('farms')->nullOnDelete();
            $table->string('animal_id', 20)->nullable();
            $table->foreign('animal_id')->references('id')->on('animals')->nullOnDelete();
            $table->string('titre')->nullable();
            $table->text('message');
            $table->timestamp('sent_at')->nullable();

            // ─── Nouveaux champs ──────────────────────────────────────
            // Événement déclencheur de la notification
            $table->string('evenement_id', 20)->nullable();
            $table->foreign('evenement_id')->references('id')->on('evenements')->nullOnDelete();

            // Type pour catégoriser et filtrer les alertes
            $table->enum('type', [
                'VACCINATION',
                'TRAITEMENT',
                'NAISSANCE',
                'MOUVEMENT',
                'ALERTE',
                'INFO',
            ])->default('INFO');
            // ─────────────────────────────────────────────────────────

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->string('last_modified_by', 20)->nullable();
            $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indices existants
            $table->index('farm_id');
            $table->index('animal_id');
            $table->index('sent_at');
            $table->index('sync_status');

            // ─── Nouveaux indices ─────────────────────────────────────
            $table->index('evenement_id');
            $table->index('type');
            // ─────────────────────────────────────────────────────────
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};