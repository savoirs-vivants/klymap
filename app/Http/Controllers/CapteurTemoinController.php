<?php

namespace App\Http\Controllers;

use App\Models\CapteurTemoin;
use App\Models\CapteurTemoinMesure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class CapteurTemoinController extends Controller
{
    #[OA\Get(
        path: '/api/capteur-temoins',
        operationId: 'getCapteurTemoins',
        tags: ['Témoins'],
        summary: 'Obtenir la liste des capteurs témoins',
        description: 'Retourne tous les capteurs témoins enregistrés dans la base.',
        responses: [
            new OA\Response(response: 200, description: 'Opération réussie'),
        ]
    )]
    public function index()
    {
        $query = Auth::check()
            ? CapteurTemoin::with('mesures')
            : CapteurTemoin::withCount('mesures');

        $temoins = $query->get()->map(fn ($t) => [
            'id'           => $t->id,
            'name'         => $t->name,
            'lat'           => (float) $t->lat,
            'lng'           => (float) $t->lng,
            'mesures_count' => Auth::check() ? $t->mesures->count() : $t->mesures_count,
        ]);

        return response()->json($temoins);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'lat'     => ['required', 'numeric'],
            'lng'     => ['required', 'numeric'],
            'mesures' => ['required', 'array', 'min:1'],
        ]);

        $temoin = CapteurTemoin::create([
            'user_id' => Auth::id(),
            'name'    => $request->name,
            'lat'     => $request->lat,
            'lng'     => $request->lng,
        ]);

        $this->saveMesures($temoin, $request->mesures);

        return response()->json([
            'id'   => $temoin->id,
            'name' => $temoin->name,
            'lat'  => (float) $temoin->lat,
            'lng'  => (float) $temoin->lng,
            'mesures_count' => count($request->mesures),
        ], 201);
    }

    #[OA\Get(
        path: '/api/capteur-temoins/{capteurTemoin}/mesures',
        operationId: 'getCapteurTemoinMesures',
        tags: ['Témoins'],
        summary: 'Obtenir les mesures d\'un capteur témoin',
        description: 'Retourne le détail d\'un capteur témoin et l\'historique de ses mesures.',
        parameters: [
            new OA\Parameter(
                name: 'capteurTemoin',
                description: 'Identifiant du capteur témoin',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Opération réussie'),
            new OA\Response(response: 404, description: 'Capteur témoin introuvable'),
        ]
    )]
    public function show(CapteurTemoin $capteurTemoin)
    {
        // Lecture publique — autorisation non requise pour consulter les mesures

        $mesures = $capteurTemoin->mesures()->orderBy('enregistre_le')->get()
            ->map(fn ($m) => [
                'enregistre_le' => $m->enregistre_le->format('Y-m-d H:i:s'),
                'sht_temp'      => (float) $m->sht_temp,
                'sht_hum'       => (float) $m->sht_hum,
                'tmp_temp'      => (float) $m->tmp_temp,
            ]);

        return response()->json([
            'id'      => $capteurTemoin->id,
            'name'    => $capteurTemoin->name,
            'mesures' => $mesures,
        ]);
    }

    public function update(Request $request, CapteurTemoin $capteurTemoin)
    {
        $this->authorize($capteurTemoin);

        $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'mesures' => ['nullable', 'array'],
        ]);

        $capteurTemoin->update(['name' => $request->name]);

        if ($request->filled('mesures') && count($request->mesures)) {
            $capteurTemoin->mesures()->delete();
            $this->saveMesures($capteurTemoin, $request->mesures);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(CapteurTemoin $capteurTemoin)
    {
        $this->authorize($capteurTemoin);
        $capteurTemoin->delete();

        return response()->json(['ok' => true]);
    }

    #[OA\Get(
        path: '/api/capteur-temoins/{capteurTemoin}/export',
        operationId: 'exportCapteurTemoin',
        tags: ['Témoins'],
        summary: 'Exporter les mesures d\'un capteur témoin (XLSX)',
        description: 'Télécharge un classeur Excel (.xlsx) contenant les mesures du capteur témoin, exploitable notamment dans QGIS via une jointure sur les coordonnées.',
        parameters: [
            new OA\Parameter(
                name: 'capteurTemoin',
                description: 'Identifiant du capteur témoin',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fichier XLSX généré',
                content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ),
            new OA\Response(response: 403, description: 'Accès non autorisé'),
        ]
    )]
    public function export(CapteurTemoin $capteurTemoin)
    {
        $this->authorize($capteurTemoin);

        $mesures  = $capteurTemoin->mesures()->orderBy('enregistre_le')->get();
        $filename = 'temoin_' . $capteurTemoin->id . '_' . now()->format('Ymd_His') . '.xlsx';

        $xml = $this->buildXlsx($capteurTemoin, $mesures);

        return response($xml, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildXlsx(CapteurTemoin $capteurTemoin, \Illuminate\Support\Collection $mesures): string
    {
        $esc = fn ($v) => htmlspecialchars((string) $v, ENT_XML1);

        $rows  = '';
        $rowNb = 1;

        $meta = [
            ['Témoin',    $capteurTemoin->name],
            ['Latitude',  $capteurTemoin->lat],
            ['Longitude', $capteurTemoin->lng],
            [],
            ['Date / Heure', 'Temp. SHT (°C)', 'Humidité SHT (%)', 'Temp. TMP (°C)'],
        ];

        foreach ($meta as $cols) {
            $cells = '';
            foreach ($cols as $val) {
                $cells .= "<Cell><Data ss:Type=\"String\">{$esc($val)}</Data></Cell>";
            }
            $rows  .= "<Row ss:Index=\"{$rowNb}\">{$cells}</Row>";
            $rowNb++;
        }

        foreach ($mesures as $m) {
            $cells  = "<Cell><Data ss:Type=\"String\">{$esc($m->enregistre_le->format('d/m/Y H:i:s'))}</Data></Cell>";
            $cells .= "<Cell><Data ss:Type=\"Number\">{$esc($m->sht_temp ?? '')}</Data></Cell>";
            $cells .= "<Cell><Data ss:Type=\"Number\">{$esc($m->sht_hum  ?? '')}</Data></Cell>";
            $cells .= "<Cell><Data ss:Type=\"Number\">{$esc($m->tmp_temp ?? '')}</Data></Cell>";
            $rows  .= "<Row>{$cells}</Row>";
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
  <Worksheet ss:Name="Capteur Témoin">
    <Table>{$rows}</Table>
  </Worksheet>
</Workbook>
XML;
    }

    private function authorize(CapteurTemoin $temoin): void
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return;
        }

        abort_if($temoin->user_id !== Auth::id(), 403);
    }

    private function saveMesures(CapteurTemoin $temoin, array $mesures): void
    {
        $rows = array_map(fn ($m) => [
            'capteur_temoin_id' => $temoin->id,
            'enregistre_le'     => Carbon::parse($m['enregistre_le']),
            'sht_temp'          => $m['sht_temp'] ?? null,
            'sht_hum'           => $m['sht_hum']  ?? null,
            'tmp_temp'          => $m['tmp_temp']  ?? null,
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $mesures);

        CapteurTemoinMesure::insert($rows);
    }
}
