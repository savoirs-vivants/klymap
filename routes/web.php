<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\CapteurPointController;
use App\Http\Controllers\CapteurTemoinController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\DonneesCampagnesController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\CapteurController;
use App\Http\Controllers\ComparaisonIcuController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('welcome'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/inscription', [RegisterController::class, 'show'])->name('register');
    Route::post('/inscription', [RegisterController::class, 'store']);

    Route::get('/connexion', [LoginController::class, 'show'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store']);

    Route::get('/mot-de-passe-oublie', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/reinitialiser/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reinitialiser', [ResetPasswordController::class, 'store'])->name('password.update');

});

// Routes participant : accessibles même en étant connecté
Route::get('/code',               [ParticipantController::class, 'showJoin'])->name('participant.join');
Route::post('/code/valider',      [ParticipantController::class, 'validateCode'])->name('participant.validateCode');
Route::post('/session/rejoindre', [ParticipantController::class, 'register'])->name('participant.register');
Route::post('/session/quitter',   [ParticipantController::class, 'logout'])->name('participant.logout');

Route::prefix('api')->name('api.')->group(function () {
    Route::get('/capteur-temoins', [CapteurTemoinController::class, 'index'])->name('temoins.index');
    Route::get('/capteur-temoins/{capteurTemoin}/export', [CapteurTemoinController::class, 'export'])->name('temoins.export');
    Route::get('/capteur-temoins/{capteurTemoin}/mesures', [CapteurTemoinController::class, 'show'])->name('temoins.show');

    Route::get('/capteur-points', [CapteurPointController::class, 'index'])->name('points.index');
    Route::get('/capteur-points/{capteurPoint}', [CapteurPointController::class, 'show'])->name('points.show');

    Route::get('/capteurs-map', [CapteurController::class, 'mapMarkers'])->name('capteurs.map-markers');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn() => view('welcome'))->name('dashboard');
    Route::get('/comparaison-icu', fn() => view('comparaison-icu'))->name('comparaison-icu');
    Route::post('/comparaison-icu/export', [ComparaisonIcuController::class, 'export'])->name('comparaison-icu.export');
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/capteur-temoins', [CapteurTemoinController::class, 'store'])->name('temoins.store');
        Route::put('/capteur-temoins/{capteurTemoin}', [CapteurTemoinController::class, 'update'])->name('temoins.update');
        Route::delete('/capteur-temoins/{capteurTemoin}', [CapteurTemoinController::class, 'destroy'])->name('temoins.destroy');
        Route::post('/capteur-temoins/{capteurTemoin}/image', [CapteurTemoinController::class, 'uploadImage'])->name('temoins.upload-image');
        Route::delete('/capteur-temoins/{capteurTemoin}/image', [CapteurTemoinController::class, 'deleteImage'])->name('temoins.delete-image');

    });

    Route::get('/campagnes',                      [CampagneController::class, 'index'])->name('campagnes.index');
    Route::post('/campagne',                      [CampagneController::class, 'store'])->name('campagne.store');
    Route::put('/campagnes/{campagne}',           [CampagneController::class, 'update'])->name('campagne.update');
    Route::get('/campagnes/{campagne}/participants', [CampagneController::class, 'participants'])->name('campagne.participants');
    Route::put('/campagnes/{campagne}/terminer', [CampagneController::class, 'terminer'])->name('campagnes.terminer');
    Route::put('/campagnes/{campagne}/reouvrir', [CampagneController::class, 'reouvrir'])->name('campagnes.reouvrir');
    Route::delete('/campagnes/{campagne}',        [CampagneController::class, 'destroy'])->name('campagne.destroy');
    Route::post('/campagnes/{campagne}/activer', [CampagneController::class, 'activer'])->name('campagne.activer');
    Route::post('/campagnes/desactiver',         [CampagneController::class, 'desactiver'])->name('campagne.desactiver');

    Route::get('/profil',                   [ProfilController::class, 'profil'])->name('profil');
    Route::get('/profil/modifier',          [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil/modifier',          [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/modifier/password', [ProfilController::class, 'updatePassword'])->name('profil.update-password');

    Route::get('/capteurs', [CapteurController::class, 'index'])->name('capteurs.index');
    Route::get('/capteurs/{id}', [CapteurController::class, 'show'])->name('capteurs.show');
    Route::get('/capteurs/{id}/chart-data', [CapteurController::class, 'chartData'])->name('capteurs.chart-data');
    Route::post('/capteurs', [CapteurController::class, 'store'])->name('capteurs.store');
    Route::post('/capteurs/bluetooth/sync', [CapteurController::class, 'syncBluetooth'])->name('capteurs.bluetooth.sync');
    Route::get('/capteurs/{id}/export', [CapteurController::class, 'export'])->name('capteurs.export');
    Route::post('/api/capteurs/locate', [CapteurController::class, 'locateByDevEui'])->name('capteurs.locate');
});

Route::middleware(['auth', 'admin'])->prefix('backoffice')->name('backoffice.')->group(function () {
    Route::get('/utilisateurs', [BackofficeController::class, 'index'])->name('users');
    Route::post('/utilisateurs', [BackofficeController::class, 'store'])->name('users.store');
    Route::put('/utilisateurs/{user}', [BackofficeController::class, 'update'])->name('users.update');
    Route::delete('/utilisateurs/{user}', [BackofficeController::class, 'destroy'])->name('users.destroy');
});

Route::middleware('auth.participant')->group(function () {
    Route::get('/donnees-campagnes', [DonneesCampagnesController::class, 'index'])->name('donnees-campagnes.index');

    Route::prefix('api')->name('api.participant.')->group(function () {
        Route::post('/capteur-points', [CapteurPointController::class, 'store'])->name('points.store');
        Route::put('/capteur-points/{capteurPoint}', [CapteurPointController::class, 'update'])->name('points.update');
        Route::delete('/capteur-points/{capteurPoint}', [CapteurPointController::class, 'destroy'])->name('points.destroy');
        Route::post('/capteur-points/{capteurPoint}/image', [CapteurPointController::class, 'uploadImage'])->name('points.upload-image');
        Route::delete('/capteur-points/{capteurPoint}/image', [CapteurPointController::class, 'deleteImage'])->name('points.delete-image');
        Route::post('/capteur-points/{capteurPoint}/ack-icu', [CapteurPointController::class, 'ackIcu'])->name('points.ack-icu');
    });
});
