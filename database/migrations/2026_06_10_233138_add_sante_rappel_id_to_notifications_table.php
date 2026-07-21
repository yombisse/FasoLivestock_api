<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'sante_rappel_id')) {
                $table->string('sante_rappel_id', 20)
                    ->nullable()
                    ->after('evenement_id');

                $table->foreign('sante_rappel_id')->references('id')->on('sante_rappels')->nullOnDelete();
                $table->index('sante_rappel_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'sante_rappel_id')) {
                $table->dropForeign(['sante_rappel_id']);
                $table->dropIndex(['sante_rappel_id']);
                $table->dropColumn('sante_rappel_id');
            }
        });
    }
};