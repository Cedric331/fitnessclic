<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AnnouncementsController extends Controller
{
    /**
     * Get the current active announcement that the user hasn't seen yet.
     */
    public function current(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['announcement' => null]);
        }

        $announcement = Announcement::getUnseenFor($user);

        if (! $announcement) {
            return response()->json(['announcement' => null]);
        }

        return response()->json([
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'description' => $announcement->description,
                'image_url' => $announcement->image_url,
                'published_at' => $announcement->published_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Mark an announcement as seen by the current user.
     */
    public function markAsSeen(Announcement $announcement): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $announcement->markAsSeenBy($user);

        return response()->json(['success' => true]);
    }
}

