<?php

namespace App\Http\Controllers\Citoyen;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Collecte;
use App\Models\Formule;
use App\Models\Paiement;
use App\Rules\TelephoneComores;
use App\Services\Paiement\PasserelleMobile;
use App\Support\Telephone;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Collecte à domicile côté citoyen : offres, souscription, paiement, demandes de passage.
 * Le signalement reste gratuit ; seule la collecte à domicile est payante.
 */
class AbonnementController extends Controller
{
    /** Offres disponibles + abonnements du citoyen. */
    public function index(Request $request): View
    {
        return view('citoyen.collecte.index', [
            'formules' => Formule::actives()->get(),
            'abonnements' => $request->user()->abonnements()->with(['paiementConfirme', 'collecteEnAttente'])->get(),
        ]);
    }

    /** Formulaire : adresse + position GPS + téléphone de paiement. */
    public function create(Request $request, Formule $formule): View
    {
        abort_unless($formule->active, 404);

        return view('citoyen.collecte.souscrire', [
            'formule' => $formule,
            'utilisateur' => $request->user(),
            'passerelle' => app(PasserelleMobile::class),
        ]);
    }

    /** Crée l'abonnement (en attente) et ouvre le paiement. Rien n'est « payé » ici. */
    public function store(Request $request, Formule $formule, PasserelleMobile $passerelle): RedirectResponse
    {
        abort_unless($formule->active, 404);

        $request->merge(['telephone' => Telephone::normaliser($request->input('telephone'))]);
        $donnees = $request->validate($this->regles(), [
            'latitude.required' => 'Indiquez la position de votre domicile sur la carte (ou utilisez « Me localiser »).',
            'longitude.required' => 'Indiquez la position de votre domicile sur la carte (ou utilisez « Me localiser »).',
        ]);
        $donnees['zone'] = $this->zoneDe($donnees['commune']);

        $paiement = DB::transaction(function () use ($request, $formule, $donnees, $passerelle) {
            $abonnement = Abonnement::souscrire($request->user(), $formule, $donnees);
            $paiement = Paiement::ouvrir($abonnement, $passerelle->code(), $donnees['telephone'], $passerelle->estSimulation());
            $passerelle->initier($paiement);

            return $paiement;
        });

        return redirect()
            ->route('citoyen.paiements.show', $paiement->reference)
            ->with('success', 'Demande de paiement enregistrée. Votre abonnement sera activé dès que le paiement sera confirmé.');
    }

    /** Détail d'un abonnement : validité, reçus, collectes, demande de passage. */
    public function show(Request $request, Abonnement $abonnement): View
    {
        $this->verifierProprietaire($request, $abonnement);
        $abonnement->load(['paiements', 'collectes.agent', 'paiementConfirme', 'collecteEnAttente']);

        return view('citoyen.collecte.abonnement', ['abonnement' => $abonnement]);
    }

    /** Nouvel essai de paiement après un refus ou une annulation. */
    public function payer(Request $request, Abonnement $abonnement, PasserelleMobile $passerelle): RedirectResponse
    {
        $this->verifierProprietaire($request, $abonnement);

        if ($abonnement->statut !== 'en_attente_paiement') {
            return back()->with('error', 'Cet abonnement n\'attend pas de paiement.');
        }
        if ($abonnement->paiements()->where('statut', 'en_attente')->exists()) {
            return back()->with('error', 'Un paiement est déjà en attente de confirmation pour cet abonnement.');
        }

        $paiement = Paiement::ouvrir($abonnement, $passerelle->code(), $abonnement->telephone, $passerelle->estSimulation());
        $passerelle->initier($paiement);

        return redirect()->route('citoyen.paiements.show', $paiement->reference)
            ->with('success', 'Nouvelle demande de paiement enregistrée.');
    }

    /** Le citoyen demande un passage de l'équipe de collecte. */
    public function demanderCollecte(Request $request, Abonnement $abonnement): RedirectResponse
    {
        $this->verifierProprietaire($request, $abonnement);

        $donnees = $request->validate([
            'creneau' => ['required', Rule::in(array_keys(config('cleancomores.creneaux')))],
            'note_citoyen' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            Collecte::demander($abonnement, $donnees['creneau'], $donnees['note_citoyen'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Demande de collecte envoyée. Un agent passera à votre adresse et vérifiera votre reçu.');
    }

    public function annulerCollecte(Request $request, Collecte $collecte): RedirectResponse
    {
        $this->verifierProprietaire($request, $collecte->abonnement);

        try {
            $collecte->annuler();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Demande de collecte annulée.');
    }

    private function verifierProprietaire(Request $request, Abonnement $abonnement): void
    {
        // Un citoyen ne voit QUE ses propres abonnements
        abort_unless($abonnement->user_id === $request->user()->id, 404);
    }

    private function regles(): array
    {
        $communes = collect(config('cleancomores.zones'))->pluck('communes')->flatten()->all();

        return [
            'adresse' => ['required', 'string', 'min:5', 'max:255'],
            'commune' => ['required', 'string', Rule::in($communes)],
            'repere' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-12.5,-11.3'],
            'longitude' => ['required', 'numeric', 'between:43.1,44.6'],
            'telephone' => ['required', new TelephoneComores],
            'conditions' => ['accepted'],
        ];
    }

    private function zoneDe(string $commune): ?string
    {
        return collect(config('cleancomores.zones'))
            ->search(fn ($zone) => in_array($commune, $zone['communes'], true)) ?: null;
    }
}
