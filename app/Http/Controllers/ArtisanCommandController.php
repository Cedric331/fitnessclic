<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtisanCommandController extends Controller
{
    /**
     * Exécute la commande stripe:migrate-subscription
     */
    public function migrateSubscription(Request $request)
    {
        // Vérification du token secret
        $token = $request->get('token');
        $expectedToken = config('app.maintenance_token') ?? env('MAINTENANCE_TOKEN');

        if (!$expectedToken || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou manquant',
            ], 403);
        }

        try {
            $output = new BufferedOutput();

            // Récupération des options depuis la requête
            $options = [];
            
            if ($request->has('customer')) {
                $options['--customer'] = $request->get('customer');
            }
            
            if ($request->has('email')) {
                $options['--email'] = $request->get('email');
            }
            
            if ($request->has('user')) {
                $options['--user'] = $request->get('user');
            }
            
            if ($request->has('name')) {
                $options['--name'] = $request->get('name');
            }
            
            if ($request->has('status')) {
                $options['--status'] = $request->get('status');
            }
            
            if ($request->boolean('dry-run')) {
                $options['--dry-run'] = true;
            }

            $exitCode = Artisan::call('stripe:migrate-subscription', $options, $output);

            $outputContent = $output->fetch();

            Log::info('Commande stripe:migrate-subscription exécutée', [
                'options' => $options,
                'exit_code' => $exitCode,
                'output' => $outputContent,
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $outputContent,
            ], $exitCode === 0 ? 200 : 500);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'exécution de stripe:migrate-subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exécute la commande storage:link
     */
    public function storageLink(Request $request)
    {
        // Vérification du token secret
        $token = $request->get('token');
        $expectedToken = config('app.maintenance_token') ?? env('MAINTENANCE_TOKEN');

        if (!$expectedToken || $token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou manquant',
            ], 403);
        }

        try {
            $output = new BufferedOutput();

            $exitCode = Artisan::call('storage:link', [], $output);

            $outputContent = $output->fetch();

            Log::info('Commande storage:link exécutée', [
                'exit_code' => $exitCode,
                'output' => $outputContent,
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $outputContent,
                'message' => $exitCode === 0 
                    ? 'Le lien symbolique a été créé avec succès' 
                    : 'Erreur lors de la création du lien symbolique',
            ], $exitCode === 0 ? 200 : 500);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'exécution de storage:link', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

