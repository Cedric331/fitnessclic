# Interface de Création de Séances

## Fonctionnalités

### ✅ Implémenté

1. **Interface de création de séance**
   - Formulaire avec sélection de client (optionnel)
   - Nom et prénom de la personne
   - Date de la séance
   - Notes optionnelles

2. **Bibliothèque d'exercices**
   - Recherche en temps réel
   - Filtrage par catégorie
   - Vue en grille ou en liste
   - Ajout d'exercices par clic

3. **Gestion des exercices dans la séance**
   - Configuration pour chaque exercice :
     - Nombre de séries
     - Répétitions
     - Temps de repos
     - Description personnalisée
   - Réorganisation avec boutons haut/bas
   - Suppression d'exercices

4. **Actions**
   - Enregistrement de la séance
   - Effacement de la séance
   - Génération PDF (nécessite package)

### 📦 À installer

#### 1. Drag and Drop (Frontend)

Pour activer le drag and drop complet des exercices :

```bash
npm install @dnd-kit/core @dnd-kit/sortable @dnd-kit/utilities
```

**Intégration nécessaire :**

Après installation, intégrer dans `Create.vue` et `SessionExerciseItem.vue` :

```vue
<script setup>
import { DndContext, closestCenter } from '@dnd-kit/core';
import { SortableContext, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

// Utiliser dans le template avec DndContext et SortableContext
</script>
```

#### 2. Génération PDF (Backend)

```bash
composer require barryvdh/laravel-dompdf
```

Voir `PACKAGES_INSTALLATION.md` pour les détails d'implémentation.

## Structure des fichiers

```
resources/js/pages/sessions/
├── Create.vue              # Page principale de création
├── SessionExerciseItem.vue # Composant pour un exercice dans la séance
├── ExerciseLibrary.vue    # Bibliothèque d'exercices (panneau droit)
├── types.ts               # Types TypeScript
└── README.md              # Ce fichier
```

## Utilisation

1. Accéder à `/sessions/create`
2. Remplir les informations de la séance (optionnel)
3. Rechercher et filtrer les exercices dans la bibliothèque
4. Cliquer sur un exercice pour l'ajouter à la séance
5. Configurer chaque exercice (séries, répétitions, repos, description)
6. Réorganiser avec les boutons haut/bas
7. Enregistrer la séance

## Améliorations futures

- [ ] Drag and drop complet avec @dnd-kit
- [ ] Génération PDF avec template personnalisé
- [ ] Prévisualisation de la séance
- [ ] Templates de séances réutilisables
- [ ] Export en différents formats

