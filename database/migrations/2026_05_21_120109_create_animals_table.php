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
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->string('nom')->nullable();
            $table->string('race')->nullable();
            $table->enum('sexe', ['male', 'femelle'])->nullable();
            $table->date('date_naissance')->nullable();
            $table->decimal('poids', 10, 2)->nullable();
            $table->enum('statut', ['ACTIF', 'VENDU', 'MORT', 'PERDU'])->default('ACTIF');

            $table->foreignUuid('espece_id')->constrained('especes');
            $table->foreignUuid('lot_id')->nullable()->constrained('lots');
            $table->uuid('mother_id')->nullable();

            $table->string('numero_identification')->nullable();
            $table->string('photo')->nullable();
            $table->uuid('naissance_id')->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')->nullable()->constrained('users')->nullOnDelete();

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
