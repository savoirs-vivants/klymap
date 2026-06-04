<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\CapteurTemoinController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\ParticipantController;
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

    Route::get('/code',               [ParticipantController::class, 'showJoin'])->name('participant.join');
    Route::post('/code/valider',      [ParticipantController::class, 'validateCode'])->name('participant.validateCode');
    Route::post('/session/rejoindre', [ParticipantController::class, 'register'])->name('participant.register');
    Route::post('/session/quitter',   [ParticipantController::class, 'logout'])->name('participant.logout');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn() => view('welcome'))->name('dashboard');
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/capteur-temoins', [CapteurTemoinController::class, 'index'])->name('temoins.index');
        Route::post('/capteur-temoins', [CapteurTemoinController::class, 'store'])->name('temoins.store');
        Route::put('/capteur-temoins/{capteurTemoin}', [CapteurTemoinController::class, 'update'])->name('temoins.update');
        Route::delete('/capteur-temoins/{capteurTemoin}', [CapteurTemoinController::class, 'destroy'])->name('temoins.destroy');
        Route::get('/capteur-temoins/{capteurTemoin}/export', [CapteurTemoinController::class, 'export'])->name('temoins.export');
    });

    Route::get('/campagnes',                      [CampagneController::class, 'index'])->name('campagnes.index');
    Route::post('/campagne',                      [CampagneController::class, 'store'])->name('campagne.store');
    Route::put('/campagnes/{campagne}',           [CampagneController::class, 'update'])->name('campagne.update');
    Route::get('/campagnes/{campagne}/participants', [CampagneController::class, 'participants'])->name('campagne.participants');
    Route::put('/campagnes/{campagne}/terminer', [CampagneController::class, 'terminer'])->name('campagnes.terminer');
    Route::delete('/campagnes/{campagne}',        [CampagneController::class, 'destroy'])->name('campagne.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('backoffice')->name('backoffice.')->group(function () {
    Route::get('/utilisateurs', [BackofficeController::class, 'index'])->name('users');
    Route::post('/utilisateurs', [BackofficeController::class, 'store'])->name('users.store');
    Route::put('/utilisateurs/{user}', [BackofficeController::class, 'update'])->name('users.update');
    Route::delete('/utilisateurs/{user}', [BackofficeController::class, 'destroy'])->name('users.destroy');
});
