<?php

namespace App\Http\Controllers;

use App\Models\CapteurMeteo;
use App\Models\Mesure;
use Illuminate\Http\Request;

class MeteoIngestController extends Controller
{
    public function store(Request $request)
    {
        // Authentification par Bearer token
        $token = $request->bearerToken();
        if (!$token || $token !== env('RASPBERRY_API_SECRET')) {
            return response()->json(['error' => 'Non autorisé'], 401);
        }

        $data = $request->validate([
            'deveui'             => ['required', 'string'],
            'lat'                => ['nullable', 'numeric'],
            'long'               => ['nullable', 'numeric'],
            'temp'               => ['nullable', 'numeric'],
            'hum'                => ['nullable', 'numeric'],
            'vitesse_vent'       => ['nullable', 'numeric'],
            'press_baro'         => ['nullable', 'numeric'],
            'pluie'              => ['nullable', 'numeric'],
            'indice_chaleur'     => ['nullable', 'numeric'],
            'debit_pluie'        => ['nullable', 'numeric'],
            'densite_air'        => ['nullable', 'numeric'],
            'evapotranspiration' => ['nullable', 'numeric'],
            'timestamp'          => ['nullable', 'date'],
        ]);

        // Trouver ou créer le capteur via DevEui
        $capteur = CapteurMeteo::firstOrCreate(
            ['DevEui' => $data['deveui']],
            ['lat' => $data['lat'] ?? null, 'long' => $data['long'] ?? null]
        );

        // Mettre à jour les dernières valeurs sur le capteur
        $capteur->update([
            'lat'                => $data['lat']                ?? $capteur->lat,
            'long'               => $data['long']               ?? $capteur->long,
            'temp'               => $data['temp']               ?? $capteur->temp,
            'hum'                => $data['hum']                ?? $capteur->hum,
            'vitesse_vent'       => $data['vitesse_vent']       ?? $capteur->vitesse_vent,
            'press_baro'         => $data['press_baro']         ?? $capteur->press_baro,
            'pluie'              => $data['pluie']              ?? $capteur->pluie,
            'indice_chaleur'     => $data['indice_chaleur']     ?? $capteur->indice_chaleur,
            'debit_pluie'        => $data['debit_pluie']        ?? $capteur->debit_pluie,
            'densite_air'        => $data['densite_air']        ?? $capteur->densite_air,
            'evapotranspiration' => $data['evapotranspiration'] ?? $capteur->evapotranspiration,
        ]);

        // Enregistrer la mesure horodatée
        $mesure = new Mesure([
            'capteur_id'         => $capteur->id,
            'temp'               => $data['temp']               ?? null,
            'hum'                => $data['hum']                ?? null,
            'vitesse_vent'       => $data['vitesse_vent']       ?? null,
            'press_baro'         => $data['press_baro']         ?? null,
            'pluie'              => $data['pluie']              ?? null,
            'indice_chaleur'     => $data['indice_chaleur']     ?? null,
            'debit_pluie'        => $data['debit_pluie']        ?? null,
            'densite_air'        => $data['densite_air']        ?? null,
            'evapotranspiration' => $data['evapotranspiration'] ?? null,
        ]);

        // Utiliser le timestamp envoyé par la Raspberry si présent
        if (!empty($data['timestamp'])) {
            $mesure->created_at = $data['timestamp'];
            $mesure->updated_at = $data['timestamp'];
        }

        $mesure->save();

        return response()->json([
            'ok'         => true,
            'capteur_id' => $capteur->id,
            'mesure_id'  => $mesure->id,
        ], 201);
    }
}
