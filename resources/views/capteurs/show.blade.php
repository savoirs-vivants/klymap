<x-layouts.app title="Capteur">

@php
    $titreCapteur = $capteur->UID ?? ('Station #' . $capteur->id);
    $kpis = [
        ['key' => 'temp',           'label' => 'Température',     'unit' => '°C',   'color' => 'text-orange-500',  'bg' => 'bg-orange-50',  'border' => 'border-orange-100'],
        ['key' => 'hum',            'label' => 'Humidité',        'unit' => '%',    'color' => 'text-blue-500',    'bg' => 'bg-blue-50',    'border' => 'border-blue-100'],
        ['key' => 'vitesse_vent',   'label' => 'Vitesse du vent', 'unit' => 'km/h', 'color' => 'text-cyan-500',    'bg' => 'bg-cyan-50',    'border' => 'border-cyan-100'],
        ['key' => 'direction_vent', 'label' => 'Direction du vent', 'unit' => '',   'color' => 'text-amber-500',   'bg' => 'bg-amber-50',   'border' => 'border-amber-100'],
        ['key' => 'press_baro',     'label' => 'Pression',        'unit' => 'hPa',  'color' => 'text-violet-500',  'bg' => 'bg-violet-50',  'border' => 'border-violet-100'],
        ['key' => 'pluie',          'label' => 'Pluie',           'unit' => 'mm',   'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-100'],
    ];
    $derniere = $mesures->first();
@endphp

<div class="px-4 sm:px-8 py-6">

    <div class="mb-6">
        <h1 class="text-xl font-bold text-[#222a60]">{{ $titreCapteur }}</h1>
        <p class="text-sm text-slate-500">Capteur #{{ $capteur->id }}</p>
    </div>

    <div id="chart-data" class="hidden"
         data-capteur-id="{{ $capteur->id }}"
         data-chart-url="{{ route('capteurs.chart-data', $capteur->id) }}"
         data-export-url="{{ route('capteurs.export', $capteur->id) }}">
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        @foreach ($kpis as $k)
        @php $val = $derniere?->{$k['key']}; @endphp
        <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_rgba(34,42,96,0.06)] p-4">
            <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-2">{{ $k['label'] }}</p>
            <p class="text-2xl font-bold {{ $val !== null ? $k['color'] : 'text-slate-300' }}">
                {{ $val !== null ? $val : '—' }}
            </p>
            @if ($val !== null && $k['unit'] !== '')
                <p class="text-[10px] text-slate-400 mt-0.5">{{ $k['unit'] }}</p>
            @endif
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_rgba(34,42,96,0.06)] p-4 sm:p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                <div>
                    <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Identifiant</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $titreCapteur }}</p>
                </div>
                <div>
                    <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Coordonnées</p>
                    <p class="font-mono text-xs sm:text-sm text-slate-600">
                        @if ($capteur->lat !== null && $capteur->long !== null)
                            {{ $capteur->lat }}, {{ $capteur->long }}
                        @else
                            Non renseignées
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Mesures</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $mesures->count() }}</p>
                </div>
                @if ($derniere)
                <div>
                    <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">Dernière mesure</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $derniere->created_at->diffForHumans() }}</p>
                </div>
                @endif
            </div>

            <button id="btn-export-excel" title="Exporter les données filtrées en Excel"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg border border-emerald-200 text-xs font-bold transition-colors">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Excel
            </button>
        </div>

        <div class="mt-4 pt-4 border-t border-slate-100">
            <a href="{{ route('capteurs.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors no-underline">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Retour
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_rgba(34,42,96,0.06)] p-6 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-semibold text-slate-700">Évolution des paramètres</h2>

                    <div class="flex items-center gap-1.5 ml-2">
                        <button id="btn-export-png" title="Exporter le graphique en PNG"
                            class="flex items-center gap-1 px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded text-[10px] font-bold transition-colors">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            PNG
                        </button>
                    </div>
                </div>
                <span id="chart-count" class="text-[10px] font-mono text-slate-400"></span>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">Période</p>
                    <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                        @foreach(['1m' => '1 mois', '6m' => '6 mois', '1a' => '1 an', 'custom' => 'Custom'] as $val => $label)
                        <button data-period="{{ $val }}"
                            class="chart-period-btn px-3 py-1.5 rounded-lg text-[11px] font-semibold transition-all
                                   {{ $val === '1m' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>

                <div id="custom-range" class="hidden">
                    <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">Plage personnalisée</p>
                    <div class="flex items-center gap-2">
                        <input type="date" id="chart-from" class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-600 font-mono">
                        <span class="text-slate-400 text-xs">→</span>
                        <input type="date" id="chart-to" class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-600 font-mono">
                        <button id="chart-custom-apply" class="px-3 py-1.5 bg-sv-blue text-white text-[11px] font-semibold rounded-lg hover:opacity-90 transition-opacity">Appliquer</button>
                    </div>
                </div>

                <div>
                    <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1.5">Max. mesures affichées</p>
                    <div class="flex items-center gap-2">
                        <input type="number" id="chart-limit" value="50" min="5" max="2000" step="5"
                            class="w-24 text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-700 font-mono text-center">
                        <button id="chart-limit-apply" class="px-3 py-1.5 bg-slate-700 text-white text-[11px] font-semibold rounded-lg hover:opacity-90 transition-opacity">Appliquer</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative w-full h-72 lg:h-96">
            @if ($mesures->isEmpty())
                <div class="absolute inset-0 flex items-center justify-center text-slate-400 text-sm italic">
                    Aucune donnée historique pour ce capteur.
                </div>
            @else
                <canvas id="capteurChart"></canvas>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_rgba(34,42,96,0.06)] p-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">Historique des mesures</h2>

        @if ($mesures->isEmpty())
            <p class="text-sm text-slate-400 italic text-center py-8">Aucune mesure enregistrée.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 border-b border-slate-100">
                            <th class="pb-3 pr-4">Date & Heure</th>
                            <th class="pb-3 pr-4">Température</th>
                            <th class="pb-3 pr-4">Humidité</th>
                            <th class="pb-3 pr-4">Vent</th>
                            <th class="pb-3 pr-4">Direction</th>
                            <th class="pb-3 pr-4">Pression</th>
                            <th class="pb-3">Pluie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($tableMesures as $m)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 pr-4 text-slate-500 whitespace-nowrap font-mono text-xs">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td class="py-3 pr-4 font-mono font-semibold text-orange-500">{{ $m->temp ?? '—' }} <span class="text-[10px] text-slate-400 font-normal">°C</span></td>
                            <td class="py-3 pr-4 font-mono font-semibold text-blue-500">{{ $m->hum ?? '—' }} <span class="text-[10px] text-slate-400 font-normal">%</span></td>
                            <td class="py-3 pr-4 font-mono text-cyan-600">{{ $m->vitesse_vent ?? '—' }} <span class="text-[10px] text-slate-400 font-normal">km/h</span></td>
                            <td class="py-3 pr-4 font-mono text-amber-600">{{ $m->direction_vent ?? '—' }}</td>
                            <td class="py-3 pr-4 font-mono text-violet-600">{{ $m->press_baro ?? '—' }} <span class="text-[10px] text-slate-400 font-normal">hPa</span></td>
                            <td class="py-3 font-mono text-emerald-600">{{ $m->pluie ?? '—' }} <span class="text-[10px] text-slate-400 font-normal">mm</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

</x-layouts.app>
