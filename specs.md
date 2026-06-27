# Spécifications — Espace client, profils coachs publics & messagerie

## 1. Grandes lignes (demande initiale)

- **Espace client** : visibilité des cours associés au client + accès à la messagerie pour discuter avec des coachs.
- **Profils des coachs** (Photo, Description, Tarif, Ville) affichés sur le site et **accessibles sans authentification** sur la page d'accueil. Design inspiré de https://www.superprof.fr.
- **Service de messagerie** (pas d'instantané / non temps réel), nécessite d'être authentifié, possible **uniquement entre un coach et un client**.

---

## 2. État actuel du code (rappel pour cadrer le dev)

| Entité | Rôle réel aujourd'hui | Authentifié ? |
|---|---|---|
| `User` (table `users`) | **Coach** : crée séances, exercices, gère ses clients, abonnement Stripe. Enum `UserRole` = `ADMIN` \| `CUSTOMER` (défaut `customer`). | Oui (Fortify) |
| `Customer` (table `customers`) | **Fiche client** créée et gérée par un coach (`user_id`). Champs : `first_name`, `last_name`, `email`, `phone`, `internal_note`, `is_active`. | **Non** (aucun mot de passe / login) |
| `Session` (table `training_sessions`) | **Cours / programme** créé par un coach, assigné à un ou plusieurs `Customer` via le pivot `session_customer`. | — |

- **Stack** : Laravel 12, Inertia 2 + Vue 3 (TS), Tailwind 4, composants `reka-ui`, Spatie MediaLibrary, Filament 4 (admin), Fortify (auth), Cashier (Stripe).
- **Auth** : `app/Actions/Fortify/CreateNewUser.php` crée un `User` sans définir de rôle (défaut DB = `customer`). Features Fortify actives : registration, reset password, email verification, 2FA.
- **Front public** : `resources/js/pages/Welcome.vue` (sections dans `resources/js/components/welcome/`). Pages app sous `resources/js/pages/*`, layouts `AppLayout.vue` / `AuthLayout.vue`.
- **⚠️ Incohérence existante** : `User::getFilamentName()` lit `first_name`/`last_name` alors que la table `users` n'a qu'un champ `name`. À ne pas propager.

---

## 3. Décisions (✅ validées)

### D1 — Authentification du client → ✅ **Compte `User` unifié**
- Enum `UserRole` étendu : `ADMIN`, `COACH`, `CLIENT`.
- Le client s'inscrit / se connecte comme `User` (role `CLIENT`), réutilisant Fortify (vérif email, 2FA, settings).
- La fiche `Customer` est **reliée** au compte client via une FK nullable `customers.account_user_id` → `users.id` (liaison automatique par email à l'inscription).
- La relation coach↔client reste portée par `customers.user_id` (coach propriétaire).

### D2 — Périmètre de la messagerie → ✅ **N'importe quel coach**
- Un client authentifié peut **contacter n'importe quel coach** depuis son profil public (logique superprof). Le coach répond. Une conversation = 1 paire unique (coach, client).

### D3 — Inscription & migration des comptes → ✅ **Choix coach/client à l'inscription**
- Page d'inscription : sélecteur **« Je suis coach » / « Je suis client »** définissant le rôle à la création (modifier `CreateNewUser`).
- Migration de backfill : tous les `users` existants `role = customer` (non-admin) deviennent `role = coach`.

### D4 — Photo de profil coach → ✅ **Spatie MediaLibrary**
- Collection `coach_avatar` + conversion thumbnail (cohérent avec `Exercise`/`BlogPost`). Fallback sur `users.avatar_url` existant.

### D5 — Notifications de nouveaux messages → ✅ **E-mail, pas de temps réel**
- Notification **e-mail** au destinataire (throttlée : 1 e-mail max / conversation / X min tant que non lu) + badge « non lus » au chargement des pages (compteur via requête, pas de websocket).

---

## 3bis. Relation `Customer` ↔ compte client `User` (règles figées)

**Principe** : ce sont deux objets distincts, pas deux copies d'une personne.
- **`User` (role `client`)** = l'**identité / le compte** de la personne (login, email, nom, avatar, messagerie). **1 personne = 1 `User`.**
- **`Customer`** = la **fiche CRM d'un coach** pour cette personne = la *relation* coach↔client (champs privés coach : `internal_note`, assignation des cours). **Par coach** → une personne suivie par N coachs = N `Customer`, toutes reliées au même `User`.

**Liaison** : portée par `customers.account_user_id` (FK nullable → `users.id`), clé de rapprochement = **email normalisé** (`lower(trim())`).

Déclencheurs automatiques (centralisés dans un service `CustomerAccountLinker`) :
- **À l'inscription d'un client** (`CreateNewUser`) : relier toutes les `Customer` `WHERE account_user_id IS NULL AND lower(email)=lower(user.email)`.
- **À la création/màj d'une fiche** (`CustomerObserver`) : si une `Customer` a un email correspondant à un `User` role `client` existant, poser `account_user_id`.

Règles d'intégrité :
- `account_user_id` ne pointe que vers un `User` role `client`.
- Suppression du compte client → FK `nullOnDelete` : la fiche **reste** (donnée du coach), `account_user_id` repasse à `null`.
- Recommandé : index unique `(user_id, email)` sur `customers` (un coach n'a pas 2 fiches du même email).

Cas non couvert par l'auto-liaison (emails différents) → **liaison manuelle** depuis la fiche côté coach (rechercher/relier un compte client).

Sécurité : l'auto-liaison ne crée pas de faille — l'espace client est derrière le middleware `verified`, donc le client n'accède à ses cours **qu'après vérification de son email** (preuve de possession de l'adresse).

Côté usage :
- « Mes cours » (client) = `Customer::where('account_user_id', $me->id)->with('trainingSessions')` → cours de **tous** ses coachs agrégés.
- La **messagerie est découplée** (`User`↔`User`) : un client peut écrire à un coach sans aucune fiche chez lui.

---

## 4. Modèle de données (migrations)

### 4.1 Enum & rôles
- Étendre `App\Enums\UserRole` : ajouter `COACH = 'coach'`, `CLIENT = 'client'`.
- Migration : modifier la colonne `users.role` (enum → inclure `coach`, `client`) + backfill (D3).
- Ajouter helpers `User::isCoach()`, `User::isClientAccount()` ; clarifier/renommer l'actuel `isClient()`.

### 4.2 Profil coach — table `coach_profiles` (1‑1 avec `users`)
| Colonne | Type | Notes |
|---|---|---|
| `id` | id | |
| `user_id` | FK users, unique | propriétaire (coach) |
| `slug` | string, unique | URL SEO `/coachs/{slug}` |
| `headline` | string nullable | accroche courte |
| `bio` | text nullable | description |
| `hourly_rate` | integer nullable | **tarif** en centimes (€) |
| `city` | string nullable | **ville** |
| `postal_code` | string nullable | filtre géo |
| `specialties` | json nullable | spécialités/disciplines |
| `is_published` | boolean, défaut false | visible publiquement ou non |
| `published_at` | timestamp nullable | |
| timestamps | | |
- Photo : via MediaLibrary (collection `coach_avatar`).

### 4.3 Liaison compte client
- Migration : `customers.account_user_id` FK nullable → `users.id` (D1, option A).

### 4.4 Messagerie
**Table `conversations`**
| Colonne | Type | Notes |
|---|---|---|
| `id` | id | |
| `coach_id` | FK users | |
| `client_id` | FK users | |
| `last_message_at` | timestamp nullable | tri liste |
| timestamps | | |
- Index unique `(coach_id, client_id)` (une seule conversation par paire).

**Table `messages`**
| Colonne | Type | Notes |
|---|---|---|
| `id` | id | |
| `conversation_id` | FK conversations, cascade | |
| `sender_id` | FK users | |
| `body` | text | |
| `read_at` | timestamp nullable | accusé de lecture |
| timestamps | | |
- Index `(conversation_id, created_at)`.

---

## 5. Backend

### 5.1 Modèles & relations
- `CoachProfile` (HasMedia/InteractsWithMedia) : `belongsTo(User)`. Sur `User` : `hasOne(CoachProfile)`.
- `Conversation` : `belongsTo(User, coach_id)`, `belongsTo(User, client_id)`, `hasMany(Message)`. Helper `unreadCountFor(User)`.
- `Message` : `belongsTo(Conversation)`, `belongsTo(User, sender_id)`.
- `Customer` : ajouter `belongsTo(User, account_user_id)` (`accountUser()`).
- `User` : `hasMany(Conversation, coach_id)` + `hasMany(Conversation, client_id)`, scope/accessor `conversations()` fusionnant les deux selon le rôle. `customerRecords()` (fiches liées au compte client via `account_user_id`).

### 5.2 Politiques (Policies)
- `CoachProfilePolicy` : `update`/`publish` réservé au coach propriétaire.
- `ConversationPolicy` : `view`/`reply` réservé au `coach_id` ou `client_id` de la conversation. `create` : un `CLIENT` peut ouvrir une conversation vers un `COACH` (selon D2).
- `MessagePolicy` : envoyer si participant à la conversation.

### 5.3 Contrôleurs & routes

**Public (sans auth)** — `routes/web.php`
- `GET /coachs` → `CoachDirectoryController@index` — annuaire public, filtres `?ville=&q=&specialite=`, pagination. Page Inertia `coachs/Index`.
- `GET /coachs/{slug}` → `CoachDirectoryController@show` — profil public d'un coach. Page `coachs/Show`. CTA « Contacter » (→ login si non authentifié, sinon ouverture conversation).
- Intégration home : section « Trouver un coach » + lien sur `Welcome.vue`.

**Espace client (auth + role client)** — middleware `auth`, `verified`, gate rôle
- `GET /espace-client` → `ClientSpaceController@index` — dashboard client : liste des cours qui lui sont associés (via `Customer`(s) liés au compte → `trainingSessions`) + accès messagerie. Page `client/Dashboard`.
- `GET /espace-client/cours/{session}` → consultation d'un cours (réutiliser/adapter l'affichage de séance existant, lecture seule).

**Messagerie (auth)** — middleware `auth`, `verified`
- `GET /messages` → `ConversationsController@index` — liste des conversations (coach ou client). Page `messaging/Index`.
- `GET /messages/{conversation}` → `@show` — fil d'une conversation + marque comme lus. Page `messaging/Show`.
- `POST /messages` → `@store` — ouvrir une conversation (client → coach) ou récupérer l'existante, puis 1er message.
- `POST /messages/{conversation}/reply` → `@reply` — nouveau message.
- `GET /messages/unread-count` → compteur léger (badge nav, polling optionnel).

**Profil coach (auth + role coach)**
- `GET /mon-profil-coach` + `PUT` → `CoachProfileController` (édition headline, bio, tarif, ville, spécialités, photo, publication on/off). Page `coach/ProfileEdit`.

### 5.4 Form Requests (validation)
- `StoreCoachProfileRequest` / `UpdateCoachProfileRequest` (bio max, tarif ≥ 0, ville requise si `is_published`, slug unique généré).
- `StoreMessageRequest` (`body` requis, max length, anti-spam basique / throttle).
- `StoreConversationRequest` (coach cible valide & publié, expéditeur = client).

### 5.5 Notifications
- `NewMessageNotification` (mail) au destinataire, throttlée (D5).
- Lien direct vers `/messages/{conversation}`.

---

## 6. Frontend (Inertia + Vue 3 + Tailwind)

### 6.1 Annuaire & profils publics coachs (inspiration superprof)
- **`pages/coachs/Index.vue`** : barre de recherche (ville + mot-clé), filtres latéraux (spécialité, fourchette de tarif), grille de **cartes coach** (photo, nom, accroche, ville, tarif/h, bouton « Voir le profil »). Responsive, sans auth.
- **`pages/coachs/Show.vue`** : photo large, description, tarif, ville, spécialités, bouton **« Contacter ce coach »** (→ `/login?redirect=` si non connecté, sinon `POST /messages`).
- Composants : `components/coachs/CoachCard.vue`, `CoachFilters.vue`, `CoachProfileHeader.vue`.
- **SEO** : balises `<Head>` (title/description/OG) par profil ; ajout des profils publiés au `SitemapController`.
- Réutiliser le layout public (NavBar/Footer de `welcome/`).

### 6.2 Espace client
- **`pages/client/Dashboard.vue`** : liste des cours associés (cartes → consultation lecture seule), accès rapide messagerie + badge non lus.
- Entrée de menu dédiée (layout client) ; le `AppLayout`/sidebar actuel est orienté coach → prévoir une navigation conditionnée au rôle.

### 6.3 Messagerie
- **`pages/messaging/Index.vue`** : liste des conversations (avatar interlocuteur, dernier message, date, pastille non lus).
- **`pages/messaging/Show.vue`** : fil de discussion (bulles), zone de saisie + envoi (Inertia POST, pas de websocket), rafraîchissement à la navigation. Marquage lus à l'ouverture.
- Composants : `components/messaging/ConversationList.vue`, `MessageThread.vue`, `MessageComposer.vue`.

### 6.4 Profil coach (côté coach connecté)
- **`pages/coach/ProfileEdit.vue`** : formulaire (photo upload, accroche, bio, tarif, ville, spécialités, switch publication).

### 6.5 Inscription
- Adapter `pages/auth/Register.vue` : choix **« Je suis coach » / « Je suis client »** (ou 2 routes d'inscription). Définit le rôle à la création (modifier `CreateNewUser`).

---

## 7. Non‑fonctionnel
- **Sécurité** : autorisation systématique par Policy ; un coach ne voit que ses conversations ; un client ne voit que les siennes ; profils non publiés invisibles publiquement.
- **Anti‑spam messagerie** : throttle sur l'envoi, longueur max, échappement du contenu (XSS).
- **Perf** : pagination annuaire & messages ; eager-loading (`with`) pour éviter N+1 ; compteur non-lus en une requête.
- **SEO** : URLs propres `/coachs/{slug}`, sitemap, métadonnées.
- **i18n** : interface en français (cohérent avec l'existant).
- **Tests (Pest)** : policies (accès messagerie/profil), visibilité publique des profils publiés/non publiés, ouverture de conversation, envoi & lecture de message, accès espace client aux seuls cours liés.

---

## 8. Découpage proposé (lots de dev)

> Estimations indicatives à 150 €/jour, à affiner après validation des décisions §3.

| Lot | Contenu | Charge estimée |
|---|---|---|
| **L0 — Fondations rôles & comptes** | Enum rôles (coach/client), migration + backfill, `account_user_id`, parcours d'inscription coach/client, helpers & gates | 2 j |
| **L1 — Profils coachs publics** | Table `coach_profiles`, modèle + média photo, édition côté coach, annuaire `/coachs`, profil `/coachs/{slug}` (design superprof), SEO/sitemap | 3 j |
| **L2 — Espace client** | Dashboard client, liste des cours associés (lecture seule), navigation conditionnée au rôle | 1,5 j |
| **L3 — Messagerie** | Tables `conversations`/`messages`, contrôleurs, policies, UI liste + fil + envoi, compteur non-lus, notification e-mail | 3 j |
| **L4 — Finitions & tests** | Tests Pest, autorisations, responsive, recette | 1,5 j |
| **Total** | | **≈ 11 jours** |

---

## 9bis. Avancement

- **L0 — Fondations rôles & comptes : ✅ code complet, validé par les tests (174 passants).**
  - Enum `UserRole` → `admin`/`coach`/`client` (+ `label()`).
  - Migrations : transition de l'enum `users.role` avec backfill `customer`→`coach` (chemin MySQL `ALTER`/chemin SQLite `change()`), + `customers.account_user_id` (FK `nullOnDelete`).
  - `User::isCoach()` / `isClientAccount()` / `customerRecords()`. `Customer::accountUser()` / `isLinkedToAccount()`.
  - Service `CustomerAccountLinker` + `CustomerObserver` (liaison auto par email, 2 sens). Hook dans `CreateNewUser` (rôle à l'inscription, invitation → coach, liaison client).
  - Front : sélecteur **coach/client** sur `Register.vue`. `role`/`isCoach`/`isClient` exposés via `HandleInertiaRequests`.
  - Maj références : Filament (UserForm/UsersTable), `UserFactory` (states `coach()`/`client()`), seeders, commande Stripe.
  - ✅ **Migrations appliquées et vérifiées sur la base dev MySQL** (2026-06-27) : enum = `('admin','coach','client')`, backfill OK (1 admin, 14 coachs, 0 `customer` résiduel), colonne `account_user_id` présente. Commandes artisan à exécuter dans le conteneur : `docker exec fitnessclic-laravel.test-1 php artisan …`.
- **L1 — Profils coachs publics + annuaire : ✅ code complet, 6 tests dédiés + suite globale (180 passants).**
  - Table `coach_profiles` + modèle `CoachProfile` (photo via Spatie MediaLibrary, slug auto, scope `published`, tarif en centimes).
  - `User::coachProfile()`, `CoachProfilePolicy`, `UpdateCoachProfileRequest`.
  - `CoachProfileController` (édition `/mon-profil-coach`) + `CoachDirectoryController` (`/coachs`, `/coachs/{slug}`).
  - Pages Vue : `coachs/Index.vue` (annuaire + recherche ville/mot-clé/spécialité **+ fourchette de tarif**), `coachs/Show.vue` (profil public + bouton « Contacter » → login ou `/messages` selon auth, branché en L3), `coach/ProfileEdit.vue`.
  - Entrée sidebar « Mon profil coach ». Sitemap : annuaire + profils publiés.
  - **SEO/partage** : balises OG + Twitter sur la page Inertia, et **vue de partage `coachs.share`** servie aux crawlers (détection bot par user-agent), URLs absolues HTTPS. Tests dédiés (filtre tarif + vue OG).
  - ⚠️ **Pagination** : un `LengthAwarePaginator` Inertia expose `total`/`last_page`/`links` **à la racine** (pas sous `meta`) — le pattern `meta` de `blog/Index.vue` est erroné (pagination silencieusement cassée côté blog, à corriger un jour).
  - ⚠️ **Front en mode build** : pas de serveur Vite dev actif (`public/hot` absent) → l'app lit `public/build/manifest.json`. Toute nouvelle page Vue nécessite un `npm run build` pour être servie (lent dans cet env, ~10 min). Les tests utilisent `withoutVite()` pour ne pas dépendre du build.
- **L2 — Espace client : ✅ code complet, 4 tests dédiés.**
  - `ClientSpaceController` + route `/espace-client` ; redirection `/dashboard` selon le rôle (client → espace, coach → création de séance).
  - Page `client/Dashboard.vue` : séances agrégées de **tous** les coachs du client (via `customerRecords`), en lecture seule (réutilise la vue de partage existante `/session/{share_token}`) + section « Mes coachs ».
  - Sidebar conditionnée au rôle (les clients ne voient pas les outils coach).
- **L3 — Messagerie (non temps réel) : ✅ code complet, 7 tests dédiés.**
  - Tables `conversations` / `messages` ; modèles + `ConversationPolicy` (participants only).
  - `ConversationsController` : index, show (marque comme lus), start (client→coach, find-or-create), reply, unread-count.
  - Notification e-mail `NewMessageNotification` throttlée (1 mail/conversation tant que non lu).
  - Pages `messaging/Index.vue` (liste + pastilles non-lus) et `messaging/Show.vue` (fil + composer). Bouton « Contacter ce coach » du profil public → `POST /messages/start` (client) ou login (visiteur).
  - Compteur de non-lus exposé via `HandleInertiaRequests` + badge dans la nav.
- **L4 — Finitions : ✅** lien public « Trouver un coach » (NavBar desktop + mobile). **Suite complète : 191 tests passants** (2 échecs pré-existants non liés). **Build prod réussi (dans le conteneur)** : les 6 pages sont dans le manifest, rendu réel vérifié (`/coachs`, `/coachs/{slug}` → 200 ; pages authentifiées → 302 invité).
  - ⚠️ **Build** : lancer `docker exec fitnessclic-laravel.test-1 npm run build` (PAS sur l'hôte : Wayfinder y exécute `php artisan` qui ne joint pas la base `mysql`).
  - ✅ **Durcissement des rôles** : middleware `coach` (alias dans `bootstrap/app.php`, classe `EnsureUserIsCoach`) appliqué à toutes les routes coach (`/sessions`, `/customers`, `/exercises`, `/categories`, `/team`, `/subscription`, `/mon-profil-coach`, annonces). Un compte client y est redirigé vers `/espace-client` ; coachs et admins passent. `/espace-client` et la messagerie restent partagés. Couvert par tests dédiés.

---

## 9. Hors périmètre (à confirmer)
- Pas de messagerie temps réel (websockets) — volontaire.
- Pas de réservation/paiement en ligne ici (couvert par le devis « plateforme » séparé).
- Pas de pièces jointes dans les messages (sauf demande).
- Modération/signalement des messages : non prévu en v1.
