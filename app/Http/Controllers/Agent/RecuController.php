<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Collecte;
use App\Models\Paiement;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Vérification d'un reçu par l'agent : tout est lu EN BASE.
 * Une capture d'écran ou un faux QR code ne peut pas afficher « confirmé ».
 */
class RecuController extends Controller
{
    /** Formulaire de saisie de la référence (ou ouverture via le QR code). */
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->filled('reference')) {
            return redirect()->route('agent.recus.show', strtoupper(trim($request->input('reference'))));
        }

        return view('agent.recus.index');
    }

    public function show(Request $request, string $reference): View
    {
        $paiement = Paiement::where('reference', strtoupper($reference))
            ->with(['abonnement.citoyen', 'abonnement.collecteEnAttente', 'collectes.agent'])
            ->first();

        // Jeton du QR code : absent = saisie manuelle ; présent mais faux = QR falsifié
        $jeton = $request->query('jeton');
        $qrInvalide = $paiement && $jeton !== null && ! $paiement->jetonValide($jeton);

        return view('agent.recus.show', [
            'reference' => strtoupper($reference),
            'paiement' => $qrInvalide ? null : $paiement,
            'qrInvalide' => $qrInvalide,
            'viaQr' => $paiement && $jeton !== null && ! $qrInvalide,
        ]);
    }

    /**
     * Collecte faite sur place sans demande préalable : on crée la demande et on la confirme
     * dans la même transaction (mêmes contrôles que Collecte::confirmerPar).
     */
    public function collecter(Request $request, string $reference): RedirectResponse
    {
        $paiement = Paiement::where('reference', strtoupper($reference))->firstOrFail();
        $donnees = $request->validate(['note_agent' => ['nullable', 'string', 'max:500']]);

        if ($paiement->statut !== 'confirme') {
            return back()->with('error', 'Paiement non confirmé : aucune collecte ne peut être enregistrée.');
        }

        try {
            $collecte = DB::transaction(function () use ($paiement, $request, $donnees) {
                $abonnement = $paiement->abonnement;
                $collecte = $abonnement->collecteEnAttente()->first()
                    ?? Collecte::demander($abonnement, 'indifferent', 'Collecte enregistrée sur place par l\'agent.');
                $collecte->confirmerPar($request->user(), $donnees['note_agent'] ?? null);

                return $collecte;
            });
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('agent.collectes.show', $collecte)
            ->with('success', 'Collecte effectuée et enregistrée.');
    }
}
