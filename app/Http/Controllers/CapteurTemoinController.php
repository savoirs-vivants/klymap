<?php

namespace App\Http\Controllers;

use App\Models\CapteurTemoin;
use App\Models\CapteurTemoinMesure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            'image_url'     => $t->image ? asset('storage/' . $t->image) : null,
            'date'          => $t->date?->format('Y-m-d'),
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
        $this->updateDate($temoin);

        return response()->json([
            'id'   => $temoin->id,
            'name' => $temoin->name,
            'image_url' => $temoin->image ? asset('storage/' . $temoin->image) : null,
            'date'      => $temoin->date?->format('Y-m-d'),
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
            'image_url' => $capteurTemoin->image ? asset('storage/' . $capteurTemoin->image) : null,
            'date'      => $capteurTemoin->date?->format('Y-m-d'),
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
            $this->updateDate($capteurTemoin);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(CapteurTemoin $capteurTemoin)
    {
        $this->authorize($capteurTemoin);
        $capteurTemoin->delete();

        return response()->json(['ok' => true]);
    }

    public function uploadImage(Request $request, CapteurTemoin $capteurTemoin)
    {
        $this->authorize($capteurTemoin);

        $request->validate(['image' => ['required', 'image', 'max:4096']]);

        if ($capteurTemoin->image) {
            Storage::disk('public')->delete($capteurTemoin->image);
        }

        $path = $request->file('image')->store('capteur-temoins', 'public');
        $capteurTemoin->update(['image' => $path]);

        return response()->json(['image_url' => asset('storage/' . $path)]);
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

        $spreadsheet = $this->buildXlsx($capteurTemoin, $mesures);

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildXlsx(CapteurTemoin $capteurTemoin, \Illuminate\Support\Collection $mesures): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Capteur Témoin');

        // ── Bloc d'informations ──────────────────────────────────────────────
        $infos = [
            ['Capteur Témoin', $capteurTemoin->name ?? '—'],
            ['Latitude',       $capteurTemoin->lat   ?? '—'],
            ['Longitude',      $capteurTemoin->lng   ?? '—'],
            ['Nb mesures',     $mesures->count()],
            ['Export le',      now()->format('d/m/Y H:i')],
        ];

        $row = 1;
        foreach ($infos as [$label, $value]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}")->getFont()->setColor(
                (new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB('64748b')
            );
            $row++;
        }

        $row++; // ligne vide de séparation

        // ── En-têtes du tableau ──────────────────────────────────────────────
        $headerRow = $row;
        $headers = ['Date & Heure', 'Temp. SHT (°C)', 'Humidité SHT (%)', 'Temp. TMP (°C)'];
        $sheet->fromArray($headers, null, "A{$headerRow}");

        $headerRange = "A{$headerRow}:D{$headerRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F3460']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row++;

        // ── Données ──────────────────────────────────────────────────────────
        $firstDataRow = $row;

        foreach ($mesures as $m) {
            $sheet->setCellValue("A{$row}", $m->enregistre_le->format('d/m/Y H:i:s'));
            $sheet->setCellValue("B{$row}", $m->sht_temp);
            $sheet->setCellValue("C{$row}", $m->sht_hum);
            $sheet->setCellValue("D{$row}", $m->tmp_temp);

            // Zébrage une ligne sur deux
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:D{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            $sheet->getStyle("A{$row}")->getFont()->setColor(
                (new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB('64748B')
            );
            $sheet->getStyle("B{$row}:D{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // ── Bordure autour du tableau complet ────────────────────────────────
        if ($row > $firstDataRow) {
            $tableRange = "A{$headerRow}:D" . ($row - 1);
            $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->applyFromArray([
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => 'E2E8F0'],
            ]);
        }

        // ── Largeurs de colonnes ─────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(16);

        // Figer la ligne d'en-têtes pour faciliter le défilement
        $sheet->freezePane('A' . ($headerRow + 1));

        return $spreadsheet;
    }

    private function authorize(CapteurTemoin $temoin): void
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
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

    private function updateDate(CapteurTemoin $temoin): void
    {
        $first = $temoin->mesures()->min('enregistre_le');

        $temoin->update(['date' => $first ? Carbon::parse($first)->toDateString() : null]);
    }
}
