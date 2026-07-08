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
            if (!Schema::hasColumn('animals', 'naissance_id')) {
                $table->foreignUuid('naissance_id')
                    ->nullable()
                    ->constrained('naissances')
                    ->nullOnDelete()
                    ->after('mother_id');

                $table->index('naissance_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            if (Schema::hasColumn('animals', 'naissance_id')) {
                $table->dropForeign(['naissance_id']);
                $table->dropIndex(['naissance_id']);
                $table->dropColumn('naissance_id');
            }
        });
    }
};
