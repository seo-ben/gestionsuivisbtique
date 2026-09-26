<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Produit extends Model
{
    protected $fillable = [
        'uuid', 'boutique_id', 'nom', 'prix_actuel', 'unite_reference',
        'vendeur_peut_modifier_prix', 'stock_initial', 'stock_actuel', 'seuil_alerte_stock', 'actif', 'version',
    ];

    protected $casts = [
        'vendeur_peut_modifier_prix' => 'boolean',
        'actif' => 'boolean',
        'stock_initial' => 'float',
        'stock_actuel' => 'float',
        'prix_actuel' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function ($p) {
            $p->uuid ??= (string) Str::uuid();
            if (isset($p->stock_actuel) && !isset($p->stock_initial)) {
                $p->stock_initial = $p->stock_actuel;
            } elseif (isset($p->stock_initial) && !isset($p->stock_actuel)) {
                $p->stock_actuel = $p->stock_initial;
            }
        });
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class);
    }

    public function reapprovisionnements()
    {
        return $this->hasMany(Reapprovisionnement::class);
    }

    /**
     * Recalcule le stock depuis les mouvements et incrémente le verrou optimiste.
     * Appelé par SyncService après chaque synchronisation.
     */
    public function reconcilierStock(): void
    {
        $totalReappros = (float) $this->reapprovisionnements()->sum('quantite');
        $totalVentes   = (float) $this->ventes()->sum('quantite');
        $stock         = (float) $this->stock_initial + $totalReappros - $totalVentes;

        $this->update([
            'stock_actuel' => max(0, $stock),
            'version'      => $this->version + 1,
        ]);
    }
}
