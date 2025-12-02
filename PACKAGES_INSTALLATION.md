# Packages à installer pour la fonctionnalité de création de séances

## Packages nécessaires

### 1. Drag and Drop - @dnd-kit (Frontend)

Pour le drag and drop des exercices dans la séance, installez les packages suivants :

```bash
npm install @dnd-kit/core @dnd-kit/sortable @dnd-kit/utilities
```

**Documentation :** https://docs.dndkit.com/

**Utilisation :**
- `@dnd-kit/core` : Bibliothèque principale pour le drag and drop
- `@dnd-kit/sortable` : Composants pour les listes triables
- `@dnd-kit/utilities` : Utilitaires pour les calculs de position

### 2. Génération PDF - barryvdh/laravel-dompdf (Backend)

Pour la génération de PDF des séances, installez le package Laravel suivant :

```bash
composer require barryvdh/laravel-dompdf
```

**Documentation :** https://github.com/barryvdh/laravel-dompdf

**Configuration :**

Après l'installation, publiez la configuration (optionnel) :

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

**Utilisation dans le controller :**

Le controller `SessionsController` contient déjà une méthode `pdf()` qui doit être complétée. Voici un exemple d'implémentation :

```php
use PDF;

public function pdf(Session $session)
{
    if ($session->user_id !== Auth::id()) {
        abort(403);
    }

    $session->load(['customer', 'exercises.categories', 'exercises.media', 'user']);

    $pdf = \PDF::loadView('sessions.pdf', [
        'session' => $session,
    ]);
    
    return $pdf->download("seance-{$session->id}.pdf");
}
```

**Création de la vue PDF :**

Créez le fichier `resources/views/sessions/pdf.blade.php` pour le template PDF de la séance.

## Notes importantes

1. **Drag and Drop** : Les composants Vue sont déjà préparés pour utiliser `@dnd-kit`. Il faudra intégrer les hooks dans `SessionExerciseItem.vue` et `Create.vue` après l'installation.

2. **PDF** : La méthode `pdf()` dans `SessionsController` est prête mais nécessite l'installation du package et la création de la vue Blade pour le template PDF.

3. **Compatibilité** : Ces packages sont compatibles avec Laravel 11+ et Vue 3.

