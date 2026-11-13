<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Responsable\TableauController;
use App\Http\Controllers\Agent\MissionController;
use 
App\Http\Controllers\Citoyen\SignalementController as CitoyenSignalementController;
use App\Http\Controllers\Responsable\SignalementController as ResponsableSignalementController;
use App\Http\Controllers\Citoyen\ProfilController;
use App\Http\Controllers\Citoyen\AbonnementController;
use App\Http\Controllers\Citoyen\CarteController as CitoyenCarteController;
use App\Http\Controllers\Citoyen\PaiementController as CitoyenPaiementController;
use App\Http\Controllers\Responsable\CarteController as ResponsableCarteController;
use App\Http\Controllers\Responsable\PaiementController as ResponsablePaiementController;
use App\Http\Controllers\Agent\CollecteController;
use App\Http\Controllers\Agent\RecuController;

Route::view('/', 'accueil')->name('home');

// Après connexion, Fortify envoie sur /dashboard : on redirige selon le rôle
Route::get('dashboard', function () {
    return redirect()->route(match (auth()->user()->role) {
        'responsable' => 'responsable.tableau',
        'agent' => 'agent.missions.index',
        default => 'citoyen.signalements.index',
    });
})->middleware('auth')->name('dashboard');

// Noms utilisés par les vues de l'équipe
Route::redirect('/connexion', '/login')->name('connexion');
Route::redirect('/inscription', '/register')->name('inscription');
/* --------------------------------------------------------------- Citoyen */
Route::middleware(['auth', 'role:citoyen'])->prefix('citoyen')->name('citoyen.')->group(function () {
    Route::get('/', [CitoyenSignalementController::class, 'index'])->name('signalements.index');
    Route::get('/signalements/nouveau', [CitoyenSignalementController::class, 'create'])->name('signalements.create');
    Route::post('/signalements', [CitoyenSignalementController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('signalements.store');
    Route::get('/signalements/{signalement}', [CitoyenSignalementController::class, 'show'])->name('signalements.show');
        Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/notifications', [ProfilController::class, 'notifications'])->name('profil.notifications');
    Route::put('/profil/mot-de-passe', [ProfilController::class, 'motDePasse'])->name('profil.mot-de-passe');
});
/* ----------------------------------------------------------- Responsable */
Route::middleware(['auth', 'role:responsable'])->prefix('responsable')->name('responsable.')->group(function () {
    Route::get('/signalements', [ResponsableSignalementController::class, 'index'])->name('signalements.index');
    Route::get('/signalements/{signalement}', [ResponsableSignalementController::class, 'show'])->name('signalements.show');
         Route::get('/', [TableauController::class, 'tableau'])->name('tableau');
    Route::get('/statistiques', [TableauController::class, 'statistiques'])->name('statistiques');
    Route::controller(ResponsableSignalementController::class)
        ->prefix('/signalements/{signalement}')
        ->name('signalements.')
        ->group(function () {
            Route::post('/examiner', 'examiner')->name('examiner');
            Route::post('/valider', 'valider')->name('valider');
            Route::post('/priorite', 'priorite')->name('priorite');
            Route::post('/affecter', 'affecter')->name('affecter');
            Route::post('/rejeter', 'rejeter')->name('rejeter');
            Route::post('/cloturer', 'cloturer')->name('cloturer');
            Route::post('/reprise', 'reprise')->name('reprise');
        });
     
});
   /* ----------------------------------------------------------------- Agent */
Route::middleware(['auth', 'role:agent'])->prefix('agent')->name('agent.')->controller(MissionController::class)->group(function () {
    Route::get('/', 'index')->name('missions.index');
    Route::get('/missions/{signalement}', 'show')->name('missions.show');
    Route::post('/missions/{signalement}/demarrer', 'demarrer')->name('missions.demarrer');
    Route::post('/missions/{signalement}/terminer', 'terminer')->name('missions.terminer');
    Route::get('/historique', 'historique')->name('historique');
});
/* =====================================================================
   Carte interactive + collecte à domicile (abonnements, paiements, reçus)
   ===================================================================== */
Route::middleware(['auth', 'role:citoyen'])->prefix('citoyen')->name('citoyen.')->group(function () {
    Route::get('/carte', [CitoyenCarteController::class, 'index'])->name('carte');

    Route::controller(AbonnementController::class)->prefix('collecte')->group(function () {
        Route::get('/', 'index')->name('abonnements.index');
        Route::get('/formules/{formule:slug}', 'create')->name('abonnements.create');
        Route::post('/formules/{formule:slug}', 'store')->middleware('throttle:10,1')->name('abonnements.store');
        Route::get('/abonnements/{abonnement}', 'show')->name('abonnements.show');
        Route::post('/abonnements/{abonnement}/payer', 'payer')->middleware('throttle:10,1')->name('abonnements.payer');
        Route::post('/abonnements/{abonnement}/collectes', 'demanderCollecte')->name('collectes.store');
        Route::post('/collectes/{collecte}/annuler', 'annulerCollecte')->name('collectes.annuler');
    });

    Route::controller(CitoyenPaiementController::class)->prefix('collecte')->group(function () {
        Route::get('/paiements', 'index')->name('paiements.index');
        Route::get('/recus/{paiement:reference}', 'show')->name('paiements.show');
        Route::post('/recus/{paiement:reference}/annuler', 'annuler')->name('paiements.annuler');
    });
});

Route::middleware(['auth', 'role:responsable'])->prefix('responsable')->name('responsable.')->group(function () {
    Route::get('/carte', [ResponsableCarteController::class, 'index'])->name('carte');
    Route::get('/paiements', [ResponsablePaiementController::class, 'index'])->name('paiements.index');
    Route::post('/paiements/{paiement}/confirmer', [ResponsablePaiementController::class, 'confirmer'])->name('paiements.confirmer');
    Route::post('/paiements/{paiement}/refuser', [ResponsablePaiementController::class, 'refuser'])->name('paiements.refuser');
});

Route::middleware(['auth', 'role:agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/collectes', [CollecteController::class, 'index'])->name('collectes.index');
    Route::get('/collectes/effectuees', [CollecteController::class, 'historique'])->name('collectes.historique');
    Route::get('/collectes/{collecte}', [CollecteController::class, 'show'])->name('collectes.show');
    Route::post('/collectes/{collecte}/confirmer', [CollecteController::class, 'confirmer'])->name('collectes.confirmer');
    Route::get('/recus', [RecuController::class, 'index'])->name('recus.index');
    Route::get('/recus/{reference}', [RecuController::class, 'show'])->name('recus.show');
    Route::post('/recus/{reference}/collecter', [RecuController::class, 'collecter'])->name('recus.collecter');
});

require __DIR__.'/settings.php';


