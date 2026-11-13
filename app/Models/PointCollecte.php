<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Point de dépôt / centre de collecte affiché sur la carte. Géré dans /admin.
 */
class PointCollecte extends Model
{
    protected $table = 'points_collecte';

    protected $fillable = [
        'nom', 'commune', 'zone', 'adresse', 'latitude', 'longitude',
        'dechets_acceptes', 'horaires', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'actif' => 'boolean',
        ];
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    /** Point prêt pour <x-cc.carte>. */
    public function pourCarte(): array
    {
        return [
            'type' => 'point_collecte',
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'titre' => $this->nom,
            'details' => collect([$this->commune, $this->horaires, $this->dechets_acceptes])->filter()->implode(' · '),
        ];
    }
}
