<?php

namespace App\Http\Controllers\Citoyen;

use App\Http\Controllers\Controller;
use App\Models\PointCollecte;
use App\Models\Signalement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarteController extends Controller
{
    /**
     * Carte publique pour le citoyen : signalements (sans photo ni donnée personnelle)
     * et points de collecte. Seuls SES signalements ont un lien vers le dossier.
     */
    public function index(Request $request): View
    {
        $zone = $request->string('zone')->toString();
        $zone = array_key_exists($zone, config('cleancomores.zones')) ? $zone : null;
        $moi = $request->user()->id;

        $signalements = Signalement::query()
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->where('statut', '!=', 'rejete')
            ->when($zone, fn ($q) => $q->where('zone', $zone))
            ->latest()->limit(1000)
            ->get(['id', 'user_id', 'categorie', 'commune', 'statut', 'latitude', 'longitude']);

        $points = $signalements->map(fn ($s) => [
            'type' => 'signalement',
            'lat' => $s->latitude,
            'lng' => $s->longitude,
            'titre' => config('cleancomores.categories.'.$s->categorie.'.label', $s->categorie).' — '.$s->commune,
            'statut' => $s->statut,
            'url' => $s->user_id === $moi ? route('citoyen.signalements.show', $s->id) : null,
            'lien' => 'Voir mon signalement',
        ])->concat(
            PointCollecte::actifs()->when($zone, fn ($q) => $q->where('zone', $zone))->get()->map->pourCarte()
        );

        return view('citoyen.carte', [
            'points' => $points,
            'zone' => $zone,
            'nbSignalements' => $signalements->count(),
        ]);
    }
}
