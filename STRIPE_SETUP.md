# Configuration Stripe pour FitnessClic

## Étapes de configuration

### 1. Créer un produit et un prix dans Stripe

1. Connectez-vous à votre [tableau de bord Stripe](https://dashboard.stripe.com/)
2. Allez dans **Produits** > **Ajouter un produit**
3. Créez un produit nommé "FitnessClic Pro"
4. Configurez un prix récurrent mensuel de 5€
5. Copiez le **Price ID** (commence par `price_...`)

### 2. Configurer les variables d'environnement

Ajoutez les variables suivantes dans votre fichier `.env` :

```env
STRIPE_KEY=pk_test_...  # Votre clé publique Stripe
STRIPE_SECRET=sk_test_...  # Votre clé secrète Stripe
STRIPE_WEBHOOK_SECRET=whsec_...  # Secret du webhook (voir étape 3)
STRIPE_PRICE_ID=price_...  # Le Price ID copié à l'étape 1 (configuré dans config/cashier.php)
```

**Note** : Le `STRIPE_PRICE_ID` est configuré dans `config/cashier.php` et peut être modifié directement dans ce fichier si nécessaire.

### 3. Configurer les webhooks Stripe

#### En développement (avec Stripe CLI)

1. Installez Stripe CLI : https://stripe.com/docs/stripe-cli
2. Connectez-vous : `stripe login`
3. Écoutez les webhooks : `stripe listen --forward-to localhost:8082/stripe/webhook`
4. Copiez le **Signing secret** affiché (commence par `whsec_...`) et ajoutez-le dans `.env` :
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

#### En production

1. Dans votre tableau de bord Stripe, allez dans **Développeurs** > **Webhooks**
2. Cliquez sur **Ajouter un endpoint**
3. Entrez l'URL : `https://votre-domaine.com/stripe/webhook`
4. Sélectionnez les événements suivants :
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.trial_will_end`
   - `checkout.session.completed`
5. Copiez le **Signing secret** et ajoutez-le dans `.env` comme `STRIPE_WEBHOOK_SECRET`

### 4. Tester en mode développement

Pour tester avec Stripe en mode test :

1. Utilisez les clés de test (`pk_test_...` et `sk_test_...`)
2. Utilisez les cartes de test Stripe : https://stripe.com/docs/testing
3. Configurez un webhook local avec Stripe CLI :
   ```bash
   stripe listen --forward-to localhost:8000/stripe/webhook
   ```

### 5. Commandes utiles

```bash
# Publier les migrations Cashier (déjà fait)
php artisan vendor:publish --tag="cashier-migrations"

# Exécuter les migrations
php artisan migrate

# Publier la configuration Cashier (déjà fait)
php artisan vendor:publish --tag="cashier-config"
```

## Fonctionnalités par plan

### Plan Gratuit
- ✅ Création de séances (sans enregistrement)
- ✅ Accès à tous les exercices de la bibliothèque
- ✅ Impression des séances possible
- ✅ Support par email
- ❌ Limité à 3 clients
- ❌ Limité à 10 exercices importés
- ❌ Limité à 5 catégories privées
- ❌ Pas d'export PDF

### Plan Pro (5€/mois)
- ✅ Tout du plan gratuit
- ✅ Clients illimités
- ✅ Export des séances en PDF
- ✅ Création et enregistrement illimités de séances
- ✅ Import d'exercices illimités dans la bibliothèque
- ✅ Support email prioritaire
- ✅ Création de nouvelles catégories d'exercices illimités

## Routes disponibles

- `/subscription` - Page de gestion d'abonnement
- `/subscription/checkout` - Redirection vers Stripe Checkout
- `/subscription/portal` - Redirection vers le portail de facturation Stripe
- `/subscription/success` - Page de succès après souscription
- `/stripe/webhook` - Endpoint webhook Stripe (POST uniquement)

