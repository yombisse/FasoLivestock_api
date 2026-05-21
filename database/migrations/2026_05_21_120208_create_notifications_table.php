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
            $table->foreignUuid('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();
            $table->string('titre')->nullable();
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->boolean('synced')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Indices pour performance
            $table->index('animal_id');
            $table->index('sent_at');
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
