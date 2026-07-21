<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            if (!Schema::hasColumn('evenements', 'transaction_id')) {
                $table->string('transaction_id', 20)
                    ->nullable()
                    ->after('statut_apres');

                $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
                $table->index('transaction_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            if (Schema::hasColumn('evenements', 'transaction_id')) {
                $table->dropForeign(['transaction_id']);
                $table->dropIndex(['transaction_id']);
                $table->dropColumn('transaction_id');
            }
        });
    }
};