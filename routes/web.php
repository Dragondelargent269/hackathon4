<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'accueil')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// Front-end : pages de la maquette (données fictives). À retirer quand le back-end est branché.
if (! app()->isProduction()) {
    require __DIR__.'/maquette.php';
}
