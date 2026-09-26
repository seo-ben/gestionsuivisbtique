<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AutorisationReapprovisionnement extends Model
{
    protected $table = 'autorisations_reapprovisionnement';

    protected $fillable = [
        'uuid', 'boutique_id', 'vendeur_id', 'accordee_par',
        'type', 'statut', 'date_debut', 'date_fin', 'note',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($a) => $a->uuid ??= (string) Str::uuid());
    }

    public function boutique()      { return $this->belongsTo(Boutique::class); }
    public function vendeur()       { return $this->belongsTo(User::class, 'vendeur_id'); }
    public function accordeePar()   { return $this->belongsTo(User::class, 'accordee_par'); }
    public function reapprovisionnements() { return $this->hasMany(Reapprovisionnement::class, 'autorisation_id'); }

    public function estActive(): bool
    {
        return $this->statut === 'active'
            && ($this->date_fin === null || $this->date_fin->isFuture());
    }
}
