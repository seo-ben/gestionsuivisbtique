<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clotures_caisse', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('boutique_id')->constrained('boutiques')->restrictOnDelete();
            $table->date('date_cloture');
            $table->decimal('fond_caisse_initial', 14, 2)->default(0);
            $table->decimal('total_ventes', 14, 2)->default(0);
            $table->decimal('total_sorties', 14, 2)->default(0);
            $table->decimal('solde_theorique', 14, 2); // fond + ventes - sorties
            $table->decimal('solde_reel', 14, 2)->nullable();
            $table->decimal('ecart', 14, 2)->nullable();
            $table->enum('statut', ['normal', 'ecart_signale', 'bloquee'])->default('normal');
            $table->dateTime('heure_cloture_effective');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->unique(['boutique_id', 'date_cloture'], 'uniq_cloture_boutique_date');
            $table->index('statut', 'idx_clotures_statut');
        });

        Schema::create('paiements_boutiquiers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendeur_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('initie_par');
            $table->foreign('initie_par')->references('id')->on('users')->restrictOnDelete();
            $table->decimal('montant', 12, 2);
            $table->enum('type', ['salaire', 'prime', 'remboursement', 'autre']);
            $table->date('date_paiement');
            $table->enum('statut', ['en_attente', 'confirme'])->default('en_attente');
            $table->dateTime('date_confirmation')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index('vendeur_id', 'idx_paiements_vendeur');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_boutiquiers');
        Schema::dropIfExists('clotures_caisse');
    }
};
