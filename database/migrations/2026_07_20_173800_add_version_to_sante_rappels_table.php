<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sante_rappels', function (Blueprint $table) {
            $table->integer('version')->default(1)->after('last_modified_by');
        });
    }

    public function down(): void
    {
        Schema::table('sante_rappels', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
