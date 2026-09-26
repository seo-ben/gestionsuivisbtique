<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametresBoutique extends Model
{
    public $timestamps = false;
    protected $table = 'parametres_boutique';
    protected $primaryKey = 'boutique_id';
    public $incrementing = false;

    protected $fillable = [
        'boutique_id', 'heure_cloture', 'fond_caisse_initial',
        'blocage_ecart_actif', 'seuil_blocage_ecart', 'seuil_alerte_ecart',
    ];

    protected $casts = [
        'blocage_ecart_actif' => 'boolean',
        'fond_caisse_initial' => 'float',
        'seuil_blocage_ecart' => 'float',
        'seuil_alerte_ecart'  => 'float',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class, 'boutique_id');
    }
}
