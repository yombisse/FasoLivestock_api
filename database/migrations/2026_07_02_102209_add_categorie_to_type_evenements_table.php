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
        Schema::table('type_evenements', function (Blueprint $table) {
            $table->enum('categorie', ['MOUVEMENT', 'REPRODUCTION', 'SANITAIRE'])->default('SANITAIRE')->after('description');
            $table->index('categorie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_evenements', function (Blueprint $table) {
            $table->dropIndex(['categorie']);
            $table->dropColumn('categorie');
        });
    }
};
