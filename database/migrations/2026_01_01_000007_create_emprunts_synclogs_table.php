<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprunts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('organisme_preteur', 150);
            $table->decimal('capital_emprunte', 14, 2);
            $table->decimal('capital_restant_du', 14, 2); // maintenu par l'app à chaque versement
            $table->decimal('taux_interet', 6, 3);
            $table->date('date_octroi');
            $table->unsignedInteger('duree_mois');
            $table->enum('statut', ['en_cours', 'solde'])->default('en_cours');
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('echeances_emprunt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emprunt_id')->constrained('emprunts')->cascadeOnDelete();
            $table->unsignedInteger('numero_echeance');
            $table->date('date_echeance');
            $table->decimal('montant_prevu', 12, 2);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->date('date_paiement')->nullable();
            $table->enum('statut', ['a_venir', 'payee', 'en_retard'])->default('a_venir');
            $table->timestamps();

            $table->unique(['emprunt_id', 'numero_echeance'], 'uniq_emprunt_echeance');
            $table->index('statut', 'idx_echeances_statut');
            $table->index('date_echeance', 'idx_echeances_date');
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 60);
            $table->uuid('record_uuid');
            $table->unsignedBigInteger('vendeur_id')->nullable(); // pas de FK intentionnellement
            $table->string('device_id', 100)->nullable();
            $table->enum('action', ['create', 'update']);
            $table->timestamp('synced_at')->useCurrent();

            $table->index(['table_name', 'record_uuid'], 'idx_sync_table_uuid');
            $table->index('device_id', 'idx_sync_device');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('echeances_emprunt');
        Schema::dropIfExists('emprunts');
    }
};
