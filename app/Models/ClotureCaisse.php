<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClotureCaisse extends Model
{
    public $timestamps = false;
    protected $table = 'clotures_caisse';

    protected $fillable = [
        'uuid', 'boutique_id', 'date_cloture', 'fond_caisse_initial',
        'total_ventes', 'total_sorties', 'solde_theorique',
        'solde_reel', 'ecart', 'statut', 'heure_cloture_effective',
    ];

    protected $casts = [
        'date_cloture'          => 'date',
        'heure_cloture_effective' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($c) => $c->uuid ??= (string) Str::uuid());
    }

    public function boutique() { return $this->belongsTo(Boutique::class); }
}
