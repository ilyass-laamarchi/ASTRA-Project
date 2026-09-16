# ASTRA PFA — Méthodologie réelle du projet

**Étudiant :** Ilyass Laamarchi  
**Établissement :** ENSI — Génie Informatique  
**Année universitaire :** 2025–2026  
**Entreprise d’accueil :** RELENTIA  
**Projet :** ASTRA

## 1. MÉTHODOLOGIE PROUVÉE

- **Méthodologie formelle trouvée : NON**
- **Nom si prouvé :** aucune méthodologie formelle identifiable.

### Éléments de preuve

- Le dépôt disponible ne contient pas d’historique Git exploitable (`.git` absent). Il est donc impossible de reconstituer des branches, des jalons ou des cycles de livraison à partir des commits.
- Aucun artefact ne prouve l’application de Scrum ou de Kanban : pas de backlog daté, de sprints, de rôles Scrum, de cérémonies, de vélocité, de tableau Kanban ni de comptes rendus d’itération.
- Aucun planning ou document de validation ne démontre un cycle en V ou un modèle en cascade. Le backlog et le diagramme de Gantt apparaissent uniquement comme des annexes à préparer dans [12_ANNEX_PLAN.md](12_ANNEX_PLAN.md), et non comme des traces de pilotage existantes.
- Les documents de contrôle signalent eux-mêmes que la méthodologie, la priorisation et les jalons restent à documenter : [10_PFE_REPORT_DRAFT_FR.md](10_PFE_REPORT_DRAFT_FR.md), [13_MISSING_INFORMATION.md](13_MISSING_INFORMATION.md) et [17_FINAL_PFE_CHECKLIST.md](17_FINAL_PFE_CHECKLIST.md).
- En revanche, les migrations datées, la séparation en modules, les suites de tests et les ajouts coordonnés entre API, interface et tests prouvent une construction par incréments. Cette preuve justifie une **approche itérative et incrémentale**, sans permettre de lui attribuer un cadre de gestion formel.

Les dates de fichiers constituent seulement des indices complémentaires, car elles peuvent être modifiées par une copie. Les noms horodatés des migrations et la cohérence fonctionnelle des changements sont des traces plus fiables.

## 2. DÉMARCHE RÉELLE OBSERVÉE

1. **Cadrage fonctionnel et délimitation du système.** Les acteurs, droits, fonctionnalités et règles métier sont identifiables dans le README, les routes, les matrices fonctionnelles et la traçabilité des exigences. Les documents finaux formalisent trois rôles et les principaux cas d’utilisation, sans prouver l’existence d’un cahier des charges initial validé.
2. **Mise en place du socle technique et des données.** La structure sépare le backend Laravel, le frontend Vue, la base MySQL et les services d’exécution. Les migrations du 4 août 2026 créent les jetons Sanctum puis le noyau métier : catégories, véhicules, images, réservations et paiements.
3. **Réalisation du noyau métier.** L’authentification, la flotte, la disponibilité et la réservation ont été développées autour de règles centralisées côté serveur : prix calculé par l’API, détection des chevauchements, transaction, verrouillage et transitions de statut.
4. **Ajouts fonctionnels successifs.** Les migrations du 11 août ajoutent les profils, préférences, paramètres, notifications et demandes de contact. D’autres composants complètent ensuite les espaces Client, Responsable et Administrateur ainsi que les abstractions de paiement, OAuth et temps réel.
5. **Intégration progressive des interfaces.** Les vues publiques, l’authentification et les espaces protégés sont reliés aux API par rôle. La présence de tests Playwright publics dès le 12 août, puis de scénarios couvrant les espaces métier, indique une extension progressive de la surface testée.
6. **Validation et corrections.** PHPUnit vérifie les règles métier et la sécurité des accès, Vitest les fonctions et gardes du frontend, et Playwright les parcours navigateur. L’ajout coordonné du 27 août du contrôleur de gestion, du gestionnaire de clients et de leurs tests illustre un incrément transversal suivi d’une validation.
7. **Consolidation documentaire et audit final.** Les guides d’architecture, d’API et de tests ont été regroupés, puis les dossiers `docs/pfe/00` à `docs/pfe/20` ont servi à contrôler la couverture fonctionnelle, la traçabilité, la maturité des intégrations et la cohérence du rapport.

Cette reconstitution exprime un ordre logique appuyé par les traces disponibles. Elle ne permet pas d’affirmer la durée des phases, leur découpage en sprints ou l’existence de validations périodiques par l’entreprise.

## 3. RECOMMENDED REPORT WORDING

### 1.8 Méthodologie de travail

Dans le cadre de ce Projet de Fin d’Année réalisé au sein de RELENTIA, le développement d’ASTRA a suivi une **approche itérative et incrémentale**. Cette qualification correspond à l’organisation observable des travaux : le système a été construit à partir d’un socle initial, puis enrichi par modules, intégré progressivement et vérifié à plusieurs niveaux. Les éléments disponibles ne permettent toutefois pas d’affirmer l’application complète d’un cadre formel tel que Scrum ou Kanban. En particulier, aucun découpage documenté en sprints, aucun rôle Scrum, aucune cérémonie et aucun tableau de flux daté ne sont conservés dans les traces du projet.

La démarche a commencé par la délimitation fonctionnelle de la plateforme. Les principaux acteurs ont été identifiés — Client, Responsable et Administrateur — avec des droits distincts. Les besoins essentiels ont ensuite été structurés autour de la consultation de la flotte, de l’authentification, de la réservation, du paiement et de la gestion administrative. Cette analyse a orienté une architecture séparant l’interface Vue, l’API Laravel et la persistance MySQL. Les routes, les modèles, les migrations et les matrices de traçabilité disponibles matérialisent cette structuration des responsabilités.

Un premier incrément a établi le socle technique et le noyau métier. Il comprend l’authentification par jetons avec Sanctum, les catégories, les véhicules, leurs images, les réservations et les paiements. Les règles critiques ont été placées côté serveur afin de maintenir la cohérence des données : calcul du prix, vérification des périodes, détection des conflits, exécution transactionnelle, verrouillage et contrôle des changements de statut. Ce choix a permis de disposer d’une base fonctionnelle avant d’étendre les services proposés.

Les incréments suivants ont complété le produit avec les profils utilisateurs, les préférences, les paramètres de l’agence, les notifications et le formulaire de contact. En parallèle, les interfaces publiques et les espaces propres à chaque rôle ont été reliés progressivement aux API. Les connexions à Stripe, Google OAuth et Reverb ont été préparées dans l’architecture et la configuration. Leur présence technique doit être distinguée d’une validation complète en environnement réel, laquelle n’est pas démontrée par les traces conservées.

Chaque extension a été accompagnée d’activités de vérification adaptées. Les tests PHPUnit couvrent notamment l’authentification, les autorisations, la tarification serveur, les conflits de réservation et les transitions d’état. Les tests Vitest portent sur les calculs de dates, les données de réservation et les gardes de navigation. Des scénarios Playwright vérifient les parcours publics et protégés, la séparation entre rôles ainsi que plusieurs enchaînements fonctionnels. L’ajout conjoint d’une fonctionnalité de gestion des clients dans le backend, le frontend et les tests constitue également une trace concrète d’amélioration transversale et de validation progressive.

Enfin, une phase de consolidation a regroupé la documentation de l’architecture, de l’API et des tests, puis établi une traçabilité entre exigences, implémentation et preuves de validation. L’audit final a servi à relever les limites restantes, notamment pour les services externes et les validations non démontrées. La démarche retenue décrit ainsi fidèlement une progression par fonctionnalités, avec intégration, vérification et correction successives, sans attribuer au projet une méthodologie formelle qui ne peut pas être prouvée.

## 4. TABLEAU DES PHASES

| Phase | Objectif | Travaux réalisés | Livrable observable |
|---|---|---|---|
| Cadrage fonctionnel | Définir le périmètre et les acteurs | Identification des rôles, fonctionnalités, règles et limites | README, routes, analyse fonctionnelle et traçabilité |
| Socle technique | Établir l’architecture exécutable | Séparation Laravel/Vue/MySQL, authentification et schéma initial | Configuration, migrations et structure applicative |
| Noyau métier | Rendre la réservation cohérente | Flotte, disponibilité, réservation, prix serveur et statuts | Services métier, contrôleurs et modèles |
| Extensions | Compléter les usages | Profils, préférences, paramètres, notifications, contact et paiements | Migrations et modules fonctionnels additionnels |
| Intégration | Relier les parcours aux API | Interfaces publiques et espaces Client, Responsable et Administrateur | Vues Vue, composants et appels API |
| Vérification et correction | Contrôler les règles et les parcours | Tests PHPUnit, Vitest et Playwright ; corrections transversales | Suites de tests et incrément de gestion des clients |
| Consolidation | Préparer la validation académique | Documentation, matrice de couverture et audit final | Guides techniques et dossier `docs/pfe` |

## 5. VERDICT

**Libellé recommandé pour le rapport : Approche itérative et incrémentale**

Ce libellé est le seul de la liste qui corresponde aux traces disponibles. Scrum, Kanban et le cycle en V ne sont pas démontrés ; « Unable to determine » serait trop restrictif, car les incréments fonctionnels, l’intégration progressive, les tests et les corrections sont observables.
