<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = BlogPost::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(function (BlogPost $post) {
                return [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'published_at' => optional($post->published_at)->toDateString()
                        ?? optional($post->created_at)->toDateString(),
                    'image_url' => $post->banner_url,
                    'tags' => $post->tags ?? [],
                ];
            });

        return Inertia::render('blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $post = BlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        if ($this->shouldServeShareView($request->userAgent())) {
            $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
            $canonicalUrl = $baseUrl."/blog/{$post->slug}";
            $imageUrl = $post->banner_url ?: '/assets/logo_fitnessclic.png';
            if ($imageUrl && ! preg_match('~^https?://~', $imageUrl)) {
                $imageUrl = $baseUrl.$imageUrl;
            }
            $imageUrl = str_replace('http://', 'https://', $imageUrl);

            return response()->view('blog.share', [
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'imageUrl' => $imageUrl,
                'url' => $canonicalUrl,
                'publishedAt' => optional($post->published_at)->toAtomString(),
            ]);
        }

        return Inertia::render('blog/Show', [
            'post' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
                'excerpt' => $post->excerpt,
                'published_at' => optional($post->published_at)->toDateString()
                    ?? optional($post->created_at)->toDateString(),
                'image_url' => $post->banner_url,
                'tags' => $post->tags ?? [],
            ],
        ]);
    }

    private function shouldServeShareView(?string $userAgent): bool
    {
        if (! $userAgent) {
            return false;
        }

        $botAgents = [
            'facebookexternalhit',
            'facebot',
            'twitterbot',
            'linkedinbot',
            'slackbot',
            'discordbot',
            'whatsapp',
            'telegrambot',
            'pinterest',
            'skypeuripreview',
        ];

        $userAgent = strtolower($userAgent);

        foreach ($botAgents as $bot) {
            if (str_contains($userAgent, $bot)) {
                return true;
            }
        }

        return false;
    }
}


