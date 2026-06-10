<?php

namespace App\Console\Commands;

use App\Models\CapteurPoint;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RecalculateIcuValues extends Command
{
    protected $signature = 'icu:recalculate';

    protected $description = "Recalcule l'ICU et l'écart-type de tous les points de mesure en se basant sur tmp_temp (au lieu de sht_temp)";

    public function handle(): int
    {
        $points = CapteurPoint::with(['mesures', 'capteurTemoin.mesures'])
            ->whereNotNull('capteur_temoin_id')
            ->get();

        $count = 0;

        foreach ($points as $point) {
            $temoin = $point->capteurTemoin;

            if (! $temoin || $temoin->mesures->isEmpty()) {
                continue;
            }

            $pointMesures = $point->mesures->where('excluded', false)->values();

            [$icu, $std] = $this->calculate($pointMesures, $temoin->mesures, $point->night_overrides ?? []);

            $point->update([
                'icu_value' => $icu,
                'std_dev'   => $std,
            ]);

            $count++;
        }

        $this->info("{$count} point(s) de mesure mis à jour.");

        return self::SUCCESS;
    }

    private function isNightWindow($enregistreLe): bool
    {
        $h = $enregistreLe->hour;

        return $h >= 2 && $h < 8;
    }

    private function groupByNight(Collection $mesures): array
    {
        $groups = [];

        foreach ($mesures as $m) {
            if ($this->isNightWindow($m->enregistre_le)) {
                $groups[$m->enregistre_le->format('Y-m-d')][] = $m;
            }
        }

        return $groups;
    }

    private function calculate(Collection $pointMesures, Collection $temoinMesures, array $nightOverrides): array
    {
        $pointNights  = $this->groupByNight($pointMesures);
        $temoinNights = $this->groupByNight($temoinMesures);

        $nights = collect(array_keys($pointNights))
            ->merge(array_keys($temoinNights))
            ->unique()
            ->sort()
            ->values();

        $diffs = [];

        foreach ($nights as $night) {
            $pN = $pointNights[$night] ?? [];
            $tN = $temoinNights[$night] ?? [];

            if (! $pN || ! $tN) {
                continue;
            }

            $maxHumP = max(array_map(fn ($m) => (float) $m->sht_hum, $pN));
            if ($maxHumP > 95) {
                continue;
            }

            if (count($pN) < 3 || count($tN) < 3) {
                continue;
            }

            $override = collect($nightOverrides)->firstWhere('night', $night);

            if ($override) {
                $pAvg = (float) $override['pAvg'];
                $tAvg = (float) $override['tAvg'];
            } else {
                $p3 = collect($pN)->sortBy(fn ($m) => (float) $m->tmp_temp)->take(3);
                $t3 = collect($tN)->sortBy(fn ($m) => (float) $m->tmp_temp)->take(3);

                $pAvg = $p3->avg(fn ($m) => (float) $m->tmp_temp);
                $tAvg = $t3->avg(fn ($m) => (float) $m->tmp_temp);
            }

            $diffs[] = $pAvg - $tAvg;
        }

        if (! $diffs) {
            return [null, null];
        }

        $globalIcu = round(array_sum($diffs) / count($diffs), 2);
        $globalStd = round($this->stdDev($diffs), 2);

        return [$globalIcu, $globalStd];
    }

    private function stdDev(array $values): float
    {
        $n = count($values);

        if ($n < 2) {
            return 0.0;
        }

        $mean  = array_sum($values) / $n;
        $sumSq = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values));

        return sqrt($sumSq / ($n - 1));
    }
}
