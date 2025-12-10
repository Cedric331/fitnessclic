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

        $publicUrl = route('public.session.show', ['shareToken' => $this->session->share_token]);

        return new Content(
            view: 'emails.session',
            with: [
                'customerName' => $this->customer->full_name,
                'coachName' => $coachName,
                'sessionName' => $sessionName,
                'sessionDate' => $sessionDate,
                'sessionNotes' => $this->session->notes,
                'publicUrl' => $publicUrl,
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

            if ($originalWidth <= $maxWidth) {
                return $imagePath;
            }

            $newHeight = (int) (($maxWidth / $originalWidth) * $originalHeight);

            $tempPath = storage_path('app/temp/' . uniqid('pdf_img_', true) . '.jpg');
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

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
                    return $imagePath;
            }

            if (!$source) {
                return $imagePath;
            }

            $destination = imagecreatetruecolor($maxWidth, $newHeight);
            
            if ($mimeType === 'image/png') {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
                $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
                imagefilledrectangle($destination, 0, 0, $maxWidth, $newHeight, $transparent);
            }

            imagecopyresampled(
                $destination,
                $source,
                0, 0, 0, 0,
                $maxWidth,
                $newHeight,
                $originalWidth,
                $originalHeight
            );

            imagejpeg($destination, $tempPath, $quality);
            imagedestroy($source);
            imagedestroy($destination);

            return $tempPath;
        } catch (\Exception $e) {
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
        $this->session->load([
            'customers',
            'sessionExercises.exercise.categories',
            'sessionExercises.exercise.media',
            'sessionExercises.sets',
            'user'
        ]);

        $tempImagePaths = [];
        foreach ($this->session->sessionExercises as $sessionExercise) {
            if ($sessionExercise->exercise && $sessionExercise->exercise->media->count() > 0) {
                $firstMedia = $sessionExercise->exercise->media->first();
                if ($firstMedia) {
                    try {
                        $originalPath = $firstMedia->getPath();
                        $optimizedPath = $this->optimizeImageForPdf($originalPath, 150, 50);
                        if ($optimizedPath && $optimizedPath !== $originalPath) {
                            $tempImagePaths[] = $optimizedPath;
                            $sessionExercise->exercise->optimized_image_path = $optimizedPath;
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        try {
            $pdf = Pdf::loadView('sessions.pdf', [
                'session' => $this->session,
                'use_optimized_images' => true,
            ])->setPaper('a4', 'portrait')
              ->setOption('enable-local-file-access', true)
              ->setOption('isHtml5ParserEnabled', true)
              ->setOption('isRemoteEnabled', false)
              ->setOption('dpi', 96);

            $fileName = $this->session->name 
                ? Str::slug($this->session->name) 
                : "seance-{$this->session->id}";
            $fileName .= '.pdf';

            $pdfOutput = $pdf->output();

            $this->cleanupTempImages($tempImagePaths);

            return [
                Attachment::fromData(fn () => $pdfOutput, $fileName)
                    ->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            $this->cleanupTempImages($tempImagePaths);
            throw $e;
        }
    }
}

