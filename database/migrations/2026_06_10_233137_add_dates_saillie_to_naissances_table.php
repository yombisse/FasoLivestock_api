<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('naissances', function (Blueprint $table) {
            $table->date('date_saillie')
                ->nullable()
                ->after('date_naissance');

            $table->date('date_mise_bas_prevue')
                ->nullable()
                ->after('date_saillie');

            $table->index('date_saillie');
            $table->index('date_mise_bas_prevue');
        });
    }

    public function down(): void
    {
        Schema::table('naissances', function (Blueprint $table) {
            $table->dropIndex(['date_saillie']);
            $table->dropIndex(['date_mise_bas_prevue']);
            $table->dropColumn(['date_saillie', 'date_mise_bas_prevue']);
        });
    }
};