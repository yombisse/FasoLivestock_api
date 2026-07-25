<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('farm_user', function (Blueprint $table) {
            // Renommer la colonne role en role_id_old
            $table->renameColumn('role', 'role_id_old');
        });

        // Créer la nouvelle colonne role_id (bigInteger pour correspondre à roles.id)
        Schema::table('farm_user', function (Blueprint $table) {
            $table->bigInteger('role_id')->nullable()->unsigned();
        });

        // Migrer les données: mapper les rôles statiques vers les IDs Spatie
        $roleMapping = [
            'owner' => Role::where('name', 'owner')->where('guard_name', 'api')->first()?->id,
            'manager' => Role::where('name', 'manager')->where('guard_name', 'api')->first()?->id,
            'vet' => Role::where('name', 'vet')->where('guard_name', 'api')->first()?->id,
            'worker' => Role::where('name', 'worker')->where('guard_name', 'api')->first()?->id,
        ];

        \DB::table('farm_user')->get()->each(function ($farmUser) use ($roleMapping) {
            if (isset($roleMapping[$farmUser->role_id_old]) && $roleMapping[$farmUser->role_id_old]) {
                \DB::table('farm_user')
                    ->where('id', $farmUser->id)
                    ->update(['role_id' => $roleMapping[$farmUser->role_id_old]]);
            } else {
                // Supprimer les enregistrements avec des rôles inconnus
                \DB::table('farm_user')
                    ->where('id', $farmUser->id)
                    ->delete();
            }
        });

        // Supprimer l'ancienne colonne
        Schema::table('farm_user', function (Blueprint $table) {
            $table->dropColumn('role_id_old');
        });

        // Rendre la colonne non nullable et ajouter la foreign key
        Schema::table('farm_user', function (Blueprint $table) {
            $table->bigInteger('role_id')->nullable(false)->unsigned()->change();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farm_user', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->renameColumn('role_id', 'role_id_old');
        });

        // Recréer la colonne role
        Schema::table('farm_user', function (Blueprint $table) {
            $table->string('role')->nullable();
        });

        // Migrer les données: mapper les IDs vers les noms de rôles
        $roleMapping = [];
        Role::whereIn('name', ['owner', 'manager', 'vet', 'worker'])
            ->where('guard_name', 'api')
            ->get()
            ->each(function ($role) use (&$roleMapping) {
                $roleMapping[$role->id] = $role->name;
            });

        \DB::table('farm_user')->get()->each(function ($farmUser) use ($roleMapping) {
            if (isset($roleMapping[$farmUser->role_id_old])) {
                \DB::table('farm_user')
                    ->where('id', $farmUser->id)
                    ->update(['role' => $roleMapping[$farmUser->role_id_old]]);
            } else {
                // Supprimer les enregistrements avec des rôles inconnus
                \DB::table('farm_user')
                    ->where('id', $farmUser->id)
                    ->delete();
            }
        });

        // Supprimer l'ancienne colonne
        Schema::table('farm_user', function (Blueprint $table) {
            $table->dropColumn('role_id_old');
        });

        // Rendre la colonne non nullable
        Schema::table('farm_user', function (Blueprint $table) {
            $table->string('role')->nullable(false)->change();
        });
    }
};
