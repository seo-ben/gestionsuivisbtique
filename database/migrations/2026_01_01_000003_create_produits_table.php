<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('boutique_id')->constrained('boutiques')->cascadeOnDelete();
            $table->string('nom', 150);
            $table->decimal('prix_actuel', 12, 2);
            $table->string('unite_reference', 30)->nullable();
            $table->boolean('vendeur_peut_modifier_prix')->default(false);
            $table->decimal('stock_initial', 12, 3)->default(0);
            $table->decimal('stock_actuel', 12, 3)->default(0);
            $table->decimal('seuil_alerte_stock', 12, 3)->nullable();
            $table->boolean('actif')->default(true);
            $table->unsignedInteger('version')->default(0); // verrou optimiste
            $table->timestamps();

            $table->index('boutique_id', 'idx_produits_boutique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
