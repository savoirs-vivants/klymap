@php
    $groupes = $campagne->participants->groupBy('id_groupe')->sortKeys();
    $nbPoints = $campagne->participants->sum(fn($p) => $p->capteurPoints->count());
@endphp

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm mb-4 overflow-hidden">
    {{-- Header campagne --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 bg-slate-50 border-b border-slate-100">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-slate-900">{{ $campagne->nom }}</h2>
                @if($campagne->isTerminee())
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-slate-200 text-slate-500 rounded-full">Terminée</span>
                @else
                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-teal-100 text-teal-700 rounded-full">Active</span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
                Code&nbsp;: <span class="font-mono font-bold text-slate-600">{{ $campagne->code }}</span>
                @if($showGestionnaire) · Par <strong>{{ $campagne->gestionnaire?->firstname }} {{ $campagne->gestionnaire?->name }}</strong> @endif
                · {{ $campagne->participants->count() }} participant(s) · {{ $nbPoints }} analyse(s)
                @if($campagne->date_fin) · Fin&nbsp;: {{ $campagne->date_fin->format('d/m/Y') }} @endif
            </p>
        </div>
    </div>

    {{-- Groupes --}}
    @if($groupes->isEmpty())
        <p class="px-5 py-4 text-sm text-slate-400 italic">Aucun participant pour l'instant.</p>
    @else
        @foreach($groupes as $idGroupe => $participants)
        @php $groupeLabel = $idGroupe > 0 ? 'Groupe ' . chr(64 + $idGroupe) : 'Mode individuel'; @endphp
        <div class="border-b border-slate-50 last:border-0">
            <div class="px-5 py-3 bg-white">
                <h3 class="text-xs font-bold uppercase tracking-wider text-teal-600 mb-3">{{ $groupeLabel }}</h3>

                @foreach($participants as $participant)
                    @if($participant->capteurPoints->isEmpty())
                        <div class="flex items-center gap-2 py-1.5 text-slate-400 text-sm italic">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $participant->pseudo }} — aucune analyse
                        </div>
                    @else
                        <div class="mb-3">
                            <p class="text-xs font-semibold text-slate-500 mb-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $participant->pseudo }}
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 ml-5">
                                @foreach($participant->capteurPoints as $point)
                                @php
                                    $color = match(true) {
                                        $point->icu_value === null => '#94a3b8',
                                        $point->icu_value < 0.5  => '#1e3a8a',
                                        $point->icu_value < 1.0  => '#3b82f6',
                                        $point->icu_value < 1.5  => '#f472b6',
                                        $point->icu_value < 2.0  => '#f97316',
                                        default                  => '#ef4444',
                                    };
                                @endphp
                                <button
                                    onclick="window.openPointDetail({{ $point->id }})"
                                    class="flex items-center gap-2.5 px-3 py-2.5 bg-slate-50 hover:bg-teal-50 border border-slate-100 hover:border-teal-200 rounded-xl transition-all text-left group"
                                >
                                    <div class="w-3.5 h-3.5 rounded-full shrink-0 ring-1 ring-black/10" style="background:{{ $color }}"></div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-slate-800 truncate group-hover:text-teal-700">{{ $point->name }}</p>
                                        <p class="text-[10px] text-slate-400">
                                            @if($point->icu_value !== null)
                                                ICU : <span class="font-bold" style="color:{{ $color }}">{{ $point->icu_value }} °C</span>
                                                @if($point->std_dev !== null) ± {{ $point->std_dev }} °C @endif
                                            @else
                                                ICU non calculé
                                            @endif
                                        </p>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-slate-300 group-hover:text-teal-500 ml-auto shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endforeach
    @endif
</div>
