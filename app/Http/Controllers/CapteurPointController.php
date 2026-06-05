<?php

namespace App\Http\Controllers;

use App\Models\CapteurPoint;
use App\Models\CapteurPointMesure;
use App\Models\CapteurTemoin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CapteurPointController extends Controller
{
    public function index()
    {
        $points = CapteurPoint::with(['user', 'capteurTemoin'])
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

        $point = CapteurPoint::create([
            'user_id'           => Auth::id(),
            'capteur_temoin_id' => $request->capteur_temoin_id,
            'name'              => $request->name,
            'lat'               => $request->lat,
            'lng'               => $request->lng,
            'icu_value'         => $request->icu_value,
            'std_dev'           => $request->std_dev,
        ]);

        $this->saveMesures($point, $request->mesures);

        $point->load(['user', 'capteurTemoin']);

        return response()->json($this->pointResource($point), 201);
    }

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
            'name'      => $request->name,
            'icu_value' => $request->icu_value,
            'std_dev'   => $request->std_dev,
        ];
        if ($request->filled('capteur_temoin_id')) {
            $update['capteur_temoin_id'] = $request->capteur_temoin_id;
        }

        $capteurPoint->update($update);

        if ($request->filled('mesures') && count($request->mesures)) {
            $capteurPoint->mesures()->delete();
            $this->saveMesures($capteurPoint, $request->mesures);
        } elseif ($request->has('excluded')) {
            $capteurPoint->mesures()->update(['excluded' => false]);
            if (count($request->excluded)) {
                CapteurPointMesure::whereIn('id', $request->excluded)
                    ->where('capteur_point_id', $capteurPoint->id)
                    ->update(['excluded' => true]);
            }
        }

        $capteurPoint->load(['user', 'capteurTemoin']);

        return response()->json($this->pointResource($capteurPoint));
    }

    public function destroy(CapteurPoint $capteurPoint)
    {
        $this->authorize($capteurPoint);
        $capteurPoint->delete();

        return response()->json(['ok' => true]);
    }

    private function pointResource(CapteurPoint $p): array
    {
        return [
            'id'           => $p->id,
            'name'         => $p->name,
            'lat'          => (float) $p->lat,
            'lng'          => (float) $p->lng,
            'icu_value'    => $p->icu_value !== null ? (float) $p->icu_value : null,
            'std_dev'      => $p->std_dev !== null ? (float) $p->std_dev : null,
            'temoin_name'  => $p->capteurTemoin?->name,
            'temoin_id'    => $p->capteur_temoin_id,
            'user_name'    => $p->user ? "{$p->user->firstname} {$p->user->name}" : '—',
            'mesures_count' => $p->mesures ? $p->mesures->count() : 0,
        ];
    }

    private function authorize(CapteurPoint $point): void
    {
        abort_if($point->user_id !== (int) Auth::id(), 403);
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
}
