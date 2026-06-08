<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use App\Models\SessionParticipant;
use Illuminate\Support\Facades\Auth;

class DonneesCampagnesController extends Controller
{
    public function index()
    {
        $user        = Auth::user();
        $participant = session('participant');

        $mesCampagnes    = collect();
        $toutesCampagnes = null;

        if ($user) {
            $mesCampagnes = Campagne::where('id_gestionnaire', $user->id)
                ->with([
                    'participants' => fn ($q) => $q->with([
                        'capteurPoints' => fn ($q) => $q->with('capteurTemoin')->orderBy('created_at'),
                    ])->orderBy('id_groupe')->orderBy('pseudo'),
                ])
                ->orderByDesc('created_at')
                ->get();

            if ($user->role === 'admin') {
                $toutesCampagnes = Campagne::with([
                    'gestionnaire:id,firstname,name',
                    'participants' => fn ($q) => $q->with([
                        'capteurPoints' => fn ($q) => $q->with('capteurTemoin')->orderBy('created_at'),
                    ])->orderBy('id_groupe')->orderBy('pseudo'),
                ])
                ->where('id_gestionnaire', '!=', $user->id)
                ->orderByDesc('created_at')
                ->get();
            }
        } elseif ($participant) {
            // Participant : voit seulement la campagne à laquelle il participe
            $campagne = Campagne::with([
                'participants' => fn ($q) => $q->with([
                    'capteurPoints' => fn ($q) => $q->with('capteurTemoin')->orderBy('created_at'),
                ])->orderBy('id_groupe')->orderBy('pseudo'),
            ])->find($participant['id_session']);

            if ($campagne) {
                $mesCampagnes = collect([$campagne]);
            }
        }

        return view('campagnes.donnees', compact('mesCampagnes', 'toutesCampagnes'));
    }
}
