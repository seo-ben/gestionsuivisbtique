<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vente extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'boutique_id', 'produit_id', 'vendeur_id',
        'quantite', 'unite', 'prix_unitaire', 'montant_total',
        'date_vente', 'heure_vente', 'device_id', 'created_at_local', 'synced_at',
    ];

    protected $casts = [
        'date_vente'      => 'date',
        'created_at_local' => 'datetime',
        'synced_at'       => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($v) => $v->uuid ??= (string) Str::uuid());
    }

    public function boutique() { return $this->belongsTo(Boutique::class); }
    public function produit()  { return $this->belongsTo(Produit::class); }
    public function vendeur()  { return $this->belongsTo(User::class, 'vendeur_id'); }
}
