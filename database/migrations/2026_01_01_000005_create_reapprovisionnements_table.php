<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autorisations_reapprovisionnement', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('boutique_id')->constrained('boutiques')->restrictOnDelete();
            $table->foreignId('vendeur_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('accordee_par');
            $table->foreign('accordee_par')->references('id')->on('users')->restrictOnDelete();
            $table->enum('type', ['ponctuelle', 'permanente']);
            $table->enum('statut', ['active', 'utilisee', 'expiree', 'revoquee'])->default('active');
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable(); // NULL = permanente
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['vendeur_id', 'statut'], 'idx_autor_vendeur_statut');
            $table->index(['boutique_id', 'statut'], 'idx_autor_boutique_statut');
        });

        Schema::create('reapprovisionnements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('session_uuid')->nullable(); // regroupe plusieurs lignes d'une même sortie
            $table->foreignId('autorisation_id')
                  ->constrained('autorisations_reapprovisionnement')->restrictOnDelete();
            $table->foreignId('boutique_id')->constrained('boutiques')->restrictOnDelete();
            $table->foreignId('produit_id')->constrained('produits')->restrictOnDelete();
            $table->foreignId('vendeur_id')->constrained('users')->restrictOnDelete();
            $table->decimal('quantite', 12, 3);
            $table->string('unite', 30);
            $table->decimal('montant_depense', 12, 2);
            $table->string('fournisseur', 150)->nullable();
            $table->date('date_reappro');
            $table->time('heure_reappro');
            $table->string('device_id', 100)->nullable();
            $table->dateTime('created_at_local');
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['boutique_id', 'date_reappro'], 'idx_reappro_boutique_date');
            $table->index(['vendeur_id', 'date_reappro'], 'idx_reappro_vendeur_date');
            $table->index('autorisation_id', 'idx_reappro_autorisation');
            $table->index('session_uuid', 'idx_reappro_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reapprovisionnements');
        Schema::dropIfExists('autorisations_reapprovisionnement');
    }
};
