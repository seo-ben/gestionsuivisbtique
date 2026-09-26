<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Reapprovisionnement extends Model
{
    public $timestamps = false;
    protected $table = 'reapprovisionnements';

    protected $fillable = [
        'uuid', 'session_uuid', 'autorisation_id', 'boutique_id', 'produit_id', 'vendeur_id',
        'quantite', 'unite', 'montant_depense', 'fournisseur',
        'date_reappro', 'heure_reappro', 'device_id', 'created_at_local', 'synced_at',
    ];

    protected $casts = [
        'date_reappro'    => 'date',
        'created_at_local' => 'datetime',
        'synced_at'       => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($r) => $r->uuid ??= (string) Str::uuid());
    }

    public function autorisation() { return $this->belongsTo(AutorisationReapprovisionnement::class, 'autorisation_id'); }
    public function boutique()     { return $this->belongsTo(Boutique::class); }
    public function produit()      { return $this->belongsTo(Produit::class); }
    public function vendeur()      { return $this->belongsTo(User::class, 'vendeur_id'); }
}
