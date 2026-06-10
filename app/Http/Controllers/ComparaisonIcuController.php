<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ComparaisonIcuController extends Controller
{
    public function export(Request $request)
    {
        $data = $request->validate([
            'conditions'              => ['required', 'array', 'min:1'],
            'conditions.*.name'       => ['required', 'string'],
            'conditions.*.points'     => ['required', 'array', 'min:1'],
            'conditions.*.points.*.name'        => ['nullable', 'string'],
            'conditions.*.points.*.temoin_name' => ['nullable', 'string'],
            'conditions.*.points.*.date'        => ['nullable', 'string'],
            'conditions.*.points.*.icu_value'   => ['nullable', 'numeric'],
            'conditions.*.points.*.std_dev'     => ['nullable', 'numeric'],
            'conditions.*.points.*.lat'         => ['nullable', 'numeric'],
            'conditions.*.points.*.lng'         => ['nullable', 'numeric'],
            'conditions.*.points.*.user_name'   => ['nullable', 'string'],
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $headers = ['Point', 'Zone', 'Date', 'ICU (°C)', 'Écart type point', 'Latitude', 'Longitude', 'Relevé par'];

        // Feuille de résumé
        $summary = $spreadsheet->createSheet();
        $summary->setTitle('Résumé');
        $summary->fromArray(['Condition', 'Nb points', 'ICU moyen (°C)', 'Écart type'], NULL, 'A1');
        $summary->getStyle('A1:D1')->getFont()->setBold(true);
        $summary->getStyle('A1:D1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');

        $row = 2;
        foreach ($data['conditions'] as $cond) {
            $values = array_map(fn($p) => (float) ($p['icu_value'] ?? 0), $cond['points']);
            $n      = count($values);
            $mean   = $n ? array_sum($values) / $n : 0;
            $std    = $n ? sqrt(array_sum(array_map(fn($v) => ($v - $mean) ** 2, $values)) / $n) : 0;

            $summary->fromArray([$cond['name'], $n, round($mean, 2), round($std, 2)], NULL, 'A' . $row);
            $row++;
        }
        foreach (range('A', 'D') as $col) {
            $summary->getColumnDimension($col)->setAutoSize(true);
        }

        // Une feuille par condition
        foreach ($data['conditions'] as $cond) {
            $sheet = $spreadsheet->createSheet();
            $title = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $cond['name']);
            $sheet->setTitle(mb_substr($title ?: 'Condition', 0, 31));

            $sheet->fromArray($headers, NULL, 'A1');
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
            $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E2E8F0');

            $row = 2;
            foreach ($cond['points'] as $p) {
                $sheet->fromArray([
                    $p['name'] ?? '',
                    $p['temoin_name'] ?? '',
                    $p['date'] ?? '',
                    $p['icu_value'] ?? null,
                    $p['std_dev'] ?? null,
                    $p['lat'] ?? null,
                    $p['lng'] ?? null,
                    $p['user_name'] ?? '',
                ], NULL, 'A' . $row);
                $row++;
            }

            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = 'Comparaison_ICU_' . date('Ymd_Hi') . '.xlsx';

        if (ob_get_length()) {
            ob_end_clean();
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
