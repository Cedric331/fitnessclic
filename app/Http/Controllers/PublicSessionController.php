<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;

class PublicSessionController extends Controller
{
    /**
     * Afficher une séance publique via son token de partage
     */
    public function show(string $shareToken)
    {
        $session = Session::where('share_token', $shareToken)
            ->with([
                'user',
                'sessionExercises.exercise',
                'sessionExercises.exercise.media',
                'sessionExercises.sets',
            ])
            ->first();

        if (!$session) {
            abort(404, 'Séance non trouvée ou lien invalide');
        }

        return view('sessions.public', [
            'session' => $session,
        ]);
    }
}
