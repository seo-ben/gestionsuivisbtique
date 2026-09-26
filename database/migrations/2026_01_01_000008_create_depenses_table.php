<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('boutique_id')->nullable()->constrained('boutiques')->nullOnDelete();
            $table->string('categorie', 50); // loyer, electricite_eau, transport, glace_conservation, emballages, entretien, autre
            $table->string('libelle', 150);
            $table->decimal('montant', 12, 2);
            $table->date('date_depense');
            $table->boolean('deduire_de_caisse')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'date_depense']);
            $table->index(['boutique_id', 'date_depense']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
