<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignUuid('sante_rappel_id')
                ->nullable()
                ->constrained('sante_rappels')
                ->nullOnDelete()
                ->after('evenement_id');

            $table->index('sante_rappel_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['sante_rappel_id']);
            $table->dropIndex(['sante_rappel_id']);
            $table->dropColumn('sante_rappel_id');
        });
    }
};