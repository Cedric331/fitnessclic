<?php

namespace App\Mail;

use App\Models\Session;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SessionEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Session $session,
        public Customer $customer
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $sessionName = $this->session->name ?: 'Séance d\'entraînement';
        
        return new Envelope(
            subject: "Votre séance d'entraînement : {$sessionName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $coachName = $this->session->user->name ?? 'Votre coach';
        $sessionName = $this->session->name ?: 'Séance d\'entraînement';
        $sessionDate = $this->session->session_date 
            ? $this->session->session_date->format('d/m/Y')
            : 'Non définie';

        return new Content(
            view: 'emails.session',
            with: [
                'customerName' => $this->customer->full_name,
                'coachName' => $coachName,
                'sessionName' => $sessionName,
                'sessionDate' => $sessionDate,
                'sessionNotes' => $this->session->notes,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    /**
     * Optimize image for PDF (resize and compress)
     */
    private function optimizeImageForPdf(string $imagePath, int $maxWidth = 200, int $quality = 60): ?string
    {
        if (!file_exists($imagePath)) {
            return null;
        }

        // Vérifier la taille du fichier (si < 100KB, pas besoin d'optimiser)
        if (filesize($imagePath) < 100 * 1024) {
            return $imagePath;
        }

        try {
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return null;
            }

            $originalWidth = $imageInfo[0];
            $originalHeight = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            // Si l'image est déjà petite, pas besoin de redimensionner
            if ($originalWidth <= $maxWidth) {
                return $imagePath;
            }

            // Calculer la nouvelle hauteur en gardant le ratio
            $newHeight = (int) (($maxWidth / $originalWidth) * $originalHeight);

            // Créer une image temporaire optimisée
            $tempPath = storage_path('app/temp/' . uniqid('pdf_img_', true) . '.jpg');
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Charger l'image selon son type
            switch ($mimeType) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($imagePath);
                    break;
                default:
                    return $imagePath; // Format non supporté, retourner l'original
            }

            if (!$source) {
                return $imagePath;
            }

            // Créer une nouvelle image redimensionnée
            $destination = imagecreatetruecolor($maxWidth, $newHeight);
            
            // Préserver la transparence pour PNG
            if ($mimeType === 'image/png') {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
                $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
                imagefilledrectangle($destination, 0, 0, $maxWidth, $newHeight, $transparent);
            }

            // Redimensionner
            imagecopyresampled(
                $destination,
                $source,
                0, 0, 0, 0,
                $maxWidth,
                $newHeight,
                $originalWidth,
                $originalHeight
            );

            // Sauvegarder en JPEG avec compression
            imagejpeg($destination, $tempPath, $quality);
            imagedestroy($source);
            imagedestroy($destination);

            return $tempPath;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner l'image originale
            return $imagePath;
        }
    }

    /**
     * Clean up temporary optimized images
     */
    private function cleanupTempImages(array $tempPaths): void
    {
        foreach ($tempPaths as $path) {
            if (file_exists($path) && strpos($path, storage_path('app/temp/')) === 0) {
                @unlink($path);
            }
        }
    }

    public function attachments(): array
    {
        // Charger la session avec toutes les relations nécessaires
        $this->session->load([
            'customers',
            'sessionExercises.exercise.categories',
            'sessionExercises.exercise.media',
            'sessionExercises.sets',
            'user'
        ]);

        // Optimiser les images des exercices avant la génération du PDF
        $tempImagePaths = [];
        foreach ($this->session->sessionExercises as $sessionExercise) {
            if ($sessionExercise->exercise && $sessionExercise->exercise->media->count() > 0) {
                $firstMedia = $sessionExercise->exercise->media->first();
                if ($firstMedia) {
                    try {
                        $originalPath = $firstMedia->getPath();
                        $optimizedPath = $this->optimizeImageForPdf($originalPath, 150, 50); // 150px max, qualité 50%
                        if ($optimizedPath && $optimizedPath !== $originalPath) {
                            $tempImagePaths[] = $optimizedPath;
                            // Stocker le chemin optimisé temporairement dans l'exercice
                            $sessionExercise->exercise->optimized_image_path = $optimizedPath;
                        }
                    } catch (\Exception $e) {
                        // Ignorer les erreurs d'optimisation
                    }
                }
            }
        }

        try {
            // Générer le PDF avec des options d'optimisation
            $pdf = Pdf::loadView('sessions.pdf', [
                'session' => $this->session,
                'use_optimized_images' => true,
            ])->setPaper('a4', 'portrait')
              ->setOption('enable-local-file-access', true)
              ->setOption('isHtml5ParserEnabled', true)
              ->setOption('isRemoteEnabled', false)
              ->setOption('dpi', 96); // Réduire la résolution pour réduire la taille

            // Créer un nom de fichier pour le PDF
            $fileName = $this->session->name 
                ? Str::slug($this->session->name) 
                : "seance-{$this->session->id}";
            $fileName .= '.pdf';

            $pdfOutput = $pdf->output();

            // Nettoyer les images temporaires
            $this->cleanupTempImages($tempImagePaths);

            return [
                Attachment::fromData(fn () => $pdfOutput, $fileName)
                    ->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            // Nettoyer les images temporaires en cas d'erreur
            $this->cleanupTempImages($tempImagePaths);
            throw $e;
        }
    }
}

