<?php

namespace App\Http\Controllers\Responsable;

use App\Http\Controllers\Controller;
use App\Models\Signalement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableauController extends Controller
{
    public function tableau(Request $request): View
    {
        $actifs = fn () => Signalement::query()->whereNotIn('statut', ['verifie', 'rejete']);

        $retenus = Signalement::where('statut', '!=', 'rejete')->count();
        $resolus = Signalement::whereIn('statut', ['resolu', 'verifie'])->count();

        return view('responsable.tableau', [
            'utilisateur' => $request->user(),
            'indicateurs' => [
                'a_traiter' => Signalement::whereIn('statut', ['nouveau', 'a_verifier'])->count(),
                'en_intervention' => Signalement::whereIn('statut', ['affecte', 'en_cours'])->count(),
                'a_valider' => Signalement::where('statut', 'resolu')->count(),
                'taux_resolution' => $retenus > 0 ? (int) round($resolus / $retenus * 100) : 0,
            ],
            'urgents' => $actifs()->whereIn('priorite', ['critique', 'elevee'])
                ->with('agent')->latest('updated_at')->limit(10)->get(),
            'a_valider' => Signalement::where('statut', 'resolu')
                ->with('agent')->latest('updated_at')->get(),
            'nouveaux' => Signalement::whereIn('statut', ['nouveau', 'a_verifier'])
                ->latest()->limit(10)->get(),
            'carte' => $actifs()->whereNotNull('latitude')->whereNotNull('longitude')->get(),
        ]);
    }

    public function statistiques(Request $request): View
    {
        $debut = now()->startOfMonth()->subMonths(5);

        // Reçus et résolus par mois (6 derniers mois)
        $recus = Signalement::where('created_at', '>=', $debut)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as m, COUNT(*) as n")
            ->groupBy('m')->pluck('n', 'm');

        $resolusParMois = Signalement::where('resolu_at', '>=', $debut)
            ->selectRaw("DATE_FORMAT(resolu_at, '%Y-%m') as m, COUNT(*) as n")
            ->groupBy('m')->pluck('n', 'm');

        $mois = collect(range(0, 5))->map(function (int $i) use ($debut, $recus, $resolusParMois) {
            $m = $debut->copy()->addMonths($i);

            return [
                'mois' => ucfirst($m->locale('fr')->translatedFormat('M')),
                'recus' => (int) ($recus[$m->format('Y-m')] ?? 0),
                'resolus' => (int) ($resolusParMois[$m->format('Y-m')] ?? 0),
            ];
        })->all();

        // Par île
        $zones = Signalement::query()->toBase()
            ->whereNotNull('zone')
            ->selectRaw("zone, COUNT(*) as total, SUM(statut IN ('resolu', 'verifie')) as resolus")
            ->groupBy('zone')->get()
            ->map(fn ($r) => ['zone' => $r->zone, 'total' => (int) $r->total, 'resolus' => (int) $r->resolus])
            ->all();

        // Par catégorie
        $categories = Signalement::query()->toBase()
            ->selectRaw('categorie, COUNT(*) as total')
            ->groupBy('categorie')->orderByDesc('total')->get()
            ->map(fn ($r) => ['categorie' => $r->categorie, 'total' => (int) $r->total])
            ->all();

        // Délai moyen de résolution, en jours
        $heures = Signalement::query()->toBase()
            ->whereNotNull('resolu_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolu_at)) as h')
            ->first()?->h;

        return view('responsable.statistiques', [
            'utilisateur' => $request->user(),
            'stats' => [
                'mois' => $mois,
                'zones' => $zones,
                'categories' => $categories,
                'delai_moyen_jours' => $heures ? round($heures / 24, 1) : 0,
            ],
        ]);
    }
}