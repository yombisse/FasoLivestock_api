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
            $table->string('nom')->nullable();
            $table->string('race')->nullable();
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->date('date_naissance')->nullable();
            $table->decimal('poids', 10, 2)->nullable();
            $table->enum('statut', ['ACTIF', 'VENDU', 'MORT', 'PERDU'])->default('ACTIF');

            $table->foreignUuid('espece_id')->constrained('especes');
            $table->foreignUuid('lot_id')->nullable()->constrained('lots');
            $table->uuid('mother_id')->nullable();
            
            $table->boolean('synced')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indices pour performance
            $table->index('espece_id');
            $table->index('lot_id');
            $table->index('mother_id');
            $table->index('statut');
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
