<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aliments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->string('nom');
            $table->enum('unite', ['KG', 'LITRE', 'SAC', 'BOTTE'])->default('KG');
            $table->decimal('prix_unitaire', 10, 2)->nullable();
            $table->decimal('stock_actuel', 10, 2)->default(0);
            $table->text('description')->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('farm_id');
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aliments');
    }
};