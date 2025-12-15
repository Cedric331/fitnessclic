import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    // Optimisations de performance
    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            'vue-router',
            '@vueuse/core',
            'lucide-vue-next',
            'es-errors',
            'es-errors/type',
        ],
        exclude: ['@tailwindcss/vite'],
    },
    server: {
        // Limiter les scans de fichiers pour améliorer les performances
        fs: {
            strict: false,
            allow: ['.', '..'],
        },
        // Augmenter le timeout pour les environnements lents (WSL)
        hmr: {
            overlay: true,
        },
        watch: {
            // Ignorer certains dossiers pour accélérer le watch
            ignored: ['**/node_modules/**', '**/.git/**', '**/vendor/**', '**/storage/**'],
        },
    },
    build: {
        // Optimisations de build
        target: 'esnext',
        minify: 'esbuild',
        sourcemap: false, // Désactiver en dev pour plus de vitesse
        rollupOptions: {
            output: {
                manualChunks: undefined, // Laisser Vite gérer automatiquement
            },
            external: [], // S'assurer que toutes les dépendances sont bundle
        },
        commonjsOptions: {
            include: [/node_modules/],
            transformMixedEsModules: true,
        },
        // S'assurer que les exports conditionnels sont correctement résolus
        modulePreload: {
            polyfill: true,
        },
    },
    resolve: {
        dedupe: ['vue', '@inertiajs/vue3'],
        alias: {
            'es-errors/type': 'es-errors/type.js',
        },
    },
    // Désactiver certaines vérifications en développement
    esbuild: {
        target: 'esnext',
    },
});
