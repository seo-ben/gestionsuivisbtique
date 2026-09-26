<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('boutique_id')->constrained('boutiques')->restrictOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('vendeur_id')->constrained('users')->restrictOnDelete();
            $table->decimal('quantite', 12, 3);
            $table->string('unite', 30);
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('montant_total', 12, 2);
            $table->date('date_vente');
            $table->time('heure_vente');
            $table->string('device_id', 100)->nullable();
            $table->dateTime('created_at_local'); // horodatage terrain
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['boutique_id', 'date_vente'], 'idx_ventes_boutique_date');
            $table->index(['vendeur_id', 'date_vente'], 'idx_ventes_vendeur_date');
            $table->index('produit_id', 'idx_ventes_produit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
