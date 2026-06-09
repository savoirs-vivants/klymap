<?php

use App\Http\Controllers\MeteoIngestController;
use Illuminate\Support\Facades\Route;

// Ingestion Raspberry Pi — authentifié par Bearer token (RASPBERRY_API_SECRET dans .env)
Route::post('/saisie-meteo', [MeteoIngestController::class, 'store'])->name('api.meteo.ingest');
