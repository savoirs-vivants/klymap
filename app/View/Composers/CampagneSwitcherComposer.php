<?php

namespace App\View\Composers;

use App\Models\Campagne;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CampagneSwitcherComposer
{
    public function compose(View $view): void
    {
        $creatorCampagnes = collect();
        $activeCampagneId = null;

        if (Auth::check()) {
            $creatorCampagnes = Campagne::where('id_gestionnaire', Auth::id())
                ->where(function ($q) {
                    $q->whereNull('date_fin')->orWhere('date_fin', '>=', now());
                })
                ->orderBy('nom')
                ->get();

            $activeCampagneId = session('active_campagne_id');

            if ($activeCampagneId && ! $creatorCampagnes->contains('id', $activeCampagneId)) {
                session()->forget('active_campagne_id');
                $activeCampagneId = null;
            }
        }

        $view->with([
            'creatorCampagnes'  => $creatorCampagnes,
            'activeCampagneId'  => $activeCampagneId,
            'activeParticipant' => session('participant'),
        ]);
    }
}
