<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignUuid('aliment_id')->constrained('aliments')->cascadeOnDelete();

            // Une ration peut cibler un animal OU un lot, pas les deux
            $table->foreignUuid('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();
            $table->foreignUuid('lot_id')
                ->nullable()
                ->constrained('lots')
                ->nullOnDelete();

            $table->decimal('quantite', 10, 2);
            $table->date('date_distribution');
            $table->time('heure_distribution')->nullable();
            $table->text('observation')->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('farm_id');
            $table->index('aliment_id');
            $table->index('animal_id');
            $table->index('lot_id');
            $table->index('date_distribution');
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rations');
    }
};