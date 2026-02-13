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

    public function show(string $slug): Response
    {
        $post = BlogPost::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

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
}


