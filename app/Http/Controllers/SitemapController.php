<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\CoachProfile;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Génère le sitemap XML dynamique
     */
    public function index(): Response
    {
        // Utiliser l'URL de la requête si config('app.url') n'est pas définie
        $baseUrl = config('app.url') ?: request()->getSchemeAndHttpHost();
        $now = now()->toAtomString();

        $urls = [
            // Page d'accueil
            [
                'loc' => $baseUrl,
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            // Blog
            [
                'loc' => route('blog.index'),
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
            // Annuaire des coachs
            [
                'loc' => route('coachs.index'),
                'lastmod' => $now,
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
            // Pages publiques
            [
                'loc' => route('legal.mentions'),
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('legal.privacy'),
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('legal.terms'),
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
            [
                'loc' => route('legal.cookies'),
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
        ];

        $posts = BlogPost::query()
            ->published()
            ->get(['slug', 'updated_at', 'published_at']);

        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'lastmod' => ($post->updated_at ?? $post->published_at ?? now())->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        $coaches = CoachProfile::query()
            ->published()
            ->get(['slug', 'updated_at', 'published_at']);

        foreach ($coaches as $coach) {
            $urls[] = [
                'loc' => route('coachs.show', $coach->slug),
                'lastmod' => ($coach->updated_at ?? $coach->published_at ?? now())->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'."\n";
        $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\n";
        $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'."\n";
        $xml .= '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8').'</loc>'."\n";
            $xml .= '    <lastmod>'.htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8').'</lastmod>'."\n";
            $xml .= '    <changefreq>'.htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8').'</changefreq>'."\n";
            $xml .= '    <priority>'.htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8').'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
