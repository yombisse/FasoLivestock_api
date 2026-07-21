<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Désactiver le mode transactionnel pour cette migration
        // car PostgreSQL a des restrictions sur la modification d'enums dans les transactions
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // 1. Sauvegarder les données existantes
        $statutAvantData = DB::table('evenements')->select('id', 'statut_avant')->get();
        $statutApresData = DB::table('evenements')->select('id', 'statut_apres')->get();

        // 2. Supprimer les colonnes existantes
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn('statut_avant');
            $table->dropColumn('statut_apres');
        });

        // 3. Recréer les colonnes avec les nouveaux enums
        Schema::table('evenements', function (Blueprint $table) {
            $table->enum('statut_avant', ['SAIN', 'VENDU', 'MORT', 'PERDU'])->nullable();
            $table->enum('statut_apres', ['SAIN', 'VENDU', 'MORT', 'PERDU'])->nullable();
        });

        // 4. Restaurer les données en convertissant ACTIF -> SAIN
        foreach ($statutAvantData as $row) {
            $newValue = $row->statut_avant === 'ACTIF' ? 'SAIN' : $row->statut_avant;
            DB::table('evenements')->where('id', $row->id)->update(['statut_avant' => $newValue]);
        }

        foreach ($statutApresData as $row) {
            $newValue = $row->statut_apres === 'ACTIF' ? 'SAIN' : $row->statut_apres;
            DB::table('evenements')->where('id', $row->id)->update(['statut_apres' => $newValue]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Désactiver le mode transactionnel pour cette migration
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // 1. Sauvegarder les données existantes
        $statutAvantData = DB::table('evenements')->select('id', 'statut_avant')->get();
        $statutApresData = DB::table('evenements')->select('id', 'statut_apres')->get();

        // 2. Supprimer les colonnes existantes
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn('statut_avant');
            $table->dropColumn('statut_apres');
        });

        // 3. Recréer les colonnes avec les anciens enums
        Schema::table('evenements', function (Blueprint $table) {
            $table->enum('statut_avant', ['ACTIF', 'VENDU', 'MORT', 'PERDU'])->nullable();
            $table->enum('statut_apres', ['ACTIF', 'VENDU', 'MORT', 'PERDU'])->nullable();
        });

        // 4. Restaurer les données en convertissant SAIN -> ACTIF
        foreach ($statutAvantData as $row) {
            $newValue = $row->statut_avant === 'SAIN' ? 'ACTIF' : $row->statut_avant;
            DB::table('evenements')->where('id', $row->id)->update(['statut_avant' => $newValue]);
        }

        foreach ($statutApresData as $row) {
            $newValue = $row->statut_apres === 'SAIN' ? 'ACTIF' : $row->statut_apres;
            DB::table('evenements')->where('id', $row->id)->update(['statut_apres' => $newValue]);
        }
    }
};
