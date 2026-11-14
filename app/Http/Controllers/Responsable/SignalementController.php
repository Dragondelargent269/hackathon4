<?php

namespace App\Http\Controllers\Responsable;

use App\Enums\Priorite;
use App\Enums\StatutSignalement;
use App\Http\Controllers\Controller;
use App\Models\Signalement;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SignalementController extends Controller
{
    public function index(Request $request): View
    {
        $signalements = Signalement::query()
            ->with(['citoyen', 'agent'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('priorite'), fn ($q) => $q->where('priorite', $request->priorite))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie', $request->categorie))
            ->when($request->filled('zone'), fn ($q) => $q->where('zone', $request->zone))
            ->when($request->filled('q'), function ($q) use ($request) {
                $terme = '%'.$request->q.'%';
                $q->where(fn ($w) => $w
                    ->where('reference', 'like', $terme)
                    ->orWhere('description', 'like', $terme)
                    ->orWhere('commune', 'like', $terme)
                    ->orWhere('repere', 'like', $terme));
            })
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('responsable.signalements.index', [
            'utilisateur' => $request->user(),
            'signalements' => $signalements,
            'total' => Signalement::count(),
        ]);
    }

    public function show(Request $request, Signalement $signalement): View
    {
        $signalement->load(['citoyen', 'agent', 'historique.user']);

        $agents = User::query()
            ->where('role', User::ROLE_AGENT)
            ->withCount(['interventions as missions_en_cours' => fn ($q) => $q->whereIn('statut', ['affecte', 'en_cours'])])
            ->orderBy('name')
            ->get()
            ->each(fn (User $agent) => $agent->disponible = $agent->missions_en_cours < 3);

        return view('responsable.signalements.show', [
            'utilisateur' => $request->user(),
            'signalement' => $signalement,
            'agents' => $agents,
        ]);
    }

    // ---------------------------------------------------------------
    // Actions du cycle de vie
    // ---------------------------------------------------------------

    public function examiner(Request $request, Signalement $signalement): RedirectResponse
    {
        return $this->appliquer(
            fn () => $signalement->changerStatut(StatutSignalement::AVerifier, $request->user()),
            'Le dossier est passé « À vérifier ».'
        );
    }

    public function valider(Request $request, Signalement $signalement): RedirectResponse
    {
        $donnees = $request->validate([
            'priorite' => ['required', Rule::enum(Priorite::class)],
        ]);

        return $this->appliquer(function () use ($request, $signalement, $donnees) {
            $signalement->priorite = $donnees['priorite'];
            $signalement->changerStatut(
                StatutSignalement::Valide,
                $request->user(),
                                'Priorité définie : '.Priorite::from($donnees['priorite'])->getLabel().'.'
            );
        }, 'Signalement validé, priorité enregistrée.');
    }

    public function priorite(Request $request, Signalement $signalement): RedirectResponse
    {
        $donnees = $request->validate([
            'priorite' => ['required', Rule::enum(Priorite::class)],
        ]);

        $signalement->update(['priorite' => $donnees['priorite']]);

        return back()->with('success', 'Priorité mise à jour.');
    }

    public function affecter(Request $request, Signalement $signalement): RedirectResponse
    {
        $donnees = $request->validate([
            'agent_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_AGENT)],
            'consignes' => ['nullable', 'string', 'max:1000'],
        ]);

        $agent = User::findOrFail($donnees['agent_id']);

        return $this->appliquer(function () use ($request, $signalement, $donnees, $agent) {
            $signalement->agent_id = $agent->id;
            $signalement->consignes = $donnees['consignes'] ?? null;
            $signalement->changerStatut(
                StatutSignalement::Affecte,
                $request->user(),
                "Mission confiée à {$agent->name}."
            );
        }, "Mission affectée à {$agent->name}.");
    }

    public function rejeter(Request $request, Signalement $signalement): RedirectResponse
    {
        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        return $this->appliquer(function () use ($request, $signalement, $donnees) {
            $signalement->motif_rejet = $donnees['motif'];
            $signalement->changerStatut(StatutSignalement::Rejete, $request->user(), $donnees['motif']);
        }, 'Signalement rejeté. Le citoyen verra le motif.');
    }

    public function cloturer(Request $request, Signalement $signalement): RedirectResponse
    {
        return $this->appliquer(
            fn () => $signalement->changerStatut(
                StatutSignalement::Verifie,
                $request->user(),
                'Résolution contrôlée et validée. Merci pour votre signalement !'
            ),
            'Résolution validée : dossier clôturé (Vérifié).'
        );
    }

    public function reprise(Request $request, Signalement $signalement): RedirectResponse
    {
        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        return $this->appliquer(
            fn () => $signalement->changerStatut(
                StatutSignalement::EnCours,
                $request->user(),
                'Reprise demandée : '.$donnees['motif']
            ),
            'Reprise demandée : le dossier repasse « En cours ».'
        );
    }

    /**
     * Exécute une transition. Si elle est interdite, on revient avec un message d'erreur.
     */
    private function appliquer(callable $action, string $succes): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $succes);
    }
}