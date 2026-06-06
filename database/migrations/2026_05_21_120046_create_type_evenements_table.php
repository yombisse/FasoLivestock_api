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
        Schema::create('type_evenements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom_type')->unique();
            $table->string('description')->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_evenements');
    }
};
