<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Abonnement d'un citoyen à une formule de collecte à domicile.
 * statut en base : en_attente_paiement | actif | annule.
 * « expire » et « epuise » sont calculés (voir statutEffectif()).
 * statut, debut_at, fin_at et collectes_restantes ne changent QUE via
 * Paiement::confirmer() et Collecte::confirmerPar().
 */
class Abonnement extends Model
{
    protected $attributes = [
        'statut' => 'en_attente_paiement',
    ];

    protected $fillable = [
        'formule_id', 'formule_nom', 'prix', 'duree_jours', 'collectes_incluses',
        'adresse', 'commune', 'zone', 'repere', 'latitude', 'longitude', 'telephone',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'integer',
            'duree_jours' => 'integer',
            'collectes_incluses' => 'integer',
            'collectes_restantes' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'debut_at' => 'datetime',
            'fin_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Abonnement $abonnement) {
            $abonnement->reference = sprintf('AB-%s-%04d', $abonnement->created_at->format('Y'), $abonnement->id);
            $abonnement->saveQuietly();
        });
    }

    /** Crée un abonnement en attente de paiement, avec une copie de la formule. */
    public static function souscrire(User $citoyen, Formule $formule, array $adresse): self
    {
        $abonnement = new self([
            ...$adresse,
            'formule_id' => $formule->id,
            'formule_nom' => $formule->nom,
            'prix' => $formule->prix,
            'duree_jours' => $formule->duree_jours,
            'collectes_incluses' => $formule->collectes_incluses,
        ]);
        $abonnement->user_id = $citoyen->id;
        $abonnement->save();

        return $abonnement;
    }

    // ---------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------

    public function citoyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function formule(): BelongsTo
    {
        return $this->belongsTo(Formule::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class)->latest('id');
    }

    public function paiementConfirme(): HasOne
    {
        return $this->hasOne(Paiement::class)->where('statut', 'confirme')->latestOfMany();
    }

    public function collectes(): HasMany
    {
        return $this->hasMany(Collecte::class)->latest('id');
    }

    public function collecteEnAttente(): HasOne
    {
        return $this->hasOne(Collecte::class)->where('statut', 'demandee')->latestOfMany();
    }

    // ---------------------------------------------------------------
    // État
    // ---------------------------------------------------------------

    /** en_attente_paiement | actif | epuise | expire | annule */
    public function statutEffectif(): string
    {
        if ($this->statut !== 'actif') {
            return $this->statut;
        }
        if ($this->fin_at && $this->fin_at->isPast()) {
            return 'expire';
        }
        if ($this->collectes_restantes <= 0) {
            return 'epuise';
        }

        return 'actif';
    }

    /** Peut-on encore demander / effectuer une collecte avec cet abonnement ? */
    public function estUtilisable(): bool
    {
        return $this->statutEffectif() === 'actif';
    }

    /** Point prêt pour <x-cc.carte>. */
    public function pourCarte(?string $url = null, ?string $details = null): array
    {
        return [
            'type' => 'collecte_domicile',
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'titre' => $this->adresse,
            'details' => $details ?? $this->commune,
            'url' => $url,
            'lien' => 'Voir la demande',
        ];
    }
}
