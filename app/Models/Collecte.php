<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Une collecte à domicile demandée par un citoyen abonné.
 * statut : demandee → effectuee | annulee (définitif).
 * Une collecte effectuée consomme une collecte de l'abonnement : le même reçu
 * ne peut donc pas servir deux fois pour la même demande, ni au-delà des collectes payées.
 */
class Collecte extends Model
{
    protected $attributes = [
        'statut' => 'demandee',
        'creneau' => 'indifferent',
    ];

    protected $fillable = ['creneau', 'note_citoyen'];

    protected function casts(): array
    {
        return [
            'effectuee_at' => 'datetime',
        ];
    }

    public function abonnement(): BelongsTo
    {
        return $this->belongsTo(Abonnement::class);
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /** Le citoyen demande un passage. Une seule demande en attente à la fois. */
    public static function demander(Abonnement $abonnement, string $creneau, ?string $note): self
    {
        return DB::transaction(function () use ($abonnement, $creneau, $note) {
            $abo = Abonnement::whereKey($abonnement->id)->lockForUpdate()->firstOrFail();
            if (! $abo->estUtilisable()) {
                throw new DomainException('Cet abonnement ne permet plus de demander une collecte.');
            }
            $paiement = $abo->paiementConfirme()->first();
            if (! $paiement) {
                throw new DomainException('Aucun paiement confirmé pour cet abonnement.');
            }
            if ($abo->collectes()->where('statut', 'demandee')->exists()) {
                throw new DomainException('Une demande de collecte est déjà en attente pour cet abonnement.');
            }

            $collecte = new self(['creneau' => $creneau, 'note_citoyen' => $note]);
            $collecte->abonnement_id = $abo->id;
            $collecte->paiement_id = $paiement->id;
            $collecte->save();

            return $collecte;
        });
    }

    /**
     * L'agent confirme le passage. Tout est revérifié EN BASE, sous verrou :
     * demande encore ouverte, paiement confirmé, abonnement actif, non expiré, collectes restantes.
     */
    public function confirmerPar(User $agent, ?string $note = null): void
    {
        DB::transaction(function () use ($agent, $note) {
            $collecte = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($collecte->statut === 'effectuee') {
                throw new DomainException('Cette collecte a déjà été effectuée : le reçu ne peut pas être réutilisé pour cette demande.');
            }
            if ($collecte->statut !== 'demandee') {
                throw new DomainException('Cette demande de collecte a été annulée.');
            }

            $abonnement = Abonnement::whereKey($collecte->abonnement_id)->lockForUpdate()->firstOrFail();
            $paiement = Paiement::whereKey($collecte->paiement_id)->first();
            if (! $paiement || $paiement->statut !== 'confirme') {
                throw new DomainException('Le paiement lié à cette demande n\'est pas confirmé.');
            }
            if (! $abonnement->estUtilisable()) {
                throw new DomainException('Abonnement non valable : '.mb_strtolower(config('cleancomores.abonnements.'.$abonnement->statutEffectif().'.label', $abonnement->statutEffectif())).'.');
            }

            $abonnement->collectes_restantes--;
            $abonnement->save();

            $collecte->statut = 'effectuee';
            $collecte->agent_id = $agent->id;
            $collecte->note_agent = $note;
            $collecte->effectuee_at = now();
            $collecte->save();

            $this->setRawAttributes($collecte->getAttributes(), true);
        });
    }

    /** Le citoyen annule une demande encore en attente. */
    public function annuler(): void
    {
        DB::transaction(function () {
            $collecte = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($collecte->statut !== 'demandee') {
                throw new DomainException('Cette demande ne peut plus être annulée.');
            }
            $collecte->statut = 'annulee';
            $collecte->save();
            $this->setRawAttributes($collecte->getAttributes(), true);
        });
    }
}
