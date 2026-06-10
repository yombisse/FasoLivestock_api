<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->foreignUuid('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete()
                ->after('statut_apres');

            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropIndex(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }
};