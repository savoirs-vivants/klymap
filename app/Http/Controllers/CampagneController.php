<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\SessionParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampagneController extends Controller
{
    public function index()
    {
        $campagnes = Campagne::where('id_gestionnaire', Auth::id())
            ->withCount('participants')
            ->orderByDesc('created_at')
            ->get();

        return view('desktop.campagnes.index', compact('campagnes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'        => 'required|string|max:255',
            'nb_groupes' => 'required|integer|min:0|max:26',
            'date_fin'   => 'nullable|date|after:today',
        ]);

        $campagne = Campagne::create([
            'nom'             => $request->nom,
            'id_gestionnaire' => Auth::id(),
            'nb_groupes'      => $request->nb_groupes,
            'date_fin'        => $request->date_fin,
        ]);

        return response()->json([
            'code'       => $campagne->code,
            'nom'        => $campagne->nom,
            'nb_groupes' => $campagne->nb_groupes,
            'date_fin'   => $campagne->date_fin?->format('d/m/Y') ?? 'Aucune',
        ]);
    }

    public function update(Request $request, Campagne $campagne)
    {
        abort_unless($campagne->id_gestionnaire === Auth::id(), 403);

        $request->validate([
            'nom'        => 'required|string|max:255',
            'nb_groupes' => 'required|integer|min:0|max:26',
            'date_fin'   => 'nullable|date',
        ]);

        $campagne->update([
            'nom'        => $request->nom,
            'nb_groupes' => $request->nb_groupes,
            'date_fin'   => $request->date_fin,
        ]);

        return response()->json(['ok' => true, 'campagne' => [
            'id'         => $campagne->id,
            'nom'        => $campagne->nom,
            'nb_groupes' => $campagne->nb_groupes,
            'date_fin'   => $campagne->date_fin?->format('d/m/Y') ?? 'Aucune',
        ]]);
    }

    public function terminer(Campagne $campagne)
    {
        abort_unless($campagne->id_gestionnaire === Auth::id(), 403);

        $campagne->update([
            'date_fin' => now()
        ]);

        return response()->json(['ok' => true]);
    }

    public function participants(Campagne $campagne)
    {
        abort_unless($campagne->id_gestionnaire === Auth::id(), 403);

        $participants = $campagne->participants()
            ->select('id', 'pseudo', 'id_groupe', 'created_at')
            ->orderBy('id_groupe')
            ->orderBy('pseudo')
            ->get()
            ->map(fn($p) => [
                'id'         => $p->id,
                'pseudo'     => $p->pseudo,
                'id_groupe'  => $p->id_groupe,
                'groupe_label' => $p->id_groupe > 0 ? 'Groupe ' . chr(64 + $p->id_groupe) : '—',
                'joined_at'  => $p->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json($participants);
    }

    public function destroy(Campagne $campagne)
    {
        abort_unless($campagne->id_gestionnaire === Auth::id(), 403);

        // Supprime les participants puis la campagne
        SessionParticipant::where('id_session', $campagne->id)->delete();
        $campagne->delete();

        return response()->json(['ok' => true]);
    }

    public function resultats()
    {
        $query = Campagne::with(['participants.analyses.point.coursDEau'])
            ->orderByDesc('created_at');

        $isAdmin = Auth::user()->role === 'admin';

        if (!$isAdmin) {
            $query->where('id_gestionnaire', Auth::id());
        }

        $campagnes = $query->get()
            ->map(function ($campagne) {
                $groupes = $campagne->participants->groupBy('id_groupe')->map(function ($participants, $idGroupe) {
                    $analyses = $participants->flatMap->analyses;

                    $points = collect();
                    if ($analyses->isNotEmpty()) {
                        $points = $analyses->groupBy('point_id')->map(function ($analysesPoint) use ($participants) {
                            $pt = $analysesPoint->first()->point;

                            return [
                                'id'        => $pt->id,
                                'latitude'  => (float) $pt->latitude,
                                'longitude' => (float) $pt->longitude,
                                'ville'     => $pt->ville ?? 'Point GPS',
                                'analyses'  => $analysesPoint->sortByDesc('created_at')->map(function ($a) use ($participants) {
                                    $participant = $participants->firstWhere('id', $a->participant_id);
                                    $mesures = is_string($a->mesures) ? json_decode($a->mesures, true) : ($a->mesures ?? []);

                                    $saisiPar = trim(($participant->prenom ?? '') . ' ' . ($participant->nom ?? ''));
                                    if (empty($saisiPar)) $saisiPar = $participant->pseudo ?? 'Inconnu';

                                    return [
                                        'id'         => $a->id,
                                        'type'       => $a->type,
                                        'qualite'    => $a->qualite,
                                        'date'       => $a->created_at?->translatedFormat('d M Y'),
                                        'time'       => $a->created_at?->format('H:i'),
                                        'created_at' => $a->created_at?->toISOString(),
                                        'image'      => $a->image ? asset('storage/' . $a->image) : null,
                                        'note'       => $mesures['note'] ?? null,
                                        'bandelette' => $mesures['bandelette'] ?? null,
                                        'photometre' => $mesures['photometre'] ?? null,
                                        'saisi_par'  => $saisiPar,
                                    ];
                                })->values(),
                            ];
                        })->values();
                    }

                    $qualiteCounts = $analyses->countBy('qualite')->toArray();

                    return [
                        'id_groupe'       => $idGroupe,
                        'label'           => $idGroupe > 0 ? 'Groupe ' . chr(64 + $idGroupe) : 'Individuel',
                        'total_analyses'  => $analyses->count(),
                        'total_points'    => $points->count(),
                        'qualite_counts'  => $qualiteCounts,
                        'qualite_globale' => empty($qualiteCounts) ? 'non_evalue' : $this->calculerQualiteGlobale($qualiteCounts),
                        'points'          => $points,
                    ];
                })->values();

                return [
                    'id'      => $campagne->id,
                    'nom'     => $campagne->nom,
                    'code'    => $campagne->code,
                    'is_mine' => $campagne->id_gestionnaire === Auth::id(),
                    'groupes' => $groupes,
                ];
            })->values();

        return view('desktop.campagnes.resultats', compact('campagnes', 'isAdmin'));
    }

    private function calculerQualiteGlobale(array $counts): string
    {
        if (empty($counts)) return 'non_evalue';

        $valid = ['tres_bon', 'bon', 'passable', 'mediocre', 'mauvais'];
        $best = 'tres_bon';
        $max = 0;
        foreach ($valid as $q) {
            if (($counts[$q] ?? 0) > $max) {
                $max = $counts[$q];
                $best = $q;
            }
        }
        return $best;
    }
}
