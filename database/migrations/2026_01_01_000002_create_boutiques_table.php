<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boutiques', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('nom', 150);
            $table->string('adresse', 255)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index('admin_id', 'idx_boutiques_admin');
        });

        Schema::create('activites', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100)->unique();
        });

        Schema::create('boutique_activite', function (Blueprint $table) {
            $table->foreignId('boutique_id')->constrained('boutiques')->cascadeOnDelete();
            $table->foreignId('activite_id')->constrained('activites')->restrictOnDelete();
            $table->primary(['boutique_id', 'activite_id']);
        });

        Schema::create('boutique_vendeur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained('boutiques')->cascadeOnDelete();
            $table->foreignId('vendeur_id')->constrained('users')->cascadeOnDelete();
            $table->date('date_rattachement');
            $table->boolean('actif')->default(true);
            $table->unique(['boutique_id', 'vendeur_id'], 'uniq_boutique_vendeur');
        });

        Schema::create('parametres_boutique', function (Blueprint $table) {
            $table->foreignId('boutique_id')->primary()->constrained('boutiques')->cascadeOnDelete();
            $table->time('heure_cloture')->default('23:59:00');
            $table->decimal('fond_caisse_initial', 12, 2)->default(0);
            $table->boolean('blocage_ecart_actif')->default(false);
            $table->decimal('seuil_blocage_ecart', 12, 2)->nullable();
            $table->decimal('seuil_alerte_ecart', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_boutique');
        Schema::dropIfExists('boutique_vendeur');
        Schema::dropIfExists('boutique_activite');
        Schema::dropIfExists('activites');
        Schema::dropIfExists('boutiques');
    }
};
