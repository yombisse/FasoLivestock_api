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
        Schema::create('animals', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('farm_id', 20);
            $table->foreign('farm_id')->references('id')->on('farms')->cascadeOnDelete();
            $table->string('nom')->nullable();
            $table->string('race')->nullable();
            $table->enum('sexe', ['male', 'femelle'])->nullable();
            $table->date('date_naissance')->nullable();
            $table->decimal('poids', 10, 2)->nullable();
            $table->enum('statut', ['SAIN', 'MALADE', 'EN_TRAITEMENT', 'VENDU', 'MORT', 'PERDU'])->default('SAIN');

            $table->string('espece_id', 20);
            $table->foreign('espece_id')->references('id')->on('especes');
            $table->string('lot_id', 20)->nullable();
            $table->foreign('lot_id')->references('id')->on('lots')->nullOnDelete();
            $table->string('mother_id', 20)->nullable();

            $table->string('numero_identification')->nullable();
            $table->string('photo')->nullable();
            $table->string('naissance_id', 20)->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->string('last_modified_by', 20)->nullable();
            $table->foreign('last_modified_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indices pour performance
            $table->index('farm_id');
            $table->index('espece_id');
            $table->index('lot_id');
            $table->index('mother_id');
            $table->index('statut');
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
