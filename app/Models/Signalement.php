<?php

namespace App\Models;

use App\Enums\StatutSignalement;
use DomainException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Signalement extends Model
{
    protected $attributes = [
        'statut' => 'nouveau',
    ];

    /**
     * statut, affecte_at et resolu_at ne sont PAS ici :
     * ils ne changent QUE via changerStatut().
     */
    protected $fillable = [
        'categorie',
        'description',
        'commune',
        'zone',
        'repere',
        'latitude',
        'longitude',
        'photo',
        'priorite',
        'agent_id',
        'consignes',
        'motif_rejet',
        'compte_rendu',
        'photo_resolution',
    ];

    /**
     * categorie, statut et priorite restent du TEXTE (contrat FRONTEND.md).
     * Les Enums servent à valider et à appliquer les règles métier.
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

    protected static function booted(): void
    {
        static::created(function (Signalement $signalement) {
            $signalement->reference = sprintf(
                'CC-%s-%04d',
                $signalement->created_at->format('Y'),
                $signalement->id
            );
            $signalement->saveQuietly();

            $signalement->historique()->create([
                'user_id' => $signalement->user_id,
                'statut' => StatutSignalement::Nouveau->value,
            ]);
        });
    }

    // ---------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------

    public function citoyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function historique(): HasMany
    {
        return $this->hasMany(HistoriqueSignalement::class)->oldest()->oldest('id');
    }

    // ---------------------------------------------------------------
    // Attributs calculés attendus par les vues
    // ---------------------------------------------------------------

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo
            ? asset('storage/'.$this->photo)
            : null);
    }

    protected function compteRendu(): Attribute
    {
        return Attribute::get(fn (?string $value) => $value === null ? null : (object) [
            'texte' => $value,
            'photo_url' => $this->photo_resolution
                ? asset('storage/'.$this->photo_resolution)
                : null,
            'created_at' => $this->resolu_at,
        ]);
    }

    // ---------------------------------------------------------------
    // Logique métier : SEUL point d'entrée pour changer un statut
    // ---------------------------------------------------------------

    public function changerStatut(StatutSignalement $nouveau, ?User $par = null, ?string $commentaire = null): void
    {
        $actuel = StatutSignalement::from($this->statut);

        if (! $actuel->peutPasserA($nouveau)) {
            throw new DomainException(
                "Transition interdite : {$actuel->getLabel()} → {$nouveau->getLabel()}."
            );
        }

        DB::transaction(function () use ($nouveau, $par, $commentaire) {
            $this->statut = $nouveau->value;

            if ($nouveau === StatutSignalement::Affecte) {
                $this->affecte_at = now();
            }

            if ($nouveau === StatutSignalement::Resolu) {
                $this->resolu_at = now();
            }

            $this->save();

            $this->historique()->create([
                'user_id' => $par?->id,
                'statut' => $nouveau->value,
                'commentaire' => $commentaire,
            ]);
        });
    }
}