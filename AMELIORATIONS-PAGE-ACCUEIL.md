# Améliorations page d'accueil — favoriser les inscriptions (coachs & clients)

> Objectif : équilibrer les deux parcours d'inscription (client « trouver un coach » /
> coach « développer son activité ») et lever les objections, **sans réintroduire de
> contenu fictif** (notes/avis, « 1ʳᵉ séance offerte », faux compteurs de coachs).

## État actuel de la home

Structure de `resources/js/pages/Welcome.vue` :

1. `MarketplaceHero` — titre + recherche (mot-clé + ville + « Autour de moi ») + disciplines populaires
2. `FeaturedCoaches` — grille de vrais coachs publiés (masquée si vide)
3. `DisciplinesSection` — 8 tuiles → annuaire filtré
4. `HowItWorksSection` — 3 étapes
5. `BecomeCoachSection` — bande sombre « Vous êtes coach ? » → inscription

**Constat** : la home est *client-first*, avec un seul appel « coach » tout en bas.

---

## 🎯 Quick wins (aucun ou très peu de backend)

| # | Idée | Pour qui | Impact | Effort |
|---|------|----------|--------|--------|
| 1 | **Hero à double parcours** : garder la recherche client en principal + un lien secondaire visible « Vous êtes coach ? Créez votre profil gratuit → » | Coach | ★★★ | Faible |
| 2 | **Micro-réassurance sous la recherche** : « Gratuit • Sans engagement • Messagerie directe avec le coach » | Client | ★★ | Très faible |
| 3 | **Enrichir la bande « Vous êtes coach ? »** en vraie section valeur : 3 bénéfices (Visibilité locale / Clients qualifiés / Outils tout-en-un : programmes, séances, messagerie) + « gratuit, sans engagement » | Coach | ★★★ | Faible |
| 4 | **Section FAQ** (accordéon) répondant aux objections des 2 cibles (client : combien ça coûte ? en ligne ou près de chez moi ? comment contacter ? — coach : c'est gratuit ? quels outils ? quel engagement ?) | Les deux | ★★★ (+ SEO) | Moyen |
| 5 | **Bande CTA finale** avant le footer, 2 boutons : « Trouver un coach » / « Devenir coach » | Les deux | ★★ | Faible |

## 📊 Valeur moyenne (petit backend)

- **6. Bandeau de chiffres RÉELS** (pas inventés) : la route `/` passe `coachs publiés`,
  `disciplines`, `villes couvertes` → affichés **uniquement au-dessus d'un seuil**
  (ex. ≥ 10 coachs) pour ne pas faire vide au démarrage. Preuve sociale honnête.
- **7. Vitrine « outils coach »** : version compacte de l'ancienne `FeaturesSection` /
  captures, orientée coach (« Tout pour gérer votre activité : bibliothèque d'exercices,
  création de séances, export PDF, gestion clients »). Convertit les coachs venus surtout
  pour le **logiciel**, pas seulement la marketplace.
- **8. Transparence tarifaire coach** : message clair « Profil gratuit, outils premium à
  partir de X€ » (on avait retiré `PricingSection` de la home) — rassure avant l'inscription.

## 🔍 SEO / acquisition organique (plus gros chantier)

- **9. Pages d'atterrissage par ville / discipline** (« Coach sportif à Lyon »,
  « Coach musculation ») indexables → trafic qualifié qui se convertit. Plus gros levier
  d'inscriptions clients à moyen terme, mais chantier conséquent.

## 🚧 À garder pour plus tard (nécessite de nouvelles fonctionnalités)

- **Avis & notes** : meilleur signal de confiance pour convertir des clients, mais il faut
  d'abord **construire le système d'avis**. Ensuite → notes sur les cartes + témoignages réels.
- **Témoignages réels** : à collecter auprès des premiers utilisateurs (ne pas inventer).

---

## Reco de démarrage

1. Lot **1 + 2 + 3 + 5** (rapide, sans backend) : rééquilibre coach/client immédiatement.
2. Puis **4 (FAQ)** : SEO + objections.
3. **6 (chiffres réels)** en complément une fois assez de coachs publiés.
