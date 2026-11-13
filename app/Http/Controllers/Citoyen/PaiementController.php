<?php

namespace App\Http\Controllers\Citoyen;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Historique des paiements et reçus numériques du citoyen. */
class PaiementController extends Controller
{
    public function index(Request $request): View
    {
        return view('citoyen.collecte.paiements', [
            'paiements' => $request->user()->paiements()->with('abonnement')->paginate(20),
        ]);
    }

    /** Reçu (imprimable, avec QR code de vérification). */
    public function show(Request $request, Paiement $paiement): View
    {
        abort_unless($paiement->user_id === $request->user()->id, 404);
        $paiement->load(['abonnement', 'citoyen', 'collectes']);

        return view('citoyen.collecte.recu', ['paiement' => $paiement]);
    }

    public function annuler(Request $request, Paiement $paiement): RedirectResponse
    {
        abort_unless($paiement->user_id === $request->user()->id, 404);

        try {
            $paiement->annuler($request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Paiement annulé. Vous pouvez réessayer depuis votre abonnement.');
    }
}
