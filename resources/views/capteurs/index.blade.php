<x-layouts.app title="Capteurs">

@php
    $params = [
        ['key' => 'temp',               'label' => 'Température',        'unit' => '°C',     'color' => 'text-orange-500'],
        ['key' => 'hum',                'label' => 'Humidité',           'unit' => '%',      'color' => 'text-blue-500'],
        ['key' => 'vitesse_vent',       'label' => 'Vitesse du vent',    'unit' => 'km/h',   'color' => 'text-cyan-500'],
        ['key' => 'press_baro',         'label' => 'Pression',           'unit' => 'hPa',    'color' => 'text-violet-500'],
        ['key' => 'pluie',              'label' => 'Pluie',              'unit' => 'mm',     'color' => 'text-emerald-500'],
        ['key' => 'indice_chaleur',     'label' => 'Indice de chaleur',  'unit' => '°C',     'color' => 'text-rose-500'],
        ['key' => 'debit_pluie',        'label' => 'Débit de pluie',     'unit' => 'mm/h',   'color' => 'text-sky-500'],
        ['key' => 'densite_air',        'label' => 'Densité de l\'air',  'unit' => 'kg/m³',  'color' => 'text-indigo-500'],
        ['key' => 'evapotranspiration', 'label' => 'Évapotranspiration', 'unit' => 'mm',     'color' => 'text-teal-500'],
    ];
@endphp

<div class="px-4 sm:px-8 py-6">
    <div class="mb-6">
        <h1 class="text-xl font-bold text-[#222a60]">Capteurs</h1>
        <p class="text-sm text-slate-500">Supervision du réseau de stations météo</p>
    </div>

    @if ($capteurs->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-slate-500">Aucun capteur enregistré</p>
            <p class="text-xs text-slate-400 mt-1">Les capteurs apparaîtront ici une fois configurés.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach ($capteurs as $capteur)
            @php $derniere = $capteur->latestMesure; @endphp
            <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_12px_rgba(34,42,96,0.06)] flex flex-col overflow-hidden">

                {{-- En-tête --}}
                <div class="px-5 pt-5 pb-4">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-[#222a60]/8 flex items-center justify-center shrink-0">
                            <svg width="18" height="18" fill="none" stroke="#222a60" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <span class="font-mono text-[10px] font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100 shrink-0">#{{ $capteur->id }}</span>
                    </div>

                    <h2 class="text-base font-bold text-[#222a60] leading-tight mb-0.5 truncate" title="{{ $capteur->UID ?? 'Station #' . $capteur->id }}">
                        {{ $capteur->UID ?? 'Station #' . $capteur->id }}
                    </h2>
                    <p class="font-mono text-[10px] text-slate-400">
                        @if ($capteur->lat !== null && $capteur->long !== null)
                            {{ number_format($capteur->lat, 5) }}, {{ number_format($capteur->long, 5) }}
                        @else
                            Position non renseignée
                        @endif
                    </p>
                    <div class="mt-2">
                        @if ($derniere)
                            <span class="inline-flex items-center gap-1 text-[10px] text-slate-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
                                Actif · {{ $derniere->created_at->diffForHumans() }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[10px] text-amber-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                                Aucune mesure reçue
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Grille des paramètres --}}
                <div class="grid grid-cols-2 gap-px bg-slate-100 border-t border-b border-slate-100 flex-1">
                    @foreach ($params as $p)
                    @php $val = $derniere?->{$p['key']}; @endphp
                    <div class="bg-white px-4 py-3 {{ $loop->last && count($params) % 2 !== 0 ? 'col-span-2' : '' }}">
                        <p class="text-[9px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-1">{{ $p['label'] }}</p>
                        <p class="text-sm font-bold {{ $val !== null ? $p['color'] : 'text-slate-300' }}">
                            {{ $val !== null ? $val : '—' }}
                            @if ($val !== null && $p['unit'] !== '')
                                <span class="text-[10px] font-normal text-slate-400">{{ $p['unit'] }}</span>
                            @endif
                        </p>
                    </div>
                    @endforeach
                </div>

                {{-- Footer --}}
                <a href="{{ route('capteurs.show', $capteur->id) }}"
                    class="flex items-center justify-center gap-2 px-5 py-3.5 text-sm font-semibold text-[#222a60] hover:bg-slate-50 transition-colors no-underline">
                    Voir l'historique
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            @endforeach
        </div>
    @endif
</div>

</x-layouts.app>
