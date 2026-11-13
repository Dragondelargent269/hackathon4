<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formule de collecte à domicile (prix en KMF, durée, collectes incluses).
 * Gérée dans /admin : changer un tarif ne demande aucune modification du code.
 */
class Formule extends Model
{
    protected $fillable = [
        'nom', 'slug', 'description', 'prix', 'duree_jours',
        'collectes_incluses', 'services', 'active', 'ordre',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'integer',
            'duree_jours' => 'integer',
            'collectes_incluses' => 'integer',
            'services' => 'array',
            'active' => 'boolean',
        ];
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('active', true)->orderBy('ordre')->orderBy('prix');
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }
}
