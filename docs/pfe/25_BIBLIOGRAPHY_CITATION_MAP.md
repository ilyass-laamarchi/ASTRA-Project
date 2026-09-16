# ASTRA PFA — Carte finale des citations bibliographiques

## 1. Résultat du contrôle

L'inspection directe du corps du document Word trouve des appels pour **[1], [2], [3], [4], [10], [11], [12], [13] et [14]**. Les références **[5], [6], [7], [8], [9], [15], [16], [17] et [18]** figurent dans la bibliographie, mais ne sont pas appelées dans le corps. Une occurrence dans la liste bibliographique ne compte pas comme une citation. La carte suivante rend les 18 entrées utiles sans ajouter de source non consultée.

## 2. Carte référence–emplacement–formulation

| Réf. | Source existante | Où citer | Phrase ou contexte recommandé | Citée actuellement ? |
|---:|---|---|---|:---:|
| [1] | Laravel, *Directory Structure*, documentation 13.x | §1.6.1 et/ou §2.4 | Conserver : « La structure de Laravel sépare notamment les routes, les contrôleurs, les modèles et les services applicatifs [1]. » | **OUI** |
| [2] | Laravel, *Broadcasting*, documentation 13.x | §1.6.5 ou §2.9 | Conserver : « Le mécanisme de diffusion de Laravel permet de propager des événements applicatifs vers les clients connectés [2]. » | **OUI** |
| [3] | Laravel, *Laravel Sanctum*, documentation 13.x | §1.6.2, §2.4 ou §2.10 | Conserver : « ASTRA protège ses routes privées au moyen de jetons API gérés par Laravel Sanctum [3]. » | **OUI** |
| [4] | Vue.js, *Introduction*, guide officiel | §1.6.1 ou §3.3 | Conserver : « Vue organise l'interface en composants réactifs, principe appliqué aux vues publiques et aux espaces par rôle d'ASTRA [4]. » | **OUI** |
| [5] | Pinia, *Introduction*, documentation officielle | §3.3 Réalisation du frontend | Ajouter après la description du store : « Pinia centralise l'identité de l'utilisateur et l'état d'authentification partagé entre les vues [5]. » | **NON** |
| [6] | Axios, *Getting Started*, documentation officielle | §3.3 Réalisation du frontend | Ajouter : « Le client Axios commun centralise l'URL de l'API, l'ajout du jeton Bearer et le traitement des réponses 401 [6]. » | **NON** |
| [7] | Docker, *Docker Compose* | §3.9 Déploiement local et DevOps | Ajouter : « Docker Compose décrit l'environnement local multiservice d'ASTRA dans un fichier unique [7]. » | **NON** |
| [8] | Docker, *Compose file reference — services* | §2.4 ou §3.9 | Ajouter : « Les services `database`, `api`, `reverb` et `frontend`, leurs ports, volumes et dépendances sont déclarés selon le modèle de services Compose [8]. » | **NON** |
| [9] | Docker, *Control startup and shutdown order in Compose* | §3.9 | Ajouter : « Le service API attend le contrôle de santé de MySQL au moyen de `depends_on` et de la condition `service_healthy` [9]. » | **NON** |
| [10] | Oracle, *InnoDB Locking*, MySQL 8.4 Reference Manual | §1.6.3 et §2.7.3 | Conserver : « Le verrouillage pessimiste de la ligne du véhicule sérialise le second contrôle de conflit dans la transaction MySQL [10]. » | **OUI** |
| [11] | Stripe, *Checkout Sessions API* | §1.6.4 ou §2.8 | Conserver avec prudence : « L'implémentation prévoit une Checkout Session hébergée pour une réservation confirmée [11]. » | **OUI** |
| [12] | Stripe, *Webhooks* | §1.6.4 ou §2.8 | Conserver : « La mise à jour locale vers `paid` repose sur la réception d'un webhook fournisseur [12]. » | **OUI** |
| [13] | Stripe, *Resolve webhook signature verification errors* | §1.6.4, §2.8 ou §2.10 | Conserver : « La signature du webhook est vérifiée avant le contrôle du type, du montant et de la devise [13]. » | **OUI** |
| [14] | OWASP, *Session Management Cheat Sheet* | §1.6.2 ou §2.10 | Conserver : « La protection, la durée de vie et la révocation des jetons doivent suivre les principes de gestion sûre des sessions [14]. » | **OUI** |
| [15] | OWASP, *OAuth 2.0 Protocol Cheat Sheet* | §3.8 ou §3.12 | Ajouter dans les limites : « Le flux Google utilise actuellement le mode `stateless` et transmet un jeton applicatif dans l'URL de retour ; il devra être durci avant une exposition publique conformément aux recommandations OAuth 2.0 [15]. » | **NON** |
| [16] | OWASP, *HTML5 Security Cheat Sheet* | §3.12 Analyse de sécurité | Ajouter : « Le stockage du jeton Bearer dans `localStorage` augmente son exposition en cas d'injection de script et constitue une dette de sécurité identifiée [16]. » | **NON** |
| [17] | Microsoft, *Playwright documentation — Introduction* | §2.11 ou §3.10 | Ajouter : « Les spécifications Playwright présentes ciblent les parcours de bout en bout dans un navigateur, mais elles n'ont pas été exécutées lors de la validation finale [17]. » | **NON** |
| [18] | Vitest, *Getting Started* | §2.11 ou §3.10 | Ajouter : « Les tests unitaires du frontend sont exécutés avec Vitest ; dix tests répartis dans cinq fichiers ont réussi lors de la validation consignée [18]. » | **NON** |

## 3. Répartition recommandée sans surcharge

- **État de l'art (§1.6) :** [1], [2], [3], [4], [10], [11], [12], [13], [14].
- **Architecture et réalisation frontend (§2.4 et §3.3) :** [5], [6].
- **Déploiement local (§3.9) :** [7], [8], [9].
- **Limites OAuth et stockage navigateur (§3.8 et §3.12) :** [15], [16].
- **Stratégie et résultats de tests (§2.11 et §3.10) :** [17], [18].

Les citations documentaires expliquent la technologie ou la recommandation externe. Les affirmations propres à ASTRA doivent rester appuyées par les fichiers du dépôt et les résultats consignés ; une documentation éditeur ne prouve pas qu'un flux réel a été exécuté.

## 4. Cohérence terminologique française

| Terme à employer | Usage retenu |
|---|---|
| **PFA — Projet de Fin d'Année** | Seule désignation académique du projet |
| **ASTRA** | Nom du projet et de la plateforme, jamais nom de l'entreprise |
| **RELENTIA** | Organisme d'accueil, uniquement dans les passages étayés par les informations de l'étudiant ou de l'entreprise |
| **Responsable ASTRA** | Nom du rôle métier ; `owner` seulement lors de sa définition technique |
| **Administrateur** | Rôle de gouvernance `admin` ; remplacer *Administrator* hors traduction de l'Abstract |
| **Client** | Seul rôle attribué par l'inscription publique et seul rôle autorisé à créer une réservation |
| **interface frontend Vue** / **API backend Laravel** | Définir ces expressions une fois, puis préférer « interface Vue » et « API Laravel » dans le texte courant |
| **application web full stack** | Employer cette expression à la première définition ; préférer ensuite « application web complète » si le sens reste clair |
| **application monopage (SPA)** | Définir `SPA` à la première occurrence : « application monopage (*Single-Page Application*, SPA) » |
| **API REST** | Conserver la forme normalisée « API REST » ; parler de ressources, routes et verbes HTTP lorsque le détail est utile |
| **actualisation périodique** | Équivalent recommandé de *polling* |
| **WebSocket** | Nom du protocole ; employer « diffusion en temps réel par WebSocket » pour décrire le rôle de Reverb |
| **temps réel** | Fonction de réactivité ; ne pas écrire « Reverb validé en conditions réelles » |
| **approche itérative et incrémentale** | Libellé méthodologique justifié par `21_REAL_PROJECT_METHODOLOGY.md` |
| **états de réservation** | en attente (`pending`), confirmée (`confirmed`), refusée (`rejected`), annulée (`cancelled`), terminée (`completed`) |
| **états de paiement** | en attente (`pending`), en traitement (`processing`), payé (`paid`), échoué (`failed`), annulé (`cancelled`), remboursé (`refunded`) |

## 5. Contrôle avant export

- Ajouter les neuf appels absents [5]–[9] et [15]–[18] aux emplacements indiqués.
- Vérifier que chaque numéro [1] à [18] apparaît au moins une fois dans le corps, hors bibliographie.
- Conserver les réserves de maturité pour Stripe, Google OAuth, Reverb et Playwright.
- Supprimer toute source non consultée au lieu de lui créer un appel artificiel.
- Harmoniser le style des 18 références selon le modèle bibliographique ENSI retenu.

MCD VERIFIED: YES

MLD VERIFIED: YES

USE CASE VERIFIED: YES

RESERVATION SEQUENCE VERIFIED: YES

RESERVATION STATES VERIFIED: YES

PAYMENT STATES VERIFIED: YES

FIGURE/TABLE PLAN READY: YES

BIBLIOGRAPHY CITATION MAP READY: YES

REPORT MODELING READY FOR FINAL CORRECTION: YES
