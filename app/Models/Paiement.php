<?php

namespace App\Models;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Paiement d'un abonnement = reçu numérique.
 * Le reçu n'est une preuve que si statut = confirme EN BASE : l'agent le vérifie
 * dans l'application (référence + jeton du QR code), jamais sur une capture d'écran.
 *
 * statut : en_attente → confirme | echoue | annule (aucun retour possible).
 * statut, traite_par et confirme_at ne changent QUE via confirmer(), refuser(), annuler().
 */
class Paiement extends Model
{
    protected $attributes = [
        'statut' => 'en_attente',
        'devise' => 'KMF',
    ];

    protected $fillable = ['operateur', 'telephone'];

    protected $hidden = ['jeton'];

    protected function casts(): array
    {
        return [
            'montant' => 'integer',
            'simulation' => 'boolean',
            'confirme_at' => 'datetime',
        ];
    }

    /** Ouvre un paiement « en attente » pour l'abonnement (montant = prix copié dans l'abonnement). */
    public static function ouvrir(Abonnement $abonnement, string $operateur, string $telephone, bool $simulation): self
    {
        $paiement = new self(['operateur' => $operateur, 'telephone' => $telephone]);
        $paiement->abonnement_id = $abonnement->id;
        $paiement->user_id = $abonnement->user_id;
        $paiement->montant = $abonnement->prix;
        $paiement->devise = config('cleancomores.paiement.devise', 'KMF');
        $paiement->simulation = $simulation;
        $paiement->reference = self::nouvelleReference();
        $paiement->jeton = Str::random(40);
        $paiement->save();

        return $paiement;
    }

    /** Référence lisible et non devinable : PAY-7KQ4M9XA (sans 0/O, 1/I). */
    public static function nouvelleReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'PAY-'.$code;
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    // ---------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------

    public function abonnement(): BelongsTo
    {
        return $this->belongsTo(Abonnement::class);
    }

    public function citoyen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function collectes(): HasMany
    {
        return $this->hasMany(Collecte::class)->latest('id');
    }

    // ---------------------------------------------------------------
    // Transitions (verrouillées en base : deux actions simultanées ne passent pas)
    // ---------------------------------------------------------------

    /**
     * Confirme le paiement et active l'abonnement.
     * En simulation : action d'un responsable, enregistrée (traite_par, confirme_at).
     * Avec une vraie passerelle : appelée par le rappel signé de l'opérateur ($transaction).
     */
    public function confirmer(?User $par, ?string $transaction = null): void
    {
        DB::transaction(function () use ($par, $transaction) {
            $paiement = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($paiement->statut !== 'en_attente') {
                throw new DomainException("Ce paiement a déjà été traité ({$paiement->statut}).");
            }

            $abonnement = Abonnement::whereKey($paiement->abonnement_id)->lockForUpdate()->firstOrFail();
            if ($abonnement->statut !== 'en_attente_paiement') {
                throw new DomainException('Cet abonnement n\'attend plus de paiement.');
            }

            $paiement->statut = 'confirme';
            $paiement->traite_par = $par?->id;
            $paiement->confirme_at = now();
            $paiement->transaction_externe = $transaction;
            $paiement->save();

            $abonnement->statut = 'actif';
            $abonnement->debut_at = now();
            $abonnement->fin_at = now()->addDays($abonnement->duree_jours);
            $abonnement->collectes_restantes = $abonnement->collectes_incluses;
            $abonnement->save();

            $this->setRawAttributes($paiement->getAttributes(), true);
        });
    }

    /** Refus (paiement non reçu). L'abonnement reste en attente : le citoyen peut réessayer. */
    public function refuser(User $par, string $motif): void
    {
        $this->terminer('echoue', $par, $motif);
    }

    /** Le citoyen abandonne un paiement en attente. */
    public function annuler(User $par): void
    {
        $this->terminer('annule', $par, 'Annulé par le citoyen.');
    }

    private function terminer(string $statut, User $par, string $motif): void
    {
        DB::transaction(function () use ($statut, $par, $motif) {
            $paiement = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if ($paiement->statut !== 'en_attente') {
                throw new DomainException("Ce paiement a déjà été traité ({$paiement->statut}).");
            }
            $paiement->statut = $statut;
            $paiement->traite_par = $par->id;
            $paiement->motif_refus = $motif;
            $paiement->save();

            $this->setRawAttributes($paiement->getAttributes(), true);
        });
    }

    /** URL contenue dans le QR code du reçu (page de vérification des agents). */
    public function urlVerification(): string
    {
        return route('agent.recus.show', ['reference' => $this->reference, 'jeton' => $this->jeton]);
    }

    /** QR code (SVG inline) de l'URL de vérification. Ne contient aucune donnée de paiement. */
    public function qrSvg(int $taille = 200): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($taille, 1), new SvgImageBackEnd));
        $svg = $writer->writeString($this->urlVerification());

        return substr($svg, strpos($svg, '<svg'));
    }

    /** Le jeton du QR code correspond-il ? (comparaison à temps constant) */
    public function jetonValide(?string $jeton): bool
    {
        return $jeton !== null && hash_equals($this->jeton, $jeton);
    }
}
