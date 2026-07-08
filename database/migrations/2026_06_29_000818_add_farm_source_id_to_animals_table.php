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
        Schema::table('animals', function (Blueprint $table) {
            $table->foreignUuid('farm_source_id')->nullable()->constrained('farms')->nullOnDelete();
            $table->index('farm_source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropForeign(['farm_source_id']);
            $table->dropIndex(['farm_source_id']);
            $table->dropColumn('farm_source_id');
        });
    }
};
