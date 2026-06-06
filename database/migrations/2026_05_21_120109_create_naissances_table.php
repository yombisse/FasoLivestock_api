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
        Schema::create('naissances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignUuid('mother_id')
                ->constrained('animals')
                ->cascadeOnDelete();
            $table->date('date_naissance');
            $table->integer('nombre_petits')->unsigned()->default(0);
            $table->decimal('poids_naissance', 10, 2)->nullable();
            $table->text('observation')->nullable();

            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->foreignUuid('last_modified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('farm_id');
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('naissances');
    }
};
