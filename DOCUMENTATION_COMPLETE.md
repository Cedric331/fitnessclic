# Documentation Complète - FitnessClic

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Technologies utilisées](#technologies-utilisées)
3. [Architecture](#architecture)
4. [Fonctionnalités](#fonctionnalités)
5. [Sécurité](#sécurité)
6. [SEO (Search Engine Optimization)](#seo-search-engine-optimization)
7. [Performance](#performance)
8. [Idées de features futures](#idées-de-features-futures)

---

## Vue d'ensemble

**FitnessClic** est une application web moderne conçue pour les professionnels du sport (coachs, entraîneurs personnels) leur permettant de créer, gérer et partager des séances d'entraînement personnalisées pour leurs clients.

### Objectif principal
Permettre aux coachs sportifs de :
- Créer rapidement des séances d'entraînement personnalisées
- Gérer une bibliothèque d'exercices
- Organiser leurs clients
- Partager des séances via des liens sécurisés
- Générer des PDFs professionnels
- Gérer des abonnements via Stripe

---

## Technologies utilisées

### Backend

#### Framework principal
- **Laravel 12.0** - Framework PHP moderne et robuste
- **PHP 8.2+** - Langage de programmation backend

#### Authentification & Sécurité
- **Laravel Fortify** (v1.30) - Système d'authentification complet
  - Inscription/Connexion
  - Vérification d'email
  - Réinitialisation de mot de passe
  - Authentification à deux facteurs (2FA)
- **Laravel Cashier** (v16.1) - Intégration Stripe pour les abonnements

#### Administration
- **Filament 4.0** - Panel d'administration moderne
- **Spatie Media Library** (v11.17) - Gestion des médias (images d'exercices)

#### Génération de documents
- **Laravel DomPDF** (v3.1) - Génération de PDFs pour les séances

#### Routing
- **Laravel Wayfinder** (v0.1.9) - Système de routing avancé

### Frontend

#### Framework JavaScript
- **Vue.js 3.5.13** - Framework JavaScript réactif
- **TypeScript 5.2.2** - Typage statique pour JavaScript
- **Inertia.js 2.0** - Bridge entre Laravel et Vue (SPA sans API)

#### Build Tools
- **Vite 7.0.4** - Build tool moderne et rapide
- **Tailwind CSS 4.1.1** - Framework CSS utility-first
- **PostCSS** - Traitement CSS

#### Bibliothèques UI
- **Reka UI 2.6.0** - Composants UI accessibles
- **Lucide Vue Next** (v0.468.0) - Icônes modernes
- **VueUse Core** (v12.8.2) - Utilitaires Vue composables

#### Fonctionnalités interactives
- **@dnd-kit** (v6.3.1) - Drag & drop pour réorganiser les exercices
- **Konva** (v10.0.12) - Canvas 2D pour l'éditeur de mise en page
- **Vue Draggable Plus** (v0.6.0) - Drag & drop supplémentaire

#### Notifications
- **@kyvg/vue3-notification** (v3.4.2) - Système de notifications toast

### Base de données
- **SQLite** (par défaut) - Base de données légère pour le développement
- Support **MySQL/PostgreSQL** - Configurable via variables d'environnement

### Paiements
- **Stripe** - Système de paiement et gestion d'abonnements
  - Webhooks pour synchronisation
  - Portail client Stripe intégré
  - Gestion des abonnements récurrents

### Outils de développement
- **Pest PHP** (v4.1) - Framework de tests PHP moderne
- **Laravel Pint** (v1.24) - Code formatter
- **ESLint** (v9.17.0) - Linter JavaScript
- **Prettier** (v3.4.2) - Formatter de code
- **TypeScript ESLint** (v8.23.0) - Linter TypeScript

### Infrastructure
- **Docker Compose** - Containerisation (optionnel)
- **Laravel Sail** (v1.41) - Environnement de développement Docker

---

## Architecture

### Architecture générale
L'application suit une architecture **monolithique moderne** avec séparation claire entre :
- **Backend** : Laravel (API + logique métier)
- **Frontend** : Vue.js 3 avec Inertia.js (SPA)
- **Base de données** : Relations Eloquent ORM

### Pattern architectural
- **MVC** (Model-View-Controller) côté Laravel
- **Composants Vue** réutilisables
- **Composables** pour la logique réutilisable
- **Form Requests** pour la validation
- **Policies** pour l'autorisation (à implémenter)

### Structure des dossiers

```
app/
├── Actions/          # Actions Fortify (création utilisateur, etc.)
├── Console/          # Commandes Artisan
├── Enums/            # Énumérations (UserRole, BlockType)
├── Filament/         # Panel d'administration Filament
├── Http/
│   ├── Controllers/  # Contrôleurs
│   ├── Middleware/   # Middleware personnalisés
│   └── Requests/     # Form Requests (validation)
├── Mail/             # Classes d'emails
├── Models/           # Modèles Eloquent
└── Providers/        # Service Providers

resources/
├── js/
│   ├── components/   # Composants Vue réutilisables
│   ├── composables/  # Composables Vue
│   ├── layouts/      # Layouts Vue
│   ├── pages/        # Pages Inertia
│   └── types/        # Types TypeScript
└── views/            # Templates Blade

database/
├── migrations/       # Migrations de base de données
├── factories/        # Factories pour les tests
└── seeders/          # Seeders pour données de test
```

### Flux de données
1. **Requête HTTP** → Route Laravel
2. **Middleware** → Authentification, CSRF, etc.
3. **Controller** → Logique métier
4. **Model** → Accès base de données
5. **Inertia Response** → Vue Component avec props
6. **Rendu côté client** → Vue.js

---

## Fonctionnalités

### Fonctionnalités principales

#### 1. Gestion des utilisateurs
- ✅ Inscription/Connexion
- ✅ Vérification d'email
- ✅ Réinitialisation de mot de passe
- ✅ Authentification à deux facteurs (2FA)
- ✅ Gestion de profil
- ✅ Rôles utilisateurs (Admin, Customer)

#### 2. Gestion des clients
- ✅ CRUD complet (Create, Read, Update, Delete)
- ✅ Recherche et filtrage
- ✅ Notes internes par client
- ✅ Statut actif/inactif
- ✅ Association clients ↔ séances

#### 3. Bibliothèque d'exercices
- ✅ CRUD complet
- ✅ Upload d'images pour les exercices
- ✅ Catégorisation (catégories publiques/privées)
- ✅ Recherche et filtrage
- ✅ Durée suggérée par exercice
- ✅ Partage d'exercices entre utilisateurs (is_shared)

#### 4. Gestion des catégories
- ✅ Catégories publiques (partagées)
- ✅ Catégories privées (par utilisateur)
- ✅ CRUD complet
- ✅ Filtrage par type

#### 5. Création de séances
- ✅ Création de séances d'entraînement
- ✅ Ajout d'exercices avec paramètres :
  - Répétitions
  - Temps de repos
  - Durée
  - Poids
  - Description additionnelle
- ✅ Gestion de séries (sets)
- ✅ Association de plusieurs clients à une séance
- ✅ Notes de séance
- ✅ Date de séance
- ✅ Réorganisation par drag & drop

#### 6. Éditeur de mise en page (Layout Editor)
- ✅ Éditeur visuel avec canvas Konva
- ✅ Drag & drop d'éléments
- ✅ Personnalisation de la mise en page
- ✅ Sauvegarde de layouts personnalisés
- ✅ Génération PDF depuis le layout

#### 7. Partage de séances
- ✅ Génération de token unique (UUID)
- ✅ Partage via lien public sécurisé
- ✅ Vue publique sans authentification
- ✅ Export PDF des séances partagées

#### 8. Génération PDF
- ✅ Export PDF des séances
- ✅ Export PDF depuis layout personnalisé
- ✅ Prévisualisation PDF
- ✅ Mise en page professionnelle

#### 9. Envoi d'emails
- ✅ Envoi de séances par email aux clients
- ✅ Templates d'email personnalisés
- ✅ Queue system pour envoi asynchrone

#### 10. Gestion d'abonnements
- ✅ Intégration Stripe
- ✅ Plans d'abonnement :
  - **Gratuit** : Limité (3 clients, 10 exercices, 5 catégories)
  - **Pro (5€/mois)** : Illimité
- ✅ Portail client Stripe
- ✅ Webhooks Stripe
- ✅ Gestion des limites par plan

#### 11. Interface utilisateur
- ✅ Design moderne et responsive
- ✅ Mode sombre/clair
- ✅ Sidebar navigable
- ✅ Notifications toast
- ✅ Composants UI accessibles

### Fonctionnalités par plan

#### Plan Gratuit
- ✅ Création de séances (sans enregistrement)
- ✅ Accès à tous les exercices de la bibliothèque
- ✅ Impression des séances
- ✅ Support par email
- ❌ Limité à 3 clients
- ❌ Limité à 10 exercices importés
- ❌ Limité à 5 catégories privées
- ❌ Pas d'export PDF

#### Plan Pro (5€/mois)
- ✅ Tout du plan gratuit
- ✅ Clients illimités
- ✅ Export des séances en PDF
- ✅ Création et enregistrement illimités de séances
- ✅ Import d'exercices illimités
- ✅ Support email prioritaire
- ✅ Création de catégories illimitées

---

## Sécurité

### Authentification

#### Mécanismes implémentés
1. **Laravel Fortify**
   - Hashage des mots de passe (bcrypt)
   - Protection CSRF sur toutes les routes
   - Vérification d'email obligatoire
   - Authentification à deux facteurs (2FA)
   - Rate limiting sur les tentatives de connexion

2. **Sessions sécurisées**
   - Sessions chiffrées
   - Timeout de session configurable
   - Protection contre le vol de session

3. **Tokens de partage**
   - UUID v4 pour les tokens de partage
   - Tokens uniques et non prévisibles
   - Accès en lecture seule aux séances partagées

### Autorisation

#### Middleware d'authentification
- `auth` - Vérifie que l'utilisateur est connecté
- `verified` - Vérifie que l'email est vérifié
- `password.confirm` - Demande confirmation du mot de passe pour actions sensibles

#### Contrôle d'accès
- Vérification de propriété des ressources (sessions, clients, exercices)
- Isolation des données par utilisateur
- Rôles utilisateurs (Admin/Customer)

### Protection des données

#### Validation des entrées
- **Form Requests** Laravel pour validation stricte
- Validation côté serveur obligatoire
- Sanitization des données utilisateur
- Protection contre les injections SQL (Eloquent ORM)

#### Protection CSRF
- Tokens CSRF sur toutes les requêtes POST/PUT/DELETE
- Middleware `ValidateCsrfToken` actif
- Exception pour webhooks Stripe (nécessaire)

#### Protection XSS
- Échappement automatique dans Blade
- Échappement dans Vue.js
- Sanitization des données avant affichage

### Sécurité des fichiers

#### Upload de fichiers
- Validation des types MIME
- Validation de la taille des fichiers
- Stockage sécurisé via Spatie Media Library
- Noms de fichiers sécurisés

#### Stockage
- Fichiers stockés dans `storage/app/public`
- Accès contrôlé via Laravel
- Pas d'accès direct aux fichiers sensibles

### Sécurité des paiements

#### Stripe
- Clés API stockées dans variables d'environnement
- Webhooks vérifiés avec signature Stripe
- Pas de stockage de données de carte bancaire
- Conformité PCI-DSS via Stripe

### Conformité RGPD

#### Mesures implémentées
1. **Politique de confidentialité** - Page dédiée
2. **Politique de cookies** - Page dédiée avec bannière
3. **Mentions légales** - Page dédiée
4. **Conditions d'utilisation** - Page dédiée

#### Droits des utilisateurs
- Accès aux données personnelles
- Modification des données
- Suppression de compte (avec nettoyage des abonnements Stripe)

#### Conservation des données
- Suppression automatique lors de suppression de compte
- Conservation limitée dans le temps
- Logs de sécurité

### Sécurité des headers HTTP

#### Headers de sécurité
- `.htaccess` configuré pour Apache
- Headers de sécurité à ajouter (HSTS, CSP, etc.)

---

## SEO (Search Engine Optimization)

### État actuel

#### Points positifs
1. **Structure HTML sémantique**
   - Utilisation de balises HTML5 appropriées
   - Structure hiérarchique des titres (h1, h2, etc.)

2. **Meta tags de base**
   - `<title>` dynamique via Inertia Head
   - `<meta charset="utf-8">`
   - `<meta name="viewport">` pour responsive

3. **Robots.txt**
   - Fichier présent dans `public/robots.txt`
   - Actuellement permissif (pas de restrictions pour favoriser le SEO)

4. **Favicons**
   - Favicon.ico
   - Favicon.svg
   - Apple touch icon

5. **Server-Side Rendering (SSR)**
   - Inertia SSR activé
   - Pré-rendu côté serveur pour meilleur SEO

6. **URLs propres**
   - Routes nommées Laravel
   - URLs lisibles et descriptives

### Améliorations SEO à implémenter

### Recommandations prioritaires

1. **Créer un blog** pour le contenu SEO
2. **Configurer Google Search Console**
3. **Ajouter Google Analytics**

---

## Performance

### Optimisations actuelles

#### Frontend
- **Vite** pour build rapide
- **Code splitting** automatique
- **Tree shaking** pour réduire la taille
- **Minification** CSS/JS en production
- **Lazy loading** des composants (via Inertia)

#### Backend
- **Cache Laravel** (configuré)
- **Eager loading** pour éviter N+1 queries
- **Indexes** sur les colonnes fréquemment recherchées

#### Base de données
- **Eloquent ORM** optimisé
- **Relations** bien définies
- **Pagination** pour les listes

### Améliorations possibles

1. **Database indexing** supplémentaire
2. **Query optimization** avec EXPLAIN
3. **Lazy loading** des images

---

## Idées de features futures

### Court terme (1-3 mois)

#### 1. Amélioration de l'expérience utilisateur
- **Duplication de séances** en un clic
- **Historique des modifications** de séances
- **Favoris** pour exercices et séances
- **Recherche avancée** avec filtres multiples
- **Export Excel** des séances
- **Calendrier** pour visualiser les séances
- **Blog** pour améliorer le SEO et la conversion 

#### 2. Fonctionnalités clients
- **Profils clients enrichis** (photo, objectifs, historique)
- **Suivi de progression** (poids, mesures, performances)
- **Graphiques de progression** pour les clients
- **Notes de séance** par le client
- **Feedback client** sur les séances

#### 3. Communication
- **Chat intégré** coach-client
- **Rappels automatiques** par email/SMS
- **Commentaires** sur les séances

#### 4. Analytics et rapports
- **Dashboard analytics** pour coachs
- **Statistiques d'utilisation** des exercices
- **Rapports de performance** des clients
- **Export de données** pour analyse

### Moyen terme (3-6 mois)

#### 1. Application mobile
- **PWA** (Progressive Web App) complète

#### 2. Fonctionnalités avancées
- **Programmes d'entraînement** multi-semaines
- **Planification automatique** de séances
- **Bibliothèque de vidéos** d'exercices
- **Intégration wearables** (Apple Health, Google Fit)
- **IA pour suggestions** d'exercices

#### 3. Social et communauté
- **Réseau social** entre coachs
- **Partage de séances** entre coachs
- **Marketplace** d'exercices
- **Reviews et ratings** d'exercices

#### 4. Monétisation avancée
- **Plans multiples** (Starter, Pro, Enterprise)
- **Paiement à la séance** (pay-per-use)
- **Affiliation** pour coachs
- **Marketplace** de programmes

### Long terme (6-12 mois)

#### 1. Intégrations tierces
- **Calendly** pour prise de rendez-vous
- **Google Calendar** sync

#### 2. Fonctionnalités entreprise
- **Multi-utilisateurs** par organisation
- **Gestion d'équipe** de coachs
- **Branding personnalisé** (white-label)
- **API publique** pour intégrations
- **Webhooks** pour événements

#### 3. Gamification
- **Badges et achievements** pour clients
- **Leaderboard** (optionnel)
- **Points et récompenses**

#### 4. Nutrition
- **Gestion de plans nutritionnels**
- **Suivi des repas**
- **Calcul de macros**
- **Intégration MyFitnessPal**

---

## Conclusion

FitnessClic est une application moderne et bien structurée, utilisant les meilleures technologies actuelles. L'application offre une base solide pour les fonctionnalités actuelles et futures.

### Points forts
- ✅ Architecture moderne et maintenable
- ✅ Technologies à jour
- ✅ Sécurité de base bien implémentée
- ✅ Interface utilisateur moderne
- ✅ Code bien organisé
- ✅ Optimisation du SEO

---

**Document généré le :** 2025-12-14  
**Version de l'application :** 1.0.0  
**Dernière mise à jour :** 2025-12-14

