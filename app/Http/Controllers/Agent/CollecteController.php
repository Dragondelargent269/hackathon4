<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Collecte;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Collectes à domicile côté agent : demandes payées, confirmation, historique. */
class CollecteController extends Controller
{
    /** Demandes en attente dont l'abonnement est payé et valable. */
    public function index(Request $request): View
    {
        $communes = collect(config('cleancomores.zones'))->pluck('communes')->flatten()->all();
        $commune = in_array($request->input('commune'), $communes, true) ? $request->input('commune') : null;

        $demandes = Collecte::where('statut', 'demandee')
            ->whereHas('paiement', fn ($q) => $q->where('statut', 'confirme'))
            ->whereHas('abonnement', fn ($q) => $q->where('statut', 'actif')
                ->where('fin_at', '>', now())->where('collectes_restantes', '>', 0)
                ->when($commune, fn ($q, $c) => $q->where('commune', $c)))
            ->with(['abonnement.citoyen', 'paiement'])
            ->oldest()
            ->get();

        return view('agent.collectes.index', [
            'demandes' => $demandes,
            'commune' => $commune,
            'communes' => $communes,
        ]);
    }

    public function show(Request $request, Collecte $collecte): View
    {
        $this->verifierAcces($request, $collecte);
        $collecte->load(['abonnement.citoyen', 'paiement', 'agent']);

        return view('agent.collectes.show', ['collecte' => $collecte]);
    }

    public function confirmer(Request $request, Collecte $collecte): RedirectResponse
    {
        $this->verifierAcces($request, $collecte);
        $donnees = $request->validate([
            'note_agent' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $collecte->confirmerPar($request->user(), $donnees['note_agent'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('agent.collectes.show', $collecte)
            ->with('success', 'Collecte effectuée et enregistrée. Le reçu ne peut plus servir pour cette demande.');
    }

    /** Collectes effectuées par l'agent connecté. */
    public function historique(Request $request): View
    {
        return view('agent.collectes.historique', [
            'collectes' => $request->user()->collectesEffectuees()
                ->where('statut', 'effectuee')
                ->with(['abonnement', 'paiement'])
                ->latest('effectuee_at')
                ->paginate(20),
        ]);
    }

    /** Une demande ouverte est visible par tous les agents ; une collecte faite, seulement par son agent. */
    private function verifierAcces(Request $request, Collecte $collecte): void
    {
        abort_unless($collecte->statut === 'demandee' || $collecte->agent_id === $request->user()->id, 404);
    }
}
