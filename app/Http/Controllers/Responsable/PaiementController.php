<?php

namespace App\Http\Controllers\Responsable;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Collecte;
use App\Models\Paiement;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Suivi des paiements, abonnements et collectes à domicile. */
class PaiementController extends Controller
{
    public function index(Request $request): View
    {
        $statut = array_key_exists($request->input('statut'), config('cleancomores.paiements')) ? $request->input('statut') : null;
        $q = trim((string) $request->input('q'));

        return view('responsable.paiements.index', [
            'paiements' => Paiement::query()
                ->with(['abonnement', 'citoyen', 'traitePar'])
                ->when($statut, fn ($query, $s) => $query->where('statut', $s))
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                    ->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('citoyen', fn ($c) => $c->where('name', 'like', "%{$q}%"))))
                ->orderByRaw("case when statut = 'en_attente' then 0 else 1 end")
                ->latest('id')
                ->paginate(25)->withQueryString(),
            'filtres' => ['statut' => $statut, 'q' => $q],
            'indicateurs' => [
                'en_attente' => Paiement::where('statut', 'en_attente')->count(),
                'encaisse' => (int) Paiement::where('statut', 'confirme')->sum('montant'),
                'abonnes_actifs' => Abonnement::where('statut', 'actif')->where('fin_at', '>', now())->where('collectes_restantes', '>', 0)->count(),
                'collectes_attente' => Collecte::where('statut', 'demandee')->count(),
                'collectes_mois' => Collecte::where('statut', 'effectuee')->where('effectuee_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    /** Confirmation manuelle : réservée aux paiements de SIMULATION (une vraie passerelle confirme seule). */
    public function confirmer(Request $request, Paiement $paiement): RedirectResponse
    {
        if (! $paiement->simulation) {
            return back()->with('error', 'Un paiement réel ne peut être confirmé que par l\'opérateur.');
        }

        try {
            $paiement->confirmer($request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Paiement {$paiement->reference} confirmé (simulation). L'abonnement est actif.");
    }

    public function refuser(Request $request, Paiement $paiement): RedirectResponse
    {
        $donnees = $request->validate(['motif' => ['required', 'string', 'min:5', 'max:255']]);

        try {
            $paiement->refuser($request->user(), $donnees['motif']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Paiement {$paiement->reference} refusé.");
    }
}
