<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sante_rappels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignUuid('animal_id')->constrained('animals')->cascadeOnDelete();
            $table->enum('type_rappel', [
                'VACCINATION',
                'TRAITEMENT',
                'CONTROLE',
            ]);
            $table->date('date_prevue');
            $table->date('date_realisee')->nullable();
            $table->enum('statut', [
                'EN_ATTENTE',
                'REALISE',
                'EN_RETARD',
            ])->default('EN_ATTENTE');
            $table->text('note')->nullable();

            // Lien vers l'événement quand le rappel est réalisé
            $table->foreignUuid('evenement_id')
                ->nullable()
                ->constrained('evenements')
                ->nullOnDelete();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('farm_id');
            $table->index('animal_id');
            $table->index('date_prevue');
            $table->index('statut');
            $table->index('type_rappel');
            $table->index('evenement_id');
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sante_rappels');
    }
};