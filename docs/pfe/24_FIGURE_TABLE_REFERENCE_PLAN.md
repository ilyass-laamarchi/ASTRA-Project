# ASTRA PFA — Plan final des figures, tableaux et renvois

## 1. Règles d'intégration

Chaque figure ou tableau doit être annoncé dans le paragraphe précédent, porter une légende Word automatique, puis être suivi d'une ligne de source. Les renvois et listes doivent utiliser des champs Word afin que la numérotation et les pages soient régénérées après insertion des captures.

Pour les productions issues du dépôt, employer : **« Source : élaborée par l'auteur à partir de … »**. Pour une capture, employer : **« Source : capture de l'application ASTRA réalisée par l'auteur sur un jeu de données de démonstration, 2026. »** Les documents éditeurs restent cités par leur numéro bibliographique.

## 2. Numérotation finale proposée

L'insertion d'un MCD et d'un MLD distincts ajoute une figure au chapitre 2. La série finale devient :

| Numéro final | Objet |
|---|---|
| Figure 2.1 | Cas d'utilisation principaux |
| Figure 2.2 | Architecture globale |
| Figure 2.3 | MCD |
| Figure 2.4 | MLD |
| Figure 2.5 | Séquence de création d'une réservation |
| Figure 2.6 | Machine à états d'une réservation |
| Figure 2.7 | Séquence nominale de paiement |
| Figure 2.8 | États de paiement définis et pris en charge |

Les figures 3.1 à 3.7 et les tableaux 1.1 à 3.4 conservent leur numéro. Word doit effectuer la renumérotation ; il ne faut pas modifier seulement les légendes visibles.

## 3. Plan des figures des chapitres 1 à 3

| ID | Titre | Chapitre/section | Phrase exacte avant la figure | Source exacte après la figure | Décision |
|---|---|---|---|---|---|
| Figure 1.1 | Positionnement fonctionnel d'ASTRA | §1.6 | Remplacer la figure par : « Les acteurs et leurs interactions avec ASTRA sont détaillés par le diagramme de cas d'utilisation de la section 2.2. » | Sans objet après suppression | **REMOVE DUPLICATE** : identique à l'actuelle figure 2.1. |
| Figure 2.1 | Cas d'utilisation principaux de la plateforme ASTRA | §2.2 | « La figure 2.1 synthétise les fonctions accessibles à chaque acteur, conformément aux routes et aux contrôles d'autorisation de l'API. » | « Source : élaborée par l'auteur à partir de `routes/api.php`, des middlewares de rôle et des contrôleurs d'ASTRA. » | **REPLACE** par le diagramme vérifié de `23_UML_FINAL_EVIDENCE.md`. |
| Figure 2.2 | Architecture globale de la plateforme ASTRA | §2.4 | « La figure 2.2 présente les couches applicatives et les frontières avec les services externes. » | « Source : élaborée par l'auteur à partir de la structure du dépôt, de `compose.yaml` et des manifestes du projet. » | **KEEP**, avec Google, Stripe et Reverb en liaisons pointillées et qualifiés non validés en conditions réelles. |
| Figure 2.3 | Modèle Conceptuel de Données | §2.5.1 | « La figure 2.3 représente les entités métier, leurs associations et leurs cardinalités indépendamment de l'implantation SQL. » | « Source : élaborée par l'auteur à partir des règles métier, des migrations et des modèles Eloquent d'ASTRA. » | **REPLACE** l'actuel modèle relationnel par le MCD de `22_MCD_MLD_FINAL_EVIDENCE.md`. |
| Figure 2.4 | Modèle Logique de Données | §2.5.2 | « La figure 2.4 traduit le modèle conceptuel en relations, clés primaires et clés étrangères conformes aux migrations Laravel. » | « Source : élaborée par l'auteur à partir des migrations Laravel et des relations Eloquent d'ASTRA. » | **ADD** ; ne pas dupliquer en annexe B. |
| Figure 2.5 | Séquence de création d'une réservation | §2.7.3 | « La figure 2.5 détaille le contrôle initial, puis la vérification répétée dans la transaction verrouillée avant la création de la réservation. » | « Source : élaborée par l'auteur à partir de `CarAvailabilityService`, `ReservationController` et `NotificationService`. » | **REPLACE** l'actuelle figure 2.4 par la séquence exacte de `23_UML_FINAL_EVIDENCE.md`. |
| Figure 2.6 | Machine à états d'une réservation | §2.7.4 | « La figure 2.6 présente l'ensemble des transitions autorisées et distingue les états qui bloquent une période de location. » | « Source : élaborée par l'auteur à partir de `ReservationStatusService` et de `CarAvailabilityService::BLOCKING_STATUSES`. » | **REPLACE** l'actuelle figure 2.5 par la machine vérifiée. |
| Figure 2.7 | Séquence nominale du paiement Stripe | §2.8 | « La figure 2.7 décrit le flux nominal prévu par l'implémentation, de la création locale du paiement au webhook signé. » | « Source : élaborée par l'auteur à partir de `PaymentService`, `StripePaymentGateway` et `PaymentController` ; flux réel Stripe non validé. » | **KEEP/UPDATE** l'actuelle figure 2.6 ; conserver explicitement le niveau de maturité. |
| Figure 2.8 | États de paiement | §2.8 | « La figure 2.8 distingue les transitions pilotées par le service des états seulement prévus dans le schéma. » | « Source : élaborée par l'auteur à partir de la migration des paiements et de `PaymentService`. » | **REPLACE** l'actuelle figure 2.7 ; `failed` et `cancelled` restent sans transition implémentée. |
| Figure 3.1 | Page d'accueil publique d'ASTRA | §3.3 | « La figure 3.1 montre l'entrée publique de l'application et la mise en avant de la flotte. » | « Source : capture de l'application ASTRA réalisée par l'auteur sur un jeu de données de démonstration, 2026. » | **REPLACE PLACEHOLDER** par F01 ; Visiteur déconnecté ; masquer toute coordonnée ou immatriculation réelle. |
| Figure 3.2 | Consultation et filtrage des véhicules disponibles | §3.3 | « La figure 3.2 illustre la recherche, le filtrage, le tri et la pagination du catalogue public. » | « Source : capture de l'application ASTRA réalisée par l'auteur sur un jeu de données de démonstration, 2026. » | **REPLACE PLACEHOLDER** par F02 ; Visiteur déconnecté, filtre appliqué, données fictives. |
| Figure 3.3 | Création d'un compte Client | §3.5 | « La figure 3.3 confirme que l'inscription publique ne propose aucun choix de rôle privilégié. » | « Source : capture de l'application ASTRA réalisée par l'auteur sur un jeu de données de démonstration, 2026. » | **REPLACE PLACEHOLDER** par F03 ; formulaire fictif ; masquer identité, courriel, téléphone et mot de passe réels. |
| Figure 3.4 | Création d'une réservation dans l'espace Client | §3.6 | « La figure 3.4 présente la sélection des dates et le devis calculé par le serveur avant validation. » | « Source : capture de l'application ASTRA réalisée par l'auteur sur un jeu de données de démonstration, 2026. » | **REPLACE PLACEHOLDER** par F04 ; Client de démonstration, véhicule disponible, dates et devis visibles ; masquer données personnelles, jetons et immatriculation réelle. |
| Figure 3.5 | Tableau de bord opérationnel du Responsable ASTRA | §3.7 | « La figure 3.5 montre les indicateurs et les demandes accessibles au Responsable ASTRA. » | « Source : capture de l'application ASTRA réalisée par l'auteur sur un jeu de données de démonstration, 2026. » | **REPLACE PLACEHOLDER** par F05 ; compte Responsable et données fictifs ; masquer clients, coordonnées, identifiants et montants réels. |
| Figure 3.6 | Tableau de bord Administrateur | §3.7 | « La figure 3.6 montre les indicateurs stratégiques et les fonctions de gouvernance réservées à l'Administrateur. » | « Source : capture de l'application ASTRA réalisée par l'auteur sur un jeu de données de démonstration, 2026. » | **REPLACE PLACEHOLDER** par F06 ; état fournisseur qualifié ; masquer personnes, paiements, URL, clés, secrets et jetons. |
| Figure 3.7 | Architecture de déploiement local décrite par Docker Compose | §3.9 | « La figure 3.7 représente les quatre services et les volumes déclarés pour l'environnement local. » | « Source : élaborée par l'auteur à partir de `compose.yaml` et des Dockerfiles d'ASTRA. » | **KEEP** ; préciser qu'il ne s'agit pas d'un déploiement de production ; supprimer le doublon D.1. |

## 4. Plan des tableaux des chapitres 1 à 3

| ID | Titre | Chapitre/section | Phrase exacte avant le tableau | Source exacte après le tableau | Décision |
|---|---|---|---|---|---|
| Tableau 1.1 | Acteurs et responsabilités principales | §1.5 | « Le tableau 1.1 délimite les responsabilités principales des quatre acteurs retenus. » | « Source : élaboré par l'auteur à partir des routes, des rôles et des contrôleurs d'ASTRA. » | **KEEP** ; rester synthétique pour ne pas répéter la matrice 2.1. |
| Tableau 1.2 | Besoins fonctionnels synthétiques | §1.7 | « Le tableau 1.2 regroupe les besoins fonctionnels couverts par le périmètre du PFA. » | « Source : élaboré par l'auteur à partir des fonctions observées et vérifiées dans ASTRA. » | **KEEP** ; ne pas présenter Stripe, Google ou Reverb comme validés en réel. |
| Tableau 1.3 | Besoins non fonctionnels | §1.7 | « Le tableau 1.3 présente les qualités recherchées et le niveau de preuve disponible pour chacune. » | « Source : élaboré par l'auteur à partir du code, de la configuration et des résultats de tests d'ASTRA. » | **KEEP/QUALIFY** ; distinguer objectif, mécanisme présent et validation obtenue. |
| Tableau 2.1 | Matrice des permissions | §2.3 | « Le tableau 2.1 détaille les opérations autorisées pour chaque rôle au niveau de l'API. » | « Source : élaboré par l'auteur à partir de `routes/api.php` et du middleware de rôle. » | **REPLACE** par la matrice vérifiée de `23_UML_FINAL_EVIDENCE.md` ou l'aligner exactement sur elle. |
| Tableau 2.2 | Technologies utilisées dans ASTRA | §2.4 | « Le tableau 2.2 récapitule le rôle de chaque composant dans l'architecture. » | « Source : élaboré par l'auteur à partir des manifestes, de `compose.yaml` et des documentations officielles [1]–[10]. » | **KEEP** ; citer chaque famille technique dans le texte, pas seulement dans la bibliographie. |
| Tableau 2.3 | Cardinalités vérifiées du modèle de données | §2.5 | « Le tableau 2.3 complète le MCD et le MLD en précisant les cardinalités vérifiées. » | « Source : élaboré par l'auteur à partir des migrations et des relations Eloquent d'ASTRA. » | **REPLACE CONTENT** de l'actuel tableau « Entités métier principales » par le tableau de `22_MCD_MLD_FINAL_EVIDENCE.md`. |
| Tableau 2.4 | Convention de l'API REST | §2.6 | « Le tableau 2.4 synthétise les conventions d'URL, de verbes HTTP, d'authentification et de pagination de l'API. » | « Source : élaboré par l'auteur à partir de `routes/api.php`, des contrôleurs et des ressources Laravel d'ASTRA. » | **KEEP**. |
| Tableau 2.5 | Contrôles de sécurité intégrés | §2.10 | « Le tableau 2.5 associe chaque risque traité au contrôle effectivement présent dans le projet. » | « Source : élaboré par l'auteur à partir des requêtes de validation, des middlewares, des services et des tests d'ASTRA [3][10][14]–[16]. » | **KEEP/UPDATE** ; séparer contrôle présent et audit non réalisé. |
| Tableau 3.1 | Versions techniques observées dans l'instantané audité | §3.2 | « Le tableau 3.1 donne les versions relevées dans les fichiers de dépendances et l'image MySQL cible. » | « Source : élaboré par l'auteur à partir de `composer.lock`, `package-lock.json` et `compose.yaml`, relevés au 1er septembre 2026. » | **KEEP** ; conserver la date de l'instantané. |
| Tableau 3.2 | Résultats de validation obtenus le 1er septembre 2026 | §3.10 | « Le tableau 3.2 distingue les commandes exécutées avec succès des scénarios seulement présents dans le dépôt. » | « Source : élaboré par l'auteur à partir des sorties consignées dans `docs/pfe/06_TESTS_AND_RESULTS.md`. » | **KEEP** ; Playwright doit rester « non exécuté ». |
| Tableau 3.3 | Niveau de complétude des fonctions principales | §3.11 | « Le tableau 3.3 présente la maturité de chaque fonction dans le périmètre effectivement vérifié. » | « Source : élaboré par l'auteur à partir de `docs/pfe/16_FEATURE_COMPLETENESS.md`. » | **KEEP/TRANSLATE** ; employer « Complet dans le périmètre testé », « Partiellement complet », « Architecture uniquement », « Non vérifié », « Absent ». |
| Tableau 3.4 | Limites et améliorations prioritaires | §3.12 | « Le tableau 3.4 relie les limites constatées aux améliorations recommandées avant une exposition publique. » | « Source : élaboré par l'auteur à partir de `docs/pfe/05_SECURITY_ANALYSIS.md`, `06_TESTS_AND_RESULTS.md` et `16_FEATURE_COMPLETENESS.md`. » | **KEEP** ; ne pas transformer cette analyse documentaire en audit de sécurité. |

## 5. Doublons et éléments à retirer

| Objets | Constat | Action finale |
|---|---|---|
| Figures 1.1 et 2.1 | Même diagramme d'acteurs/cas d'utilisation | Supprimer 1.1 ; conserver et corriger 2.1. |
| Figure 2.3 actuelle et figure B.1 | Même modèle relationnel | Remplacer le corps par MCD + MLD ; supprimer B.1 ou remplacer l'annexe par un simple renvoi aux figures 2.3 et 2.4. |
| Figure 3.7 et figure D.1 | Même architecture Docker | Conserver 3.7 ; supprimer D.1 et renvoyer à la section 3.9. |
| Tableau 1.1 et tableau 2.1 | Sujet proche, niveaux différents | Conserver 1.1 comme synthèse des responsabilités ; réserver 2.1 aux permissions détaillées. |
| Tableau 2.3 actuel et nouveaux MCD/MLD | Risque de répétition des entités | Transformer 2.3 en tableau de cardinalités ; ne pas recopier le dictionnaire complet dans le corps. |
| Annexes E et F | Outils de préparation, pas des résultats académiques | Les supprimer après intégration des captures et informations finales. |

## 6. Contrôle Word final

Après toutes les corrections : mettre à jour les champs, vérifier les renvois dans le corps, régénérer la table des matières ainsi que les listes des figures et tableaux, puis contrôler la pagination dans le PDF exporté. Les annexes conservées doivent également figurer dans les listes si elles contiennent encore des objets numérotés.

