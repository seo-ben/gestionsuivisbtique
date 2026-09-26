<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcheanceEmprunt extends Model
{
    protected $table = 'echeances_emprunt';

    protected $fillable = [
        'emprunt_id', 'numero_echeance', 'date_echeance',
        'montant_prevu', 'montant_paye', 'date_paiement', 'statut',
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'date_paiement' => 'date',
    ];

    public function emprunt() { return $this->belongsTo(Emprunt::class); }
}
