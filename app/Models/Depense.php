<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'admin_id',
        'boutique_id',
        'categorie',
        'libelle',
        'montant',
        'date_depense',
        'deduire_de_caisse',
        'note',
    ];

    protected $casts = [
        'montant' => 'float',
        'deduire_de_caisse' => 'boolean',
        'date_depense' => 'date:Y-m-d',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class, 'boutique_id');
    }
}
