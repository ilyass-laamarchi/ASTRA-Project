# 10 — Brouillon structuré du rapport PFE

> Ce texte est une base rédactionnelle factuelle. Les éléments personnels, institutionnels et ceux de l’entreprise doivent être complétés et validés par l’étudiant.

# Page de garde

Établissement : École Nationale des Sciences de l’Informatique  
Filière : [À COMPLÉTER PAR L'ÉTUDIANT]  
Titre : **Conception et développement d’une plateforme web sécurisée de gestion de location automobile : cas d’ASTRA**  
Réalisé par : [À COMPLÉTER PAR L'ÉTUDIANT]  
Entreprise d’accueil : [À COMPLÉTER PAR L'ÉTUDIANT]  
Encadrant académique : [À COMPLÉTER PAR L'ÉTUDIANT]  
Encadrant professionnel : [À COMPLÉTER PAR L'ÉTUDIANT]  
Année universitaire : [À COMPLÉTER PAR L'ÉTUDIANT]

# Dédicace

[À COMPLÉTER PAR L'ÉTUDIANT]

# Remerciements

[À COMPLÉTER PAR L'ÉTUDIANT]

# Résumé

Ce projet de fin d’études porte sur la conception et le développement d’ASTRA, une plateforme web de gestion de location automobile. La solution répond à quatre profils : visiteur, client, Responsable ASTRA (owner) et administrateur. Elle centralise la consultation d’une flotte, la création de réservations, leur traitement, le suivi des paiements, les notifications et les indicateurs de pilotage. L’architecture associe une API Laravel, une application monopage Vue.js, une base MySQL et un canal temps réel fondé sur Laravel Reverb.

Le principal enjeu technique concerne la cohérence des réservations. La solution utilise un intervalle temporel semi-ouvert, un calcul serveur du prix, une transaction de base de données, un verrouillage de véhicule et une nouvelle vérification de conflit avant insertion. L’inscription publique impose le rôle client côté serveur et rejette les tentatives d’injection de privilèges. Le paiement est abstrait derrière une passerelle Stripe; la confirmation du paiement dépend d’un webhook signé dont le montant et la devise sont contrôlés.

L’audit final a validé 48 tests backend représentant 193 assertions et 10 tests frontend. Le build de production Vue réussit. Les intégrations réelles Google, Stripe et Reverb n’ont toutefois pas été validées dans l’environnement audité, et les tests E2E présents n’ont pas été relancés sur la base persistante. Le projet constitue donc un socle fonctionnel démontrable, accompagné d’un plan clair de durcissement et d’industrialisation.

**Mots-clés :** location automobile, Laravel, Vue.js, réservation, concurrence, autorisation, paiement, temps réel.

# Abstract

This final-year project presents the design and implementation of ASTRA, a web platform for car rental management. The solution serves four profiles: visitor, client, ASTRA manager (owner), and administrator. It centralizes fleet browsing, reservation creation and processing, payment tracking, notifications, and management indicators. Its architecture combines a Laravel API, a Vue.js single-page application, a MySQL database, and real-time communication based on Laravel Reverb.

The main technical challenge is reservation consistency. The solution relies on half-open time intervals, server-side price computation, a database transaction, row-level car locking, and a second conflict check before insertion. Public registration enforces the client role on the server and rejects privilege injection attempts. Payments are abstracted through a Stripe gateway; a payment is considered successful only after a signed webhook whose amount and currency have been validated.

The final audit successfully executed 48 backend tests with 193 assertions and 10 frontend tests. The Vue production build also passed. However, live Google, Stripe, and Reverb integrations were not validated in the audited environment, and the existing E2E suite was not rerun against the persistent database. The result is therefore a demonstrable functional foundation with an explicit hardening and industrialization roadmap.

**Keywords:** car rental, Laravel, Vue.js, reservation, concurrency, authorization, payment, real time.

# Liste des abréviations

| Abréviation | Signification |
|---|---|
| API | Application Programming Interface |
| CI/CD | Continuous Integration / Continuous Delivery |
| CORS | Cross-Origin Resource Sharing |
| CRUD | Create, Read, Update, Delete |
| E2E | End-to-End |
| OAuth | Open Authorization |
| ORM | Object-Relational Mapping |
| PFE | Projet de fin d’études |
| REST | Representational State Transfer |
| SPA | Single-Page Application |
| TLS | Transport Layer Security |
| UI/UX | User Interface / User Experience |
| WebSocket | Canal bidirectionnel persistant |

# Introduction générale

Le secteur de la location automobile dépend de données fortement liées : état des véhicules, périodes d’occupation, clients, prix, paiements et décisions opérationnelles. Lorsque ces informations sont dispersées, le délai de traitement augmente et le risque de double réservation devient significatif. Une plateforme numérique doit donc offrir une expérience simple au client sans sacrifier la cohérence transactionnelle ni l’autorisation.

Le projet ASTRA a pour objectif de proposer une application web unique pour le parcours public, la réservation client, la gestion opérationnelle et le pilotage administratif. La problématique centrale peut être formulée ainsi : **comment concevoir une plateforme de location automobile fluide pour plusieurs profils tout en garantissant la cohérence des disponibilités, la maîtrise des privilèges et la fiabilité des montants ?**

La démarche suivie s’appuie sur l’étude des besoins, la modélisation des acteurs et des règles, la conception d’une architecture SPA/API, l’implémentation incrémentale et la vérification automatisée. Le premier chapitre présente le contexte et l’état de l’art. Le deuxième expose l’analyse et la conception. Le troisième décrit la réalisation et les résultats mesurés.

# Chapitre 1 — Contexte, problématique et état de l’art

## Introduction

Ce chapitre situe le projet dans son environnement, analyse les difficultés du processus de location et établit les besoins auxquels ASTRA doit répondre.

## 1. Présentation de l’organisme d’accueil

Nom, historique, secteur, organisation, service d’accueil et positionnement : [À COMPLÉTER PAR L'ÉTUDIANT].

Cette partie ne doit pas dépasser quatre pages. Les informations doivent provenir de sources autorisées par l’entreprise et non d’hypothèses tirées du code.

## 2. Étude de l’existant

Le processus initial et ses outils sont : [À COMPLÉTER PAR L'ÉTUDIANT].

À partir du périmètre du projet, les difficultés visées sont la dispersion des informations, le manque de visibilité sur la flotte, les conflits de dates, la dépendance à un calcul manuel du prix, la lenteur du traitement et l’absence d’une vue consolidée pour les décideurs. Ces difficultés devront être confirmées lors de la rédaction finale par des observations ou entretiens.

## 3. État de l’art

Une plateforme moderne de location combine généralement un catalogue public, une authentification, une gestion des disponibilités, un workflow de réservation et des outils de pilotage. L’architecture SPA/API sépare la présentation et le métier. Vue.js gère l’interface réactive tandis que Laravel centralise validation, autorisation, persistance et intégrations.

Les solutions existantes doivent être comparées selon des critères objectifs : parcours client, contrôle de flotte, gestion des rôles, prévention des conflits, paiement, notifications, analytique, coût, personnalisation et maîtrise des données. Le tableau comparatif final nécessite une recherche documentée : [À COMPLÉTER PAR L'ÉTUDIANT].

## 4. Problématique

Le besoin ne se limite pas à afficher des voitures. Il faut garantir qu’un même véhicule ne soit pas réservé deux fois sur une période incompatible, empêcher le client de choisir son rôle ou son montant, et fournir aux responsables des décisions explicites. Les services externes doivent rester périphériques afin que le cœur métier soit testable.

## 5. Acteurs et besoins

- Le visiteur consulte le site, la flotte et les informations.
- Le client crée un compte, se connecte, réserve et suit ses opérations.
- Le Responsable ASTRA (owner) gère la flotte et traite les réservations.
- L’administrateur supervise les utilisateurs, les paramètres, les analyses et les remboursements autorisés.

Les besoins non fonctionnels portent sur la sécurité, la cohérence transactionnelle, la maintenabilité, la réactivité, la compatibilité responsive, la testabilité et la déployabilité.

## 6. Méthodologie

La méthode exacte de gestion de projet — Scrum, Kanban, itérations ou autre — ainsi que le calendrier doivent être renseignés à partir des traces réelles : [À COMPLÉTER PAR L'ÉTUDIANT]. Une présentation honnête peut décrire une démarche itérative : cadrage, modélisation, implémentation du noyau, intégration de l’interface, tests puis audit final.

## Conclusion

L’analyse met en évidence une problématique de cohérence et de gouvernance autant que d’expérience utilisateur. Ces contraintes orientent directement la conception présentée au chapitre suivant.

# Chapitre 2 — Méthodologie, analyse et conception

## Introduction

Ce chapitre transforme les besoins en architecture, modèle de données, règles métier et mécanismes de sécurité.

## 1. Architecture

ASTRA suit une architecture client-serveur. Le frontend Vue communique par HTTP avec l’API Laravel. Laravel utilise MySQL pour les données métier et Reverb pour la diffusion d’événements. Stripe et Google constituent des services externes optionnels selon la configuration.

La séparation offre plusieurs avantages : interface indépendante, validation centralisée, contrôle d’accès serveur et remplacement possible d’un fournisseur externe derrière une abstraction. Docker Compose décrit quatre services locaux : base, API, Reverb et frontend.

## 2. Modèle des acteurs et autorisation

Les routes publiques restent accessibles sans jeton. Les routes authentifiées utilisent Sanctum. Des groupes de middlewares distinguent client, owner/admin et admin. Les gardes Vue améliorent l’expérience de navigation, mais ne sont jamais la source d’autorité.

L’inscription publique force le rôle client. La requête rejette également les attributs susceptibles d’accorder des privilèges. Cette défense côté serveur protège même si un utilisateur contourne l’interface.

## 3. Modèle de données

Les entités métier principales sont users, categories, cars, car_images, reservations, payments, application_settings, astra_notifications et contact_inquiries. Les tables techniques couvrent les jetons Sanctum, sessions, cache, réinitialisation et files de jobs.

Une réservation relie un client et un véhicule, contient ses dates, son état, son prix et les informations nécessaires au suivi. Un paiement se rattache à la réservation afin de maintenir une source de vérité locale.

## 4. Disponibilité et concurrence

Deux réservations se chevauchent lorsque le début de l’une est antérieur à la fin de l’autre et inversement. Le système considère l’intervalle [début, fin), ce qui autorise une nouvelle location à commencer exactement à la fin de la précédente.

Lors d’une création, le serveur valide les données, ouvre une transaction, verrouille le véhicule, vérifie à nouveau les réservations bloquantes, calcule la durée et le prix, puis insère. En cas de conflit, l’API retourne 409. La timezone métier est Africa/Casablanca.

## 5. États

Le workflow autorise pending vers confirmed, rejected ou cancelled, puis confirmed vers cancelled ou completed. Les transitions interdites sont rejetées. Les réservations pending et confirmed bloquent la disponibilité.

## 6. Paiement

Une interface PaymentGatewayInterface découple le métier du fournisseur. L’adaptateur Stripe crée une session Checkout hébergée à partir du montant enregistré. La réussite dans le navigateur ne suffit pas : le webhook signé est vérifié, puis montant et devise sont rapprochés avant passage à paid. Le remboursement est réservé à l’administrateur.

## 7. Temps réel

Reverb et Laravel Echo diffusent des événements sur des canaux privés utilisateur, un canal opérations et un canal public de disponibilité. L’application prévoit un repli par rafraîchissement au focus, à la visibilité et toutes les trente secondes. La conception existe; son fonctionnement réseau réel n’a pas été exercé pendant l’audit.

## 8. Sécurité

Les contrôles essentiels sont la validation serveur, l’autorisation par middleware, le calcul serveur des montants, l’isolation client, les transactions et la signature de webhook. Les principales améliorations concernent le stockage du jeton dans localStorage, le retour OAuth contenant un jeton, CORS, les en-têtes de sécurité, l’audit et le MFA.

## 9. Stratégie de tests

La stratégie combine tests Feature Laravel, tests unitaires frontend et scénarios Playwright. Les tests backend ciblent surtout les invariants métier et d’autorisation. Pour une industrialisation, les E2E doivent utiliser une base éphémère.

## Conclusion

La conception place les invariants critiques côté serveur et utilise la base de données comme arbitre de concurrence. Cette architecture prépare la réalisation tout en isolant les intégrations externes.

# Chapitre 3 — Réalisation et résultats

## Introduction

Ce chapitre présente les technologies réellement installées, les modules développés, l’environnement local et les résultats de vérification.

## 1. Environnement technique

Le backend utilise Laravel 13.23.0, Sanctum 4.3.3, Reverb 1.11.0, Socialite 5.29.0 et Stripe PHP 21.1.1. Le frontend utilise Vue 3.5.41, Vue Router 5.2.0, Pinia 4.0.3, Axios 1.19.0, Vite 8.2.1, Vitest 4.1.10 et Playwright 1.62.1. MySQL 8.4 est décrit par Compose.

## 2. Réalisation du frontend

L’application fournit des pages publiques, des formulaires d’authentification, un espace client, un espace opérationnel et un espace administrateur. Pinia centralise les données transverses; Axios communique avec l’API; Vue Router applique les redirections ergonomiques selon le rôle.

Les interfaces de connexion et d’inscription conservent les éléments fonctionnels : validation, visibilité du mot de passe, Google OAuth, liens croisés et récupération du mot de passe. L’identité visuelle ASTRA emploie des variantes adaptées au fond.

## 3. Réalisation du backend

L’API contient 93 routes, dont 80 protégées par Sanctum. Trente-cinq routes sont réservées à l’administrateur, vingt-cinq au responsable ou à l’administrateur et neuf au client. Les contrôleurs gèrent authentification, flotte, réservation, paiements, contacts, notifications, profils et tableaux de bord.

## 4. Réservation

Le prix n’est jamais accepté comme vérité depuis le navigateur. Le serveur détermine la durée et le coût à partir des dates et du véhicule. La transaction et le verrou répondent au risque de requêtes concurrentes. Les tests confirment les chevauchements, les bornes et la visibilité immédiate de la réservation créée.

## 5. Administration et synchronisation

L’annuaire client est calculé par le serveur, trié par identifiant décroissant et paginé par groupes de quinze. L’interface offre recherche, pagination et rafraîchissement automatique toutes les quinze secondes. Les tableaux de bord distinguent le pilotage opérationnel du responsable et l’analytique stratégique de l’administrateur.

## 6. Paiement, OAuth et temps réel

Les trois intégrations sont implémentées architecturalement. Dans l’environnement audité, les identifiants Google et Stripe étaient absents. Reverb possédait sa configuration locale mais les services étaient arrêtés. Il serait donc incorrect de les qualifier de services externes validés en conditions réelles.

## 7. Résultats

Le 1 septembre 2026 :

- 48 tests backend et 193 assertions ont réussi en 50,401 secondes;
- 10 tests frontend répartis dans cinq fichiers ont réussi en 55,17 secondes;
- le build Vue de 1 847 modules a réussi en 36,65 secondes;
- la configuration Docker Compose est valide;
- les 14 scénarios E2E présents n’ont pas été relancés.

## 8. Discussion

Les résultats prouvent la stabilité du noyau testé, notamment réservation, rôles et isolation. Ils ne mesurent ni charge, ni accessibilité, ni sécurité dynamique, ni disponibilité de production. L’absence de CI/CD, de TLS versionné, de monitoring, de sauvegarde testée et de domaine permanent maintient le projet au stade de solution PFE prête à démonstration locale.

## Conclusion

La réalisation concrétise l’architecture par un ensemble full stack cohérent et des invariants métier testés. Les prochaines étapes portent moins sur l’ajout de pages que sur le durcissement, l’observabilité et la validation des services externes.

# Conclusion générale

ASTRA répond à la problématique en combinant expérience de consultation, gestion multi-rôle et cohérence transactionnelle. Les contributions majeures sont la prévention des doubles réservations, le calcul serveur, l’inscription client sécurisée, la séparation des espaces, l’abstraction du paiement et la validation automatisée du noyau.

Le projet présente cependant des limites explicites. Les services Google et Stripe ne sont pas configurés, Reverb n’a pas été vérifié en exécution, l’E2E n’a pas été rejoué, et aucune preuve de production permanente n’existe. Le stockage du jeton et le flux OAuth nécessitent un durcissement prioritaire.

# Perspectives

1. sécuriser les sessions et OAuth;
2. ajouter CI/CD, scans et E2E isolés;
3. déployer derrière TLS avec domaine stable;
4. configurer et tester Google, Stripe, mail et Reverb;
5. ajouter observabilité, sauvegardes et audit log;
6. introduire MFA et vérification e-mail;
7. mesurer performance et accessibilité;
8. améliorer idempotence et rapprochement des remboursements;
9. documenter l’API avec OpenAPI;
10. étudier une application mobile ou PWA après stabilisation.

# Bibliographie

La bibliographie finale doit reprendre uniquement des sources consultées et citées. Une sélection vérifiée est fournie dans 11_BIBLIOGRAPHY_PLAN.md.

# Annexes

- Annexe A — Catalogue des endpoints API;
- Annexe B — Schéma de données et règles;
- Annexe C — Traçabilité exigences/tests;
- Annexe D — Configuration et déploiement;
- Annexe E — Captures;
- Annexe F — Extraits de code ciblés.
