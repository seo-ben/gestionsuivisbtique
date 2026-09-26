<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emprunt extends Model
{
    protected $fillable = [
        'admin_id', 'organisme_preteur', 'capital_emprunte', 'capital_restant_du',
        'taux_interet', 'date_octroi', 'duree_mois', 'statut', 'note',
    ];

    protected $casts = [
        'date_octroi'       => 'date',
        'capital_emprunte'  => 'float',
        'capital_restant_du' => 'float',
        'taux_interet'      => 'float',
    ];

    public function admin()     { return $this->belongsTo(User::class, 'admin_id'); }
    public function echeances() { return $this->hasMany(EcheanceEmprunt::class); }

    /** Recalcule capital_restant_du depuis les échéances et met à jour statut. */
    public function recalculerCapital(): void
    {
        $totalPaye = $this->echeances()->sum('montant_paye');
        $restant   = max(0, $this->capital_emprunte - $totalPaye);

        $this->update([
            'capital_restant_du' => $restant,
            'statut'             => $restant <= 0 ? 'solde' : 'en_cours',
        ]);
    }
}
