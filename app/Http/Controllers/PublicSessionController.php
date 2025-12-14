<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
                'layout',
            ])
            ->first();

        if (!$session) {
            abort(404, 'Séance non trouvée ou lien invalide');
        }

        // Vérifier si c'est une séance libre (avec layout personnalisé)
        $hasCustomLayout = $session->layout !== null;

        if ($hasCustomLayout) {
            return view('sessions.public-free', [
                'session' => $session,
                'layout' => $session->layout,
            ]);
        }

        return view('sessions.public', [
            'session' => $session,
        ]);
    }

    /**
     * Servir le PDF d'une séance libre publique
     */
    public function pdf(string $shareToken)
    {
        $session = Session::where('share_token', $shareToken)
            ->with('layout')
            ->first();

        if (!$session || !$session->layout) {
            abort(404, 'Séance non trouvée ou lien invalide');
        }

        $layout = $session->layout;

        // Vérifier que le PDF existe
        if (!$layout->pdf_path || !Storage::disk('local')->exists($layout->pdf_path)) {
            abort(404, 'PDF non trouvé');
        }

        $pdfContent = Storage::disk('local')->get($layout->pdf_path);

        $fileName = $session->name 
            ? \Illuminate\Support\Str::slug($session->name) 
            : "seance-{$session->id}";
        $fileName .= '.pdf';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $fileName . '"')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
