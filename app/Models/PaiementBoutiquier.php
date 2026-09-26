<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaiementBoutiquier extends Model
{
    protected $table = 'paiements_boutiquiers';

    protected $fillable = [
        'uuid', 'vendeur_id', 'initie_par', 'montant', 'type',
        'date_paiement', 'statut', 'date_confirmation', 'note',
    ];

    protected $casts = [
        'date_paiement'    => 'date',
        'date_confirmation' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($p) => $p->uuid ??= (string) Str::uuid());
    }

    public function vendeur()    { return $this->belongsTo(User::class, 'vendeur_id'); }
    public function initiePar()  { return $this->belongsTo(User::class, 'initie_par'); }
}
