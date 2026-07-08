<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL : utiliser DB::statement pour modifier l'enum
        DB::statement("ALTER TABLE categories ALTER COLUMN type TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_type_check CHECK (type IN ('ENTREE', 'SORTIE'))");
        DB::statement("ALTER TABLE categories ALTER COLUMN type DROP NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('type')->nullable()->change();
        });
    }
};
