<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Responsable\TableauController;
use App\Http\Controllers\Agent\MissionController;
use 
App\Http\Controllers\Citoyen\SignalementController as CitoyenSignalementController;
use App\Http\Controllers\Responsable\SignalementController as ResponsableSignalementController;
use App\Http\Controllers\Citoyen\ProfilController;

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
require __DIR__.'/settings.php';


