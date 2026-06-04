<?php

namespace App\Http\Controllers;

use App\Models\Analyse;
use App\Models\Campagne;
use App\Models\CoursDEau;
use App\Models\SessionParticipant;
use Illuminate\Support\Facades\DB;

class ParticipantController extends Controller
{
    public function showJoin()
    {
        if (session()->has('participant')) {
            return redirect()->route('participant.analyses');
        }
        return view('participant.join');
    }

    public function validateCode(\Illuminate\Http\Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $campagne = Campagne::where('code', strtoupper(trim($request->code)))->first();

        if (! $campagne) {
            return response()->json(['error' => 'Code invalide. Vérifiez et réessayez.'], 404);
        }

        if ($campagne->date_fin && $campagne->date_fin->isPast()) {
            return response()->json(['error' => 'Cette campagne est terminée (date de fin dépassée).'], 403);
        }

        return response()->json([
            'campagne_id' => $campagne->id,
            'nom'         => $campagne->nom,
            'nb_groupes'  => $campagne->nb_groupes,
        ]);
    }

    public function register(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'campagne_id' => 'required|integer|exists:campagnes,id',
            'pseudo'      => 'required|string|max:100',
            'id_groupe'   => 'required|integer|min:0',
        ]);

        $campagne = Campagne::findOrFail($request->campagne_id);

        $participant = SessionParticipant::create([
            'id_session' => $campagne->id,
            'pseudo'     => trim($request->pseudo),
            'id_groupe'  => $request->id_groupe,
        ]);

        session([
            'participant' => [
                'id'           => $participant->id,
                'pseudo'       => $participant->pseudo,
                'id_groupe'    => $participant->id_groupe,
                'id_session'   => $campagne->id,
                'campagne_nom' => $campagne->nom,
                'nb_groupes'   => $campagne->nb_groupes,
            ],
        ]);

        return response()->json(['redirect' => route('participant.analyses')]);
    }

    public function logout()
    {
        session()->forget('participant');
        return redirect('/');
    }
}
