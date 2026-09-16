# 12 — Plan des annexes

## Règles

- Les annexes suivent leur première citation dans le corps.
- Elles ne servent pas à déplacer une partie essentielle du raisonnement hors du rapport.
- Chaque annexe possède un titre, une introduction courte et une source.
- Les secrets, données personnelles et longues copies de code sont exclus.
- Les extraits doivent être courts, lisibles et accompagnés d’une explication.

## Annexe A — Catalogue de l’API

Contenu :

- regroupement des 93 routes API par domaine;
- méthode, URI, authentification, rôle et réponse principale;
- codes 401, 403, 409 et 422;
- exemples neutralisés de requête/réponse;
- aucune valeur de jeton.

Source : routes/api.php et contrôleurs.

Citation proposée : « Le catalogue complet des endpoints figure en annexe A. »

## Annexe B — Données et règles métier

Contenu :

- diagramme relationnel;
- dictionnaire des tables métier;
- états de réservation et paiement;
- contraintes et index;
- règle de chevauchement [début, fin);
- politique de timezone.

Source : migrations, modèles et logique métier.

## Annexe C — Traçabilité et tests

Contenu :

- matrice de 15_REQUIREMENTS_TRACEABILITY.md;
- inventaire des suites;
- résultats datés;
- cas limites;
- statut E2E non relancé;
- procédure de base éphémère recommandée.

## Annexe D — Configuration et déploiement

Contenu :

- diagramme Docker Compose;
- versions;
- liste des noms de variables sans valeurs;
- procédure locale;
- architecture cible;
- checklist de mise en production et rollback.

Exclure toute copie de .env.

## Annexe E — Interfaces

Contenu :

- captures recadrées et anonymisées;
- desktop, tablette et mobile;
- parcours visiteur/client/responsable/admin;
- légendes conformes au plan 08.

## Annexe F — Extraits techniques

Sélection maximale recommandée :

1. validation de l’inscription et rôle client forcé;
2. transaction/verrou de réservation;
3. vérification du webhook;
4. middleware de rôle;
5. repli de synchronisation frontend.

Chaque extrait doit tenir sur une page au maximum. Utiliser des ellipses explicites sans modifier le sens.

## Annexe G — Manuel utilisateur de démonstration

Contenu :

- prérequis;
- démarrage local;
- parcours par rôle avec comptes de démonstration gérés hors rapport;
- données fictives;
- arrêt propre;
- dépannage sans afficher de secret.

## Annexe H — Gestion de projet

À ajouter uniquement si des preuves existent :

- backlog;
- planning;
- sprints;
- diagramme de Gantt;
- risques;
- comptes rendus.

Informations nécessaires : [À COMPLÉTER PAR L'ÉTUDIANT].

## Contrôle des annexes

- [ ] Aucune annexe orpheline.
- [ ] Toutes sont citées dans le corps.
- [ ] Aucune donnée confidentielle.
- [ ] Figures et tableaux numérotés.
- [ ] Code limité aux extraits utiles.
- [ ] Résultats datés et reproductibles.
- [ ] Les éléments proposés sont distingués des éléments réalisés.
