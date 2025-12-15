<?php

namespace App\Http\Controllers;

use App\Models\Session;
use Illuminate\Support\Facades\Storage;

class PublicSessionController extends Controller
{
    /**
     * Afficher une séance publique via son token de partage
     */
    public function show(string $shareToken)
    {
        // S'assurer que les fonctions helper sont chargées
        if (!function_exists('formatRestTime')) {
            require_once app_path('helpers.php');
        }

        $session = Session::where('share_token', $shareToken)
            ->with([
                'user',
                'sessionExercises.exercise',
                'sessionExercises.exercise.media',
                'sessionExercises.sets',
                'layout',
            ])
            ->first();

        if (! $session) {
            return view('sessions.public-error', [
                'message' => 'Cette séance n\'existe plus ou le lien de partage est invalide.',
                'title' => 'Séance introuvable',
            ]);
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

        if (! $session) {
            return response()->view('sessions.public-error', [
                'message' => 'Cette séance n\'existe plus ou le lien de partage est invalide.',
                'title' => 'Séance introuvable',
            ], 404);
        }

        if (! $session->layout) {
            return response()->view('sessions.public-error', [
                'message' => 'Cette séance ne contient pas de mise en page.',
                'title' => 'Mise en page introuvable',
            ], 404);
        }

        $layout = $session->layout;

        // Vérifier que le PDF existe
        if (! $layout->pdf_path || ! Storage::disk('local')->exists($layout->pdf_path)) {
            return response()->view('sessions.public-error', [
                'message' => 'Le document PDF de cette séance n\'est plus disponible.',
                'title' => 'Document introuvable',
            ], 404);
        }

        $pdfContent = Storage::disk('local')->get($layout->pdf_path);

        $fileName = $session->name
            ? \Illuminate\Support\Str::slug($session->name)
            : "seance-{$session->id}";
        $fileName .= '.pdf';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$fileName.'"')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
