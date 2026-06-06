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
        Schema::create('two_factor_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();

            // email | phone
            $table->string('channel', 20)->index();

            // identifier used to send code (email or phone)
            $table->string('identifier', 255)->index();

            // code is stored hashed
            $table->string('code_hash', 255);

            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();

            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_verifications');
    }
};

