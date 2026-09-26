<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nom', 150);
            $table->string('telephone', 30)->unique();
            $table->string('email', 150)->nullable()->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'vendeur']);
            $table->boolean('actif')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index('role', 'idx_users_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
