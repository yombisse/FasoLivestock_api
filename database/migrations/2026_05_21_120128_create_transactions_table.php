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
            $table->uuid('id')->primary();
            $table->enum('type_transaction', ['ENTREE', 'SORTIE', 'TRANSFERT', 'AJUSTEMENT'])->default('ENTREE');
            $table->decimal('montant', 12, 2)->unsigned();
            $table->date('date_transaction');
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignUuid('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();
            $table->foreignUuid('categorie_id')
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->boolean('synced')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Indices pour performance
            $table->index('user_id');
            $table->index('animal_id');
            $table->index('categorie_id');
            $table->index('date_transaction');
            $table->index(['user_id', 'date_transaction']);
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
