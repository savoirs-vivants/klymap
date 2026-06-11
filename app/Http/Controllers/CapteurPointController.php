<?php

namespace App\Http\Controllers;

use App\Models\CapteurPoint;
use App\Models\CapteurPointMesure;
use App\Models\CapteurTemoin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class CapteurPointController extends Controller
{
    #[OA\Get(
        path: '/api/capteur-points',
        operationId: 'getCapteurPoints',
        tags: ['Points'],
        summary: 'Obtenir la liste des points de mesure',
        description: 'Retourne tous les points de mesure (capteurs mobiles) avec leurs coordonnées, exploitable pour un export géographique vers QGIS.',
        responses: [
            new OA\Response(response: 200, description: 'Opération réussie'),
        ]
    )]
    public function index()
    {
        $points = CapteurPoint::with(['user', 'capteurTemoin', 'participant'])
            ->get()
            ->map(fn($p) => $this->pointResource($p));

        return response()->json($points);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'              => ['required', 'string', 'max:100'],
            'lat'               => ['required', 'numeric'],
            'lng'               => ['required', 'numeric'],
            'capteur_temoin_id' => ['required', 'exists:capteur_temoins,id'],
            'mesures'           => ['required', 'array', 'min:1'],
            'icu_value'         => ['nullable', 'numeric'],
            'std_dev'           => ['nullable', 'numeric'],
        ]);

        $participant = session('participant');

        $sessionId = $participant
            ? ($participant['id_session'] ?? null)
            : session('active_campagne_id');

        $point = CapteurPoint::create([
            'user_id'           => Auth::id(),
            'capteur_temoin_id' => $request->capteur_temoin_id,
            'name'              => $request->name,
            'lat'               => $request->lat,
            'lng'               => $request->lng,
            'icu_value'         => $request->icu_value,
            'std_dev'           => $request->std_dev,
            'night_overrides'   => $request->night_overrides ?? null,
            'session_id'        => $sessionId,
            'participant_id'    => $participant['id'] ?? null,
        ]);

        $this->saveMesures($point, $request->mesures);
        $this->updateDate($point);

        $point->load(['user', 'capteurTemoin', 'participant']);

        return response()->json($this->pointResource($point), 201);
    }

    #[OA\Get(
        path: '/api/capteur-points/{capteurPoint}',
        operationId: 'getCapteurPoint',
        tags: ['Points'],
        summary: 'Obtenir le détail d\'un point de mesure',
        description: 'Retourne un point de mesure (coordonnées, métadonnées) et l\'historique de ses mesures.',
        parameters: [
            new OA\Parameter(
                name: 'capteurPoint',
                description: 'Identifiant du point de mesure',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Opération réussie'),
            new OA\Response(response: 404, description: 'Point introuvable'),
        ]
    )]
    public function show(CapteurPoint $capteurPoint)
    {
        $capteurPoint->load(['user', 'capteurTemoin', 'mesures' => fn($q) => $q->orderBy('enregistre_le')]);

        return response()->json([
            ...$this->pointResource($capteurPoint),
            'mesures' => $capteurPoint->mesures->map(fn($m) => [
                'id'           => $m->id,
                'enregistre_le' => $m->enregistre_le->format('Y-m-d H:i:s'),
                'sht_temp'     => (float) $m->sht_temp,
                'sht_hum'      => (float) $m->sht_hum,
                'tmp_temp'     => (float) $m->tmp_temp,
                'excluded'     => $m->excluded,
            ]),
        ]);
    }

    public function update(Request $request, CapteurPoint $capteurPoint)
    {
        $this->authorize($capteurPoint);

        $request->validate([
            'name'              => ['required', 'string', 'max:100'],
            'capteur_temoin_id' => ['nullable', 'exists:capteur_temoins,id'],
            'mesures'           => ['nullable', 'array'],
            'icu_value'         => ['nullable', 'numeric'],
            'std_dev'           => ['nullable', 'numeric'],
            'excluded'          => ['nullable', 'array'],
        ]);

        $update = [
            'name'            => $request->name,
            'icu_value'       => $request->icu_value,
            'std_dev'         => $request->std_dev,
            'night_overrides' => $request->has('night_overrides') ? $request->night_overrides : $capteurPoint->night_overrides,
            'icu_ack'         => (float) $request->std_dev === (float) $capteurPoint->std_dev ? $capteurPoint->icu_ack : false,
        ];
        if ($request->filled('capteur_temoin_id')) {
            $update['capteur_temoin_id'] = $request->capteur_temoin_id;
        }

        $capteurPoint->update($update);

        if ($request->filled('mesures') && count($request->mesures)) {
            $capteurPoint->mesures()->delete();
            $this->saveMesures($capteurPoint, $request->mesures);
            $this->updateDate($capteurPoint);
        } elseif ($request->has('excluded')) {
            $capteurPoint->mesures()->update(['excluded' => false]);
            if (count($request->excluded)) {
                CapteurPointMesure::whereIn('id', $request->excluded)
                    ->where('capteur_point_id', $capteurPoint->id)
                    ->update(['excluded' => true]);
            }
        }

        $capteurPoint->load(['user', 'capteurTemoin', 'participant']);

        return response()->json($this->pointResource($capteurPoint));
    }

    public function ackIcu(CapteurPoint $capteurPoint)
    {
        $this->authorize($capteurPoint);

        $capteurPoint->update(['icu_ack' => true]);

        return response()->json(['ok' => true]);
    }

    public function destroy(CapteurPoint $capteurPoint)
    {
        $this->authorize($capteurPoint);
        $capteurPoint->delete();

        return response()->json(['ok' => true]);
    }

    public function uploadImage(Request $request, CapteurPoint $capteurPoint)
    {
        $this->authorize($capteurPoint);

        $request->validate(['image' => ['required', 'image', 'max:4096']]);

        if ($capteurPoint->image) {
            Storage::disk('public')->delete($capteurPoint->image);
        }

        $path = $request->file('image')->store('capteur-points', 'public');
        $capteurPoint->update(['image' => $path]);

        return response()->json(['image_url' => asset('storage/' . $path)]);
    }

    private function pointResource(CapteurPoint $p): array
    {
        return [
            'id'             => $p->id,
            'user_id'        => $p->user_id,
            'name'           => $p->name,
            'date'           => $p->date?->format('Y-m-d'),
            'image_url'      => $p->image ? asset('storage/' . $p->image) : null,
            'night_overrides' => $p->night_overrides ?? [],
            'lat'            => (float) $p->lat,
            'lng'            => (float) $p->lng,
            'icu_value'      => $p->icu_value !== null ? (float) $p->icu_value : null,
            'std_dev'        => $p->std_dev !== null ? (float) $p->std_dev : null,
            'icu_ack'        => $p->icu_ack,
            'temoin_name'    => $p->capteurTemoin?->name,
            'temoin_id'      => $p->capteur_temoin_id,
            'user_name'      => $p->user
                ? "{$p->user->firstname} {$p->user->name}"
                : ($p->participant ? $p->participant->pseudo : '—'),
            'mesures_count'  => $p->mesures ? $p->mesures->count() : 0,
            'session_id'     => $p->session_id,
            'participant_id' => $p->participant_id,
        ];
    }

    private function authorize(CapteurPoint $point): void
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return;
        }

        $userId      = Auth::id();
        $participant = session('participant');

        $isOwner = ($userId && $point->user_id === (int) $userId)
            || ($participant && $point->participant_id === $participant['id']);

        abort_unless($isOwner, 403);
    }

    private function saveMesures(CapteurPoint $point, array $mesures): void
    {
        $rows = array_map(fn($m) => [
            'capteur_point_id' => $point->id,
            'enregistre_le'    => Carbon::parse($m['enregistre_le']),
            'sht_temp'         => $m['sht_temp'] ?? null,
            'sht_hum'          => $m['sht_hum']  ?? null,
            'tmp_temp'         => $m['tmp_temp']  ?? null,
            'excluded'         => $m['excluded']  ?? false,
            'created_at'       => now(),
            'updated_at'       => now(),
        ], $mesures);

        CapteurPointMesure::insert($rows);
    }

    private function updateDate(CapteurPoint $point): void
    {
        $first = $point->mesures()->min('enregistre_le');

        $point->update(['date' => $first ? Carbon::parse($first)->toDateString() : null]);
    }
}
