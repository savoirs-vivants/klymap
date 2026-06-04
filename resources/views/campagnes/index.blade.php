<x-layouts.app title="Mes Campagnes">

    <div class="max-w-5xl mx-auto">

        @if ($campagnes->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8 sm:p-12 text-center mx-4 sm:mx-0">
                <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <p class="text-base font-bold text-slate-600">Aucune campagne créée</p>
                <p class="text-sm text-slate-400 mt-1">Lancez votre première campagne depuis le tableau de bord.</p>
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center gap-2 mt-5 w-full sm:w-auto px-5 py-2.5 rounded-xl bg-[#222a60] text-white text-sm font-semibold hover:bg-[#1a2050] transition-colors">
                    Aller au tableau de bord
                </a>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <p class="text-sm font-bold text-slate-700">{{ $campagnes->count() }}
                        campagne{{ $campagnes->count() > 1 ? 's' : '' }}</p>
                </div>
                <div class="divide-y divide-slate-50">
                    @foreach ($campagnes as $c)
                        @php
                            $expired = $c->date_fin && $c->date_fin->isPast();
                        @endphp
                        <div class="px-4 sm:px-6 py-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4"
                            data-campagne-id="{{ $c->id }}">

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <p class="campagne-nom-display text-base font-black text-[#222a60] truncate">
                                        {{ $c->nom }}</p>
                                    <span
                                        class="font-mono text-xs font-bold tracking-widest text-slate-400 bg-slate-100 px-2 py-0.5 rounded-lg shrink-0">{{ $c->code }}</span>
                                    @if ($expired)
                                        <span
                                            class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-red-50 text-red-500 border border-red-100 shrink-0">Terminée</span>
                                    @else
                                        <span
                                            class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 shrink-0">Active</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3 sm:gap-4 mt-2 flex-wrap">
                                    <span class="text-[11px] text-slate-400 font-mono flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ $c->nb_groupes > 0 ? $c->nb_groupes . ' groupe' . ($c->nb_groupes > 1 ? 's' : '') : 'Individuel' }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-mono flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Fin : {{ $c->date_fin?->format('d/m/y') ?? '—' }}
                                    </span>
                                    <span
                                        class="text-[11px] text-slate-400 font-mono flex items-center gap-1 campagne-nb-participants">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ $c->participants_count }} part.
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 mt-2 lg:mt-0 shrink-0">

                                <button onclick="showParticipants({{ $c->id }}, '{{ addslashes($c->nom) }}')"
                                    class="flex-1 sm:flex-none justify-center flex items-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors">
                                    Participants
                                </button>

                                @if (!$expired)
                                    <button onclick="endCampagne({{ $c->id }}, '{{ addslashes($c->nom) }}')"
                                        class="flex-1 sm:flex-none justify-center flex items-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-xl bg-orange-50 hover:bg-orange-100 text-orange-600 text-xs font-semibold transition-colors">
                                        Terminer
                                    </button>
                                @endif

                                <button
                                    onclick="openEditModal({{ $c->id }}, '{{ addslashes($c->nom) }}', {{ $c->nb_groupes }}, '{{ $c->date_fin?->format('Y-m-d') ?? '' }}')"
                                    class="flex-1 sm:flex-none justify-center flex items-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-xl bg-[#222a60]/8 hover:bg-[#222a60]/15 text-[#222a60] text-xs font-semibold transition-colors">
                                    Modifier
                                </button>

                                <button onclick="deleteCampagne({{ $c->id }}, '{{ addslashes($c->nom) }}')"
                                    class="flex-1 sm:flex-none justify-center flex items-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-500 text-xs font-semibold transition-colors">
                                    Supprimer
                                </button>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    <div id="modal-edit" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h3 class="text-base font-black text-[#222a60]">Modifier la campagne</h3>
                <button onclick="closeEditModal()"
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="form-edit" class="p-6 space-y-4">
                <input type="hidden" id="edit-id">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nom de la campagne</label>
                    <input id="edit-nom" type="text" required maxlength="255"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de groupes <span
                            class="font-normal text-slate-400">(0 = individuel)</span></label>
                    <input id="edit-nb-groupes" type="number" min="0" max="26" required
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Date de fin</label>
                    <input id="edit-date-fin" type="date"
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#222a60]/25 focus:border-[#222a60] transition-all">
                </div>
                <p id="edit-error" class="text-sm text-red-500 font-medium"></p>
                <button type="submit"
                    class="w-full py-3 rounded-xl bg-[#222a60] text-white font-bold text-sm hover:bg-[#1a2050] transition-colors">
                    Enregistrer
                </button>
            </form>
        </div>
    </div>

    <div id="modal-participants" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeParticipantsModal()"></div>
        <div
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10 overflow-hidden max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                <div>
                    <h3 class="text-base font-black text-[#222a60]">Participants</h3>
                    <p id="participants-campagne-nom" class="text-xs text-slate-400 mt-0.5 truncate max-w-[200px]">
                    </p>
                </div>
                <button onclick="closeParticipantsModal()"
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="participants-list" class="flex-1 overflow-y-auto p-4">
                <div class="flex items-center justify-center py-8">
                    <div class="flex gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                            style="animation-delay:0s"></span>
                        <span class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                            style="animation-delay:.15s"></span>
                        <span class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                            style="animation-delay:.3s"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-layouts.app>

<script>
    window.__csrfToken = document.querySelector('meta[name="csrf-token"]').content;
</script>
