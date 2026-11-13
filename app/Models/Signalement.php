<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signalement extends Model
{
    /**
     * Valeurs par défaut d'un nouvel objet, avant même d'être enregistré.
     */
    protected $attributes = [
        'statut' => 'nouveau',
    ];

    /**
     * Colonnes qu'on autorise à remplir "en masse" avec create([...]) ou fill([...]).
     */
    protected $fillable = [
        'categorie',
        'description',
        'commune',
        'quartier',
        'latitude',
        'longitude',
        'photo',
        'statut',
        'priorite',
        'agent_id',
        'consigne',
        'motif_rejet',
        'compte_rendu',
        'photo_resolution',
        'affecte_at',
        'resolu_at',
    ];

    /**
     * Conversion automatique des types entre MySQL et PHP.
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'affecte_at' => 'datetime',
            'resolu_at' => 'datetime',
        ];
    }

    /**
     * Code exécuté automatiquement à certains moments de la vie du modèle.
     */
    protected static function booted(): void
    {
        static::created(function (Signalement $signalement) {
            $signalement->reference = 'CC-' . str_pad($signalement->id, 4, '0', STR_PAD_LEFT);
            $signalement->saveQuietly();
        });
    }

    /**
     * Le citoyen qui a créé le signalement.
     */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * L'agent de terrain affecté au signalement.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}