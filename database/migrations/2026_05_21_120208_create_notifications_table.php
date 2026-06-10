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
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')
                ->nullable()
                ->constrained('farms')
                ->nullOnDelete();
            $table->foreignUuid('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();
            $table->string('titre')->nullable();
            $table->text('message');
            $table->timestamp('sent_at')->nullable();

            // ─── Nouveaux champs ──────────────────────────────────────
            // Événement déclencheur de la notification
            $table->foreignUuid('evenement_id')
                ->nullable()
                ->constrained('evenements')
                ->nullOnDelete();

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
            $table->foreignUuid('last_modified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

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