<?php

namespace App\Http\Controllers\Responsable;

use App\Http\Controllers\Controller;
use App\Models\Collecte;
use App\Models\PointCollecte;
use App\Models\Signalement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarteController extends Controller
{
    /**
     * Carte complète : signalements filtrables (île, état, catégorie),
     * points de collecte et collectes à domicile en attente.
     */
    public function index(Request $request): View
    {
        $groupes = config('cleancomores.marqueurs');
        $filtres = [
            'zone' => array_key_exists($request->input('zone'), config('cleancomores.zones')) ? $request->input('zone') : null,
            'etat' => array_key_exists($request->input('etat'), $groupes) ? $request->input('etat') : null,
            'categorie' => array_key_exists($request->input('categorie'), config('cleancomores.categories')) ? $request->input('categorie') : null,
            // Couches : par défaut tout est affiché ; le formulaire envoie couches[] (+ un champ caché « f »)
            'couches' => $request->has('f')
                ? array_values(array_intersect((array) $request->input('couches', []), ['signalements', 'point_collecte', 'collecte_domicile']))
                : ['signalements', 'point_collecte', 'collecte_domicile'],
        ];

        $points = collect();

        if (in_array('signalements', $filtres['couches'], true)) {
            $points = $points->concat(
                Signalement::query()
                    ->whereNotNull('latitude')->whereNotNull('longitude')
                    ->when($filtres['zone'], fn ($q, $z) => $q->where('zone', $z))
                    ->when($filtres['etat'], fn ($q, $e) => $q->whereIn('statut', $groupes[$e]['statuts']))
                    ->when($filtres['categorie'], fn ($q, $c) => $q->where('categorie', $c))
                    ->latest()->limit(2000)
                    ->get(['id', 'reference', 'categorie', 'commune', 'statut', 'latitude', 'longitude'])
                    ->map(fn ($s) => [
                        'type' => 'signalement',
                        'lat' => $s->latitude,
                        'lng' => $s->longitude,
                        'titre' => $s->reference.' — '.$s->commune,
                        'details' => config('cleancomores.categories.'.$s->categorie.'.label'),
                        'statut' => $s->statut,
                        'url' => route('responsable.signalements.show', $s->id),
                    ])
            );
        }

        if (in_array('point_collecte', $filtres['couches'], true)) {
            $points = $points->concat(
                PointCollecte::actifs()->when($filtres['zone'], fn ($q, $z) => $q->where('zone', $z))->get()->map->pourCarte()
            );
        }

        if (in_array('collecte_domicile', $filtres['couches'], true)) {
            $points = $points->concat(
                Collecte::where('statut', 'demandee')
                    ->whereHas('abonnement', fn ($q) => $q->when($filtres['zone'], fn ($q, $z) => $q->where('zone', $z)))
                    ->with('abonnement')->get()
                    ->map(fn ($c) => $c->abonnement->pourCarte(null, $c->abonnement->commune.' · '.$c->abonnement->formule_nom))
            );
        }

        return view('responsable.carte', [
            'points' => $points->values(),
            'filtres' => $filtres,
            'compteurs' => $points->countBy('type'),
        ]);
    }
}
