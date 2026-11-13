<?php

/*
|--------------------------------------------------------------------------
| Routes MAQUETTE (front-end) — données fictives
|--------------------------------------------------------------------------
|
| Ces routes portent les NOMS et URL définitifs attendus par les vues.
| Pour brancher le back-end : remplacer chaque closure par l'action du
| contrôleur correspondant (même nom de route, mêmes variables de vue —
| voir FRONTEND.md), puis supprimer ce fichier et son require dans web.php.
|
*/

use App\Support\Maquette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

View::share('maquette', true);
app()->instance('maquette.utilisateur', fn (string $role) => Maquette::utilisateur($role));

// Simule le traitement d'un formulaire : retour avec message de succès
$simuler = fn (string $message, ?string $vers = null) => function (Request $request) use ($message, $vers) {
    return ($vers ? redirect()->route($vers, $request->route()->parameters()) : back())
        ->with('success', $message.' (maquette : aucune donnée enregistrée)');
};

/* ---------------------------------------------------------------- Public */
Route::view('/connexion', 'auth.connexion')->name('connexion');
Route::view('/inscription', 'auth.inscription')->name('inscription');

/* --------------------------------------------------------------- Citoyen */
Route::prefix('citoyen')->name('citoyen.')->group(function () use ($simuler) {
    Route::get('/', fn () => view('citoyen.signalements.index', [
        'utilisateur' => Maquette::utilisateur('citoyen'),
        'signalements' => Maquette::signalements()->filter(fn ($s) => $s->citoyen->name === 'Mariama Ahmed')->sortByDesc('updated_at')->values(),
    ]))->name('signalements.index');

    Route::get('/signalements/nouveau', fn () => view('citoyen.signalements.create', [
        'utilisateur' => Maquette::utilisateur('citoyen'),
    ]))->name('signalements.create');

    Route::post('/signalements', fn () => redirect()->route('citoyen.signalements.show', 142)
        ->with('success', 'Merci ! Votre signalement CC-2026-0142 a bien été envoyé. Vous serez informé(e) de chaque étape. (maquette)'))
        ->name('signalements.store');

    Route::get('/signalements/{signalement}', fn (int $signalement) => view('citoyen.signalements.show', [
        'utilisateur' => Maquette::utilisateur('citoyen'),
        'signalement' => Maquette::signalement($signalement),
    ]))->name('signalements.show');

    Route::get('/profil', fn () => view('citoyen.profil', [
        'utilisateur' => Maquette::utilisateur('citoyen'),
    ]))->name('profil.edit');
    Route::put('/profil', $simuler('Votre profil a été mis à jour.'))->name('profil.update');
    Route::put('/profil/notifications', $simuler('Vos préférences de notification ont été enregistrées.'))->name('profil.notifications');
    Route::put('/profil/mot-de-passe', $simuler('Votre mot de passe a été modifié.'))->name('profil.mot-de-passe');
});

/* ----------------------------------------------------------- Responsable */
Route::prefix('responsable')->name('responsable.')->group(function () use ($simuler) {
    Route::get('/', function () {
        $tous = Maquette::signalements();

        return view('responsable.tableau', [
            'utilisateur' => Maquette::utilisateur('responsable'),
            'indicateurs' => [
                'a_traiter' => $tous->whereIn('statut', ['nouveau', 'a_verifier'])->count(),
                'en_intervention' => $tous->whereIn('statut', ['affecte', 'en_cours'])->count(),
                'a_valider' => $tous->where('statut', 'resolu')->count(),
                'taux_resolution' => 77,
            ],
            'urgents' => $tous->whereIn('priorite', ['critique', 'elevee'])->whereNotIn('statut', ['verifie', 'rejete'])->values(),
            'a_valider' => $tous->where('statut', 'resolu')->values(),
            'nouveaux' => $tous->whereIn('statut', ['nouveau', 'a_verifier'])->values(),
            'carte' => $tous->whereNotIn('statut', ['verifie', 'rejete'])->values(),
        ]);
    })->name('tableau');

    Route::get('/signalements', function (Request $request) {
        $liste = Maquette::signalements()
            ->when($request->statut, fn ($c, $v) => $c->where('statut', $v))
            ->when($request->priorite, fn ($c, $v) => $c->where('priorite', $v))
            ->when($request->categorie, fn ($c, $v) => $c->where('categorie', $v))
            ->when($request->zone, fn ($c, $v) => $c->where('zone', $v))
            ->when($request->q, fn ($c, $v) => $c->filter(fn ($s) => str_contains(mb_strtolower($s->reference.' '.$s->description.' '.$s->commune.' '.$s->repere), mb_strtolower($v))))
            ->sortByDesc('updated_at')->values();

        return view('responsable.signalements.index', [
            'utilisateur' => Maquette::utilisateur('responsable'),
            'signalements' => $liste,
            'total' => Maquette::signalements()->count(),
        ]);
    })->name('signalements.index');

    Route::get('/signalements/{signalement}', fn (int $signalement) => view('responsable.signalements.show', [
        'utilisateur' => Maquette::utilisateur('responsable'),
        'signalement' => Maquette::signalement($signalement),
        'agents' => Maquette::agents(),
    ]))->name('signalements.show');

    Route::post('/signalements/{signalement}/examiner', $simuler('Le dossier est passé « À vérifier ».'))->name('signalements.examiner');
    Route::post('/signalements/{signalement}/valider', $simuler('Signalement validé, priorité enregistrée.'))->name('signalements.valider');
    Route::post('/signalements/{signalement}/priorite', $simuler('Priorité mise à jour.'))->name('signalements.priorite');
    Route::post('/signalements/{signalement}/affecter', $simuler('Mission affectée. L\'agent a été notifié.'))->name('signalements.affecter');
    Route::post('/signalements/{signalement}/rejeter', $simuler('Signalement rejeté. Le citoyen a reçu le motif.'))->name('signalements.rejeter');
    Route::post('/signalements/{signalement}/cloturer', $simuler('Résolution validée : dossier clôturé (Vérifié).'))->name('signalements.cloturer');
    Route::post('/signalements/{signalement}/reprise', $simuler('Reprise demandée : le dossier repasse « En cours ».'))->name('signalements.reprise');

    Route::get('/statistiques', fn () => view('responsable.statistiques', [
        'utilisateur' => Maquette::utilisateur('responsable'),
        'stats' => Maquette::statistiques(),
    ]))->name('statistiques');
});

/* ----------------------------------------------------------------- Agent */
Route::prefix('agent')->name('agent.')->group(function () use ($simuler) {
    Route::get('/', function () {
        $miennes = Maquette::signalements()->filter(fn ($s) => $s->agent?->id === 11);

        return view('agent.missions.index', [
            'utilisateur' => Maquette::utilisateur('agent'),
            'missions' => $miennes->whereIn('statut', ['affecte', 'en_cours'])->sortByDesc('updated_at')->values(),
        ]);
    })->name('missions.index');

    Route::get('/missions/{signalement}', fn (int $signalement) => view('agent.missions.show', [
        'utilisateur' => Maquette::utilisateur('agent'),
        'mission' => Maquette::signalement($signalement),
    ]))->name('missions.show');

    Route::post('/missions/{signalement}/demarrer', $simuler('Mission prise en charge : statut « En cours ».'))->name('missions.demarrer');
    Route::post('/missions/{signalement}/terminer', $simuler('Fin d\'intervention transmise au responsable pour validation.', 'agent.missions.index'))->name('missions.terminer');

    Route::get('/historique', fn () => view('agent.historique', [
        'utilisateur' => Maquette::utilisateur('agent'),
        'missions' => Maquette::signalements()->filter(fn ($s) => $s->agent?->id === 11)->whereIn('statut', ['resolu', 'verifie'])->values(),
    ]))->name('historique');
});
