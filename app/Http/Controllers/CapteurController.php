<?php

namespace App\Http\Controllers;

use App\Models\Capteur;
use App\Models\Mesure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CapteurController extends Controller
{
    public function index()
    {
        $capteurs = Capteur::with('latestMesure')->get();

        return view('capteurs.index', compact('capteurs'));
    }

    public function mapMarkers(): \Illuminate\Http\JsonResponse
    {
        $capteurs = Capteur::with('latestMesure')
            ->whereNotNull('lat')
            ->whereNotNull('long')
            ->get();

        return response()->json(
            $capteurs->map(fn ($c) => [
                'id'             => $c->id,
                'uid'            => $c->UID,
                'lat'            => (float) $c->lat,
                'lng'            => (float) $c->long,
                'temp'               => $c->latestMesure?->temp,
                'hum'                => $c->latestMesure?->hum,
                'vitesse_vent'       => $c->latestMesure?->vitesse_vent,
                'press_baro'         => $c->latestMesure?->press_baro,
                'pluie'              => $c->latestMesure?->pluie,
                'indice_chaleur'     => $c->latestMesure?->indice_chaleur,
                'debit_pluie'        => $c->latestMesure?->debit_pluie,
                'densite_air'        => $c->latestMesure?->densite_air,
                'evapotranspiration' => $c->latestMesure?->evapotranspiration,
                'updated_at'     => $c->latestMesure?->created_at?->diffForHumans(),
                'show_url'       => route('capteurs.show', $c->id),
            ])
        );
    }

    public function show(int $id)
    {
        $capteur = Capteur::findOrFail($id);

        $mesures = Mesure::where('capteur_id', $id)
            ->latest()
            ->take(50)
            ->get();

        $tableMesures = $mesures->take(10);

        return view('capteurs.show', compact('capteur', 'mesures', 'tableMesures'));
    }

    public function chartData(int $id, Request $request): \Illuminate\Http\JsonResponse
    {
        Capteur::findOrFail($id);

        $limit = min((int) ($request->query('limit', 50)), 2000);

        $query = Mesure::where('capteur_id', $id);

        if ($request->filled('from')) {
            $query->where('created_at', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', Carbon::parse($request->to)->endOfDay());
        }

        // latest() pour prendre les N plus récentes dans la période, puis re-trier pour l'affichage
        $query = $query->latest()->limit($limit)->get()->sortBy('created_at')->values();

        return response()->json([
            'labels'             => $query->map(fn ($m) => $m->created_at->format('d/m H:i')),
            'temp'               => $query->pluck('temp'),
            'hum'                => $query->pluck('hum'),
            'vitesse_vent'       => $query->pluck('vitesse_vent'),
            'press_baro'         => $query->pluck('press_baro'),
            'pluie'              => $query->pluck('pluie'),
            'indice_chaleur'     => $query->pluck('indice_chaleur'),
            'debit_pluie'        => $query->pluck('debit_pluie'),
            'densite_air'        => $query->pluck('densite_air'),
            'evapotranspiration' => $query->pluck('evapotranspiration'),
            'count'              => $query->count(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'UID'       => 'required|string|max:255',
        ]);

        $capteur = Capteur::where('UID', $request->UID)->first();

        if (! $capteur) {
            return back()
                ->withErrors(['capteur' => 'Capteur introuvable. Assurez-vous d\'avoir entré le bon identifiant ou qu\'il a déjà émis ses premières données.'])
                ->withInput();
        }

        $capteur->update([
            'lat'  => $request->latitude,
            'long' => $request->longitude,
        ]);

        return redirect()->route('home')->with('success', 'Le capteur a été localisé avec succès !');
    }

    public function locateByDevEui(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'DevEui' => ['required', 'string'],
            'lat'    => ['required', 'numeric'],
            'long'   => ['required', 'numeric'],
        ]);

        $capteur = Capteur::where('DevEui', $data['DevEui'])->first();

        if (! $capteur) {
            return response()->json(['error' => 'Aucun capteur trouvé avec ce DevEui.'], 404);
        }

        $capteur->update(['lat' => $data['lat'], 'long' => $data['long']]);
        $capteur->load('latestMesure');

        return response()->json([
            'id'             => $capteur->id,
            'uid'            => $capteur->UID,
            'deveui'         => $capteur->DevEui,
            'lat'            => (float) $capteur->lat,
            'lng'            => (float) $capteur->long,
            'temp'               => $capteur->latestMesure?->temp,
            'hum'                => $capteur->latestMesure?->hum,
            'vitesse_vent'       => $capteur->latestMesure?->vitesse_vent,
            'press_baro'         => $capteur->latestMesure?->press_baro,
            'pluie'              => $capteur->latestMesure?->pluie,
            'indice_chaleur'     => $capteur->latestMesure?->indice_chaleur,
            'debit_pluie'        => $capteur->latestMesure?->debit_pluie,
            'densite_air'        => $capteur->latestMesure?->densite_air,
            'evapotranspiration' => $capteur->latestMesure?->evapotranspiration,
            'updated_at'     => $capteur->latestMesure?->created_at?->diffForHumans(),
            'show_url'       => route('capteurs.show', $capteur->id),
        ]);
    }

    public function syncBluetooth(Request $request)
    {
        $request->validate([
            'uid'    => ['required', 'string'],
            'lignes' => ['required', 'array', 'min:1'],
        ]);

        try {
            // firstOrCreate : si le capteur n'existe pas encore, on le crée avec l'UID.
            // lat/long restent null — ils seront renseignés depuis la carte.
            $capteur = Capteur::firstOrCreate(['UID' => $request->uid]);

            // On charge les timestamps déjà présents en BDD pour ce capteur
            // afin de ne pas réinsérer des mesures existantes (distinct côté serveur).
            $existingTs = Mesure::where('capteur_id', $capteur->id)
                ->pluck('created_at')
                ->mapWithKeys(fn ($d) => [Carbon::parse($d)->timestamp => true])
                ->all();

            $toInsert    = [];
            $latestTs    = 0;
            $latestLigne = null;
            $now         = now()->toDateTimeString();

            foreach ($request->lignes as $ligne) {
                $ts = (int) ($ligne['timestamp'] ?? 0);
                if ($ts <= 0) continue;
                if (isset($existingTs[$ts])) continue;

                $date = Carbon::createFromTimestamp($ts)->toDateTimeString();

                $toInsert[] = [
                    'capteur_id'         => $capteur->id,
                    'temp'               => isset($ligne['temp'])               ? (float) $ligne['temp']               : null,
                    'hum'                => isset($ligne['hum'])                ? (float) $ligne['hum']                : null,
                    'vitesse_vent'       => isset($ligne['vitesse_vent'])       ? (float) $ligne['vitesse_vent']       : null,
                    'press_baro'         => isset($ligne['press_baro'])         ? (float) $ligne['press_baro']         : null,
                    'pluie'              => isset($ligne['pluie'])              ? (float) $ligne['pluie']              : null,
                    'indice_chaleur'     => isset($ligne['indice_chaleur'])     ? (float) $ligne['indice_chaleur']     : null,
                    'debit_pluie'        => isset($ligne['debit_pluie'])        ? (float) $ligne['debit_pluie']        : null,
                    'densite_air'        => isset($ligne['densite_air'])        ? (float) $ligne['densite_air']        : null,
                    'evapotranspiration' => isset($ligne['evapotranspiration']) ? (float) $ligne['evapotranspiration'] : null,
                    'created_at'         => $date,
                    'updated_at'         => $now,
                ];

                if ($ts > $latestTs) {
                    $latestTs    = $ts;
                    $latestLigne = $ligne;
                }
            }

            // Insert par chunks de 500 pour éviter les timeouts sur de grands volumes
            foreach (array_chunk($toInsert, 500) as $chunk) {
                Mesure::insert($chunk);
            }

            // Mise à jour du capteur avec les valeurs de la mesure la plus récente
            if ($latestLigne) {
                $capteur->update([
                    'temp'               => isset($latestLigne['temp'])               ? (float) $latestLigne['temp']               : $capteur->getRawOriginal('temp'),
                    'hum'                => isset($latestLigne['hum'])                ? (float) $latestLigne['hum']                : $capteur->getRawOriginal('hum'),
                    'vitesse_vent'       => isset($latestLigne['vitesse_vent'])       ? (float) $latestLigne['vitesse_vent']       : $capteur->getRawOriginal('vitesse_vent'),
                    'press_baro'         => isset($latestLigne['press_baro'])         ? (float) $latestLigne['press_baro']         : $capteur->getRawOriginal('press_baro'),
                    'pluie'              => isset($latestLigne['pluie'])              ? (float) $latestLigne['pluie']              : $capteur->getRawOriginal('pluie'),
                    'indice_chaleur'     => isset($latestLigne['indice_chaleur'])     ? (float) $latestLigne['indice_chaleur']     : $capteur->getRawOriginal('indice_chaleur'),
                    'debit_pluie'        => isset($latestLigne['debit_pluie'])        ? (float) $latestLigne['debit_pluie']        : $capteur->getRawOriginal('debit_pluie'),
                    'densite_air'        => isset($latestLigne['densite_air'])        ? (float) $latestLigne['densite_air']        : $capteur->getRawOriginal('densite_air'),
                    'evapotranspiration' => isset($latestLigne['evapotranspiration']) ? (float) $latestLigne['evapotranspiration'] : $capteur->getRawOriginal('evapotranspiration'),
                ]);
            }

            return response()->json([
                'ok'      => true,
                'inseres' => count($toInsert),
                'ignores' => count($request->lignes) - count($toInsert),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ], 500);
        }
    }

    public function export(int $id)
    {
        $mesuresRaw = \Illuminate\Support\Facades\DB::table('mesures')
            ->where('capteur_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mesures Capteur ' . $id);

        $headers = ['Date & Heure', 'Température (°C)', 'Humidité (%)', 'Vitesse du vent (km/h)', 'Pression (hPa)', 'Pluie (mm)', 'Indice de chaleur (°C)', 'Débit de pluie (mm/h)', 'Densité de l\'air (kg/m³)', 'Évapotranspiration (mm)'];
        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');

        $row = 2;
        foreach ($mesuresRaw as $m) {
            $dateFormatted = $m->created_at ? Carbon::parse($m->created_at)->format('d/m/Y H:i:s') : '';

            $sheet->fromArray([
                $dateFormatted,
                $m->temp ?? null,
                $m->hum ?? null,
                $m->vitesse_vent ?? null,
                $m->press_baro ?? null,
                $m->pluie ?? null,
                $m->indice_chaleur ?? null,
                $m->debit_pluie ?? null,
                $m->densite_air ?? null,
                $m->evapotranspiration ?? null,
            ], NULL, 'A' . $row);

            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Capteur_' . $id . '_Toutes_Les_Mesures_' . date('Ymd_Hi') . '.xlsx';

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
