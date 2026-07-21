<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Créer une séquence PostgreSQL pour générer les numéros de transaction
        DB::statement("CREATE SEQUENCE IF NOT EXISTS transaction_numero_seq START 1");

        // Réinitialiser la séquence au dernier numéro existant pour l'année courante
        $year = date('Y');
        $lastNumero = DB::table('transactions')
            ->where('numero_transaction', 'like', "TRX-{$year}-%")
            ->orderBy('numero_transaction', 'desc')
            ->value('numero_transaction');

        if ($lastNumero) {
            $sequence = (int) substr($lastNumero, -6);
            DB::statement("SELECT setval('transaction_numero_seq', {$sequence})");
        }
    }

    public function down(): void
    {
        DB::statement("DROP SEQUENCE IF EXISTS transaction_numero_seq");
    }
};
