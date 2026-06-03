<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('welcome'))->name('dashboard');
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'admin'])->prefix('backoffice')->name('backoffice.')->group(function () {
    Route::get('/utilisateurs', [BackofficeController::class, 'index'])->name('users');
    Route::post('/utilisateurs', [BackofficeController::class, 'store'])->name('users.store');
    Route::put('/utilisateurs/{user}', [BackofficeController::class, 'update'])->name('users.update');
    Route::delete('/utilisateurs/{user}', [BackofficeController::class, 'destroy'])->name('users.destroy');
});
