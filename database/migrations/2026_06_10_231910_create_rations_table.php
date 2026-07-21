<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rations', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('farm_id', 20);
            $table->foreign('farm_id')->references('id')->on('farms')->cascadeOnDelete();
            $table->string('aliment_id', 20);
            $table->foreign('aliment_id')->references('id')->on('aliments')->cascadeOnDelete();

            // Une ration peut cibler un animal OU un lot, pas les deux
            $table->string('animal_id', 20)->nullable();
            $table->foreign('animal_id')->references('id')->on('animals')->nullOnDelete();
            $table->string('lot_id', 20)->nullable();
            $table->foreign('lot_id')->references('id')->on('lots')->nullOnDelete();

            $table->decimal('quantite', 10, 2);
            $table->date('date_distribution');
            $table->time('heure_distribution')->nullable();
            $table->text('observation')->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->string('last_modified_by', 20)->nullable();
            $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();

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