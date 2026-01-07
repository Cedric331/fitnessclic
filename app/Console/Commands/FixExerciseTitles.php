<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Exercise;

class FixExerciseTitles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'exercises:fix-titles';

    /**
     * The console command description.
     */
    protected $description = 'Remplace les underscores (_) par des espaces dans les titres des exercices';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Nettoyage des titres des exercices en cours...');

        $updated = 0;

        Exercise::query()
            ->where('title', 'like', '%_%')
            ->chunkById(200, function ($exercises) use (&$updated) {
                foreach ($exercises as $exercise) {
                    $originalTitle = $exercise->title;
                    $cleanTitle = str_replace('_', ' ', $originalTitle);

                    if ($originalTitle !== $cleanTitle) {
                        $exercise->update([
                            'title' => $cleanTitle,
                        ]);

                        $updated++;
                        $this->line("✔ {$originalTitle} → {$cleanTitle}");
                    }
                }
            });

        $this->info("✅ Terminé : {$updated} exercice(s) mis à jour.");

        return Command::SUCCESS;
    }
}
