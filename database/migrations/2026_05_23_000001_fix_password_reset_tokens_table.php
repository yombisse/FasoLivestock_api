<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // Ajouter user_id avec clé étrangère
            if (!Schema::hasColumn('password_reset_tokens', 'user_id')) {
                $table->uuid('user_id')->after('id')->constrained('users')->onDelete('cascade');
            }

            // Ajouter expires_at
            if (!Schema::hasColumn('password_reset_tokens', 'expires_at')) {
                $table->timestamp('expires_at')->after('token')->nullable();
            }

            // Ajouter used et used_at
            if (!Schema::hasColumn('password_reset_tokens', 'used')) {
                $table->boolean('used')->default(false)->after('expires_at');
            }

            if (!Schema::hasColumn('password_reset_tokens', 'used_at')) {
                $table->timestamp('used_at')->after('used')->nullable();
            }

            // Ajouter timestamps si absents
            if (!Schema::hasColumn('password_reset_tokens', 'created_at')) {
                $table->timestamps();
            }

            // Ajouter soft deletes
            if (!Schema::hasColumn('password_reset_tokens', 'deleted_at')) {
                $table->softDeletes();
            }

            // Supprimer les anciennes colonnes
            if (Schema::hasColumn('password_reset_tokens', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('password_reset_tokens', 'telephone')) {
                $table->dropColumn('telephone');
            }
            if (Schema::hasColumn('password_reset_tokens', 'type')) {
                $table->dropColumn('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropForeignKey(['user_id']);
            $table->dropColumn(['user_id', 'expires_at', 'used', 'used_at', 'deleted_at']);
        });
    }
};
