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
        Schema::create('farm_user', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('farm_id', 20);
            $table->string('user_id', 20);
            $table->foreign('farm_id')->references('id')->on('farms')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->enum('role', ['owner', 'manager', 'vet', 'worker'])->default('worker');
            $table->timestamps();

            $table->unique(['farm_id', 'user_id']);
            $table->index('farm_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_user');
    }
};
