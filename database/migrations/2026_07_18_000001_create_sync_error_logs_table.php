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
        Schema::create('sync_error_logs', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('module'); // evenements, transactions, naissances, notifications, etc.
            $table->string('record_id', 20)->nullable();
            $table->string('farm_id', 20)->nullable();
            $table->foreign('farm_id')->references('id')->on('farms')->nullOnDelete();
            $table->text('reason'); // Detailed business reason for rejection
            $table->json('payload_snapshot')->nullable(); // Original payload that caused the error
            $table->timestamp('occurred_at')->useCurrent();
            
            $table->index('module');
            $table->index('farm_id');
            $table->index('occurred_at');
            $table->index(['module', 'farm_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_error_logs');
    }
};
