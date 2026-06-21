<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * farms.owner_id était en cascadeOnDelete() : supprimer un utilisateur
     * supprimait en cascade toutes ses fermes, et donc tout ce qui en
     * dépend (animaux, événements, naissances, transactions — tous en
     * cascadeOnDelete sur farm_id). On remplace par restrictOnDelete()
     * pour bloquer la suppression tant qu'une ferme dépend encore de ce
     * propriétaire : il faut transférer la propriété avant de supprimer
     * le compte.
     */
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->foreign('owner_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->foreign('owner_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }
};
