<?php

namespace App\Http\Controllers\Agent;

use App\Enums\StatutSignalement;
use App\Http\Controllers\Controller;
use App\Models\Signalement;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function index(Request $request): View
    {
        return view('agent.missions.index', [
            'utilisateur' => $request->user(),
            'missions' => $request->user()->interventions()
                ->whereIn('statut', ['affecte', 'en_cours'])
                ->latest('updated_at')
                ->get(),
        ]);
    }

    public function show(Request $request, Signalement $signalement): View
    {
        $this->verifierProprietaire($request, $signalement);

        $signalement->load(['agent', 'historique.user']);

        // L'agent ne reçoit jamais les coordonnées du citoyen
        $signalement->setRelation('citoyen', null);

        return view('agent.missions.show', [
            'utilisateur' => $request->user(),
            'mission' => $signalement,
        ]);
    }

    public function demarrer(Request $request, Signalement $signalement): RedirectResponse
    {
        $this->verifierProprietaire($request, $signalement);

        try {
            $signalement->changerStatut(StatutSignalement::EnCours, $request->user(), 'L\'équipe est sur place.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Mission prise en charge : statut « En cours ».');
    }

    public function terminer(Request $request, Signalement $signalement): RedirectResponse
    {
        $this->verifierProprietaire($request, $signalement);

        $donnees = $request->validate([
            'compte_rendu' => ['required', 'string', 'min:10', 'max:2000'],
            'photo_preuve' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            $signalement->compte_rendu = $donnees['compte_rendu'];

            if ($request->hasFile('photo_preuve')) {
                $signalement->photo_resolution = $request->file('photo_preuve')->store('resolutions', 'public');
            }

            // L'agent passe à « Résolu » ; seul le responsable pourra clôturer (« Vérifié »)
            $signalement->changerStatut(StatutSignalement::Resolu, $request->user(), $donnees['compte_rendu']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('agent.missions.index')
            ->with('success', 'Fin d\'intervention transmise au responsable pour validation.');
    }

    public function historique(Request $request): View
    {
        return view('agent.historique', [
            'utilisateur' => $request->user(),
            'missions' => $request->user()->interventions()
                ->whereIn('statut', ['resolu', 'verifie'])
                ->latest('updated_at')
                ->get(),
        ]);
    }

    /**
     * Un agent n'accède qu'aux missions qui LUI sont affectées.
     */
    private function verifierProprietaire(Request $request, Signalement $signalement): void
    {
        abort_unless($signalement->agent_id === $request->user()->id, 404);
    }
}