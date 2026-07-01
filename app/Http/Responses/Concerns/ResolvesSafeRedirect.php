<?php

namespace App\Http\Responses\Concerns;

trait ResolvesSafeRedirect
{
    /**
     * N'autorise que des chemins internes pour éviter les redirections ouvertes
     * (pas d'URL absolue, pas de « // » ni « /\ » interprétables comme un hôte).
     */
    protected function isSafeInternalPath(string $path): bool
    {
        return str_starts_with($path, '/')
            && ! str_starts_with($path, '//')
            && ! str_starts_with($path, '/\\')
            && ! str_contains($path, '\\');
    }
}
