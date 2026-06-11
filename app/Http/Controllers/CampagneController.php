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

        return view('campagnes.index', compact('campagnes'));
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

    public function reouvrir(Campagne $campagne)
    {
        abort_unless($campagne->id_gestionnaire === Auth::id(), 403);

        $campagne->update([
            'date_fin' => null,
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

        SessionParticipant::where('id_session', $campagne->id)->delete();
        $campagne->delete();

        return response()->json(['ok' => true]);
    }

    public function activer(Campagne $campagne)
    {
        abort_unless($campagne->id_gestionnaire === Auth::id() && ! $campagne->isTerminee(), 403);

        session(['active_campagne_id' => $campagne->id]);

        return response()->json(['ok' => true, 'nom' => $campagne->nom]);
    }

    public function desactiver()
    {
        session()->forget('active_campagne_id');

        return response()->json(['ok' => true]);
    }
}
