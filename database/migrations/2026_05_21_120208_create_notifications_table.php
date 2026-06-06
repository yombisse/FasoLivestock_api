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
            $table->foreignUuid('farm_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->foreignUuid('animal_id')
                ->nullable()
                ->constrained('animals')
                ->nullOnDelete();
            $table->string('titre')->nullable();
            $table->text('message');
            $table->timestamp('sent_at')->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indices pour performance
            $table->index('farm_id');
            $table->index('animal_id');
            $table->index('sent_at');
            $table->index('sync_status');
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
