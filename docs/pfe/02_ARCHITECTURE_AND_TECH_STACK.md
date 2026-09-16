# Architecture et pile technologique

## 1. Vue d’ensemble

ASTRA suit une architecture client–serveur modulaire dans un monorepo. Il ne s’agit ni de microservices ni d’une architecture distribuée à haute disponibilité.

~~~mermaid
flowchart LR
    U[Visiteur / Client / Responsable / Admin]
    V[SPA Vue 3]
    A[API REST Laravel 13]
    R[Laravel Reverb]
    D[(MySQL 8.4)]
    F[(Stockage public)]
    S[Stripe Checkout]
    G[Google OAuth]
    M[Mailer]

    U -->|HTTPS attendu en production| V
    V -->|JSON + Bearer Sanctum| A
    V <-->|WebSocket Echo| R
    A -->|Eloquent / transactions| D
    A --> F
    A -->|événements| R
    A -.->|OAuth si configuré| G
    A -.->|Checkout + webhook signé si configuré| S
    A -.->|réinitialisation| M
~~~

Les flèches pointillées correspondent à des intégrations dont le code existe mais dont les paramètres sont absents ou non validés dans l’environnement audité.

## 2. Architecture logique

### 2.1 Frontend

- **Views** : pages publiques, authentification et espace de travail.
- **Components** : catalogue, calendrier, gestion de flotte, clients, réservations, paiements, paramètres et tableaux de bord.
- **Pinia** : identité et état d’authentification partagé.
- **Vue Router** : navigation et garde ergonomique par rôle.
- **Axios** : client HTTP centralisé, ajout du jeton Bearer et purge locale sur 401.
- **Echo/Pusher client** : souscriptions Reverb.
- **Composables** : synchronisation de disponibilité avec WebSocket et fallback.

### 2.2 Backend

- **Routes** : contrat HTTP et composition des middlewares.
- **Form Requests** : règles d’entrée structurantes pour l’inscription et la réservation.
- **Controllers** : orchestration HTTP, propriété des ressources et sérialisation.
- **Services métier** : disponibilité, états, paiement, notifications et analytics.
- **Models Eloquent** : entités et relations.
- **Resources** : représentation publique des voitures et réservations.
- **Events** : disponibilité, réservation, paiement et notification.
- **Contract/Adapter** : PaymentGatewayInterface et StripePaymentGateway.

### 2.3 Données et persistance

MySQL constitue la cible Compose. Les migrations décrivent les entités métier, les jetons Sanctum, les sessions, la file de tâches et le cache. Les fichiers de véhicules et avatars utilisent le disque public Laravel.

## 3. Flux de réservation

~~~mermaid
sequenceDiagram
    actor C as Client
    participant V as Vue
    participant API as Laravel
    participant AV as AvailabilityService
    participant DB as MySQL
    participant RT as Reverb

    C->>V: Choisit voiture et dates
    V->>API: GET disponibilité
    API->>AV: normaliser et calculer
    AV->>DB: chercher les blocages
    API-->>V: disponible + jours + prix serveur
    C->>V: Confirme la demande
    V->>API: POST /my-reservations
    API->>DB: transaction + verrou voiture
    API->>AV: contrôle final
    AV->>DB: intersection pending/confirmed
    API->>DB: créer pending
    API-->>V: réservation
    API-->>RT: événements et notifications
~~~

La période est semi-ouverte : la date de fin est la date de restitution et n’est pas facturée comme un jour supplémentaire. Deux périodes se chevauchent si existing.start < requested.end et existing.end > requested.start.

## 4. Flux de paiement

~~~mermaid
sequenceDiagram
    actor C as Client
    participant V as Vue
    participant API as Laravel
    participant DB as MySQL
    participant ST as Stripe

    C->>V: Payer une réservation confirmée
    V->>API: POST /reservations/{id}/checkout
    API->>DB: vérifier propriétaire, statut, montant et disponibilité
    API->>ST: créer Checkout Session
    ST-->>API: URL hébergée
    API-->>V: redirection Checkout
    ST->>API: POST webhook signé
    API->>ST: vérification cryptographique via SDK
    API->>DB: verrou paiement, montant/devise, statut paid
    API-->>V: événement temps réel / rechargement
~~~

Le retour navigateur ne rend pas un paiement « payé » ; le webhook constitue l’autorité.

## 5. Technologies et versions observées

| Technologie | Version verrouillée/installée | Usage ASTRA |
|---|---:|---|
| PHP | 8.4.24 lors des tests | runtime backend |
| Laravel Framework | 13.23.0 | API, validation, Eloquent, transactions |
| Laravel Sanctum | 4.3.3 | jetons API |
| Laravel Reverb | 1.11.0 | serveur WebSocket |
| Laravel Socialite | 5.29.0 | OAuth Google |
| Stripe PHP SDK | 21.1.1 | Checkout, webhook, remboursement |
| PHPUnit | 12.5.33 | tests Feature |
| Vue | 3.5.41 | SPA et Composition API |
| Vue Router | 5.2.0 | navigation |
| Pinia | 4.0.3 | état d’authentification |
| Axios | 1.19.0 | HTTP |
| Laravel Echo | 2.4.0 | client d’événements |
| Pusher JS | 8.6.0 | protocole client compatible Reverb |
| Vite | 8.2.1 | développement et build |
| Vitest | 4.1.10 | tests unitaires frontend |
| Playwright | 1.62.1 | scénarios E2E |
| Tailwind CSS | 3.4.19 | utilitaires de style |
| MySQL | image 8.4 | base cible Compose |
| Node.js | image 24-alpine | frontend Compose/build |

## 6. Justification des choix

| Choix | Besoin couvert | Alternative possible | Limite |
|---|---|---|---|
| Laravel | API métier, validation, ORM, sécurité et tests cohérents | Symfony, NestJS | framework serveur unique, montée en compétence requise |
| Vue 3 + SFC | interfaces publiques et métier réactives | React, Angular | plusieurs grands composants restent à découper |
| REST/JSON | contrat simple entre SPA et backend | GraphQL | certaines vues déclenchent plusieurs requêtes |
| MySQL/InnoDB | relations, index, transactions et verrous | PostgreSQL | concurrence réelle non revalidée pendant cet audit |
| Sanctum Bearer | API stateless simple | cookies Sanctum first-party, OAuth interne | localStorage accroît le risque XSS |
| Reverb/Echo | mises à jour sans rafraîchissement | SSE, polling seul | nécessite WebSocket exposé et surveillé |
| Stripe Checkout | externalisation des champs carte | PSP marocain, formulaire PCI | indisponible sans configuration et compatibilité MAD à valider |
| Docker Compose | environnement local reproductible | installation native | pas une preuve de déploiement production |
| Vitest + PHPUnit + Playwright | pyramide de tests | suites propriétaires | E2E non isolés d’une base jetable |

## 7. Déploiement logique actuel

~~~mermaid
flowchart TB
    subgraph Host[Hôte Docker local]
      FE[frontend : Vite, port 5173]
      API[api : artisan serve, port 8000]
      RV[reverb : port 8080]
      DB[(database : MySQL, port 3306)]
      VOL1[(astra_mysql)]
      VOL2[(astra_public_storage)]
      VOL3[(frontend node_modules)]
    end
    FE --> API
    FE --> RV
    API --> DB
    API --> VOL2
    RV --> API
    DB --> VOL1
    FE --> VOL3
~~~

Cette topologie est adaptée au développement. Une cible production doit remplacer les serveurs de développement par des processus supervisés, ajouter TLS/reverse proxy, secrets, sauvegardes, observabilité et pipeline de livraison.

## 8. Décisions d’ingénierie notables

- verrouillage pessimiste de la voiture plutôt qu’un contrôle uniquement côté interface ;
- service unique de disponibilité réutilisé par le catalogue, le devis, la création et la confirmation ;
- interface PaymentGatewayInterface séparant le domaine du fournisseur ;
- événements diffusés après l’écriture métier et encapsulés par rescue : une panne WebSocket n’annule pas la transaction ;
- notifications persistées avant diffusion ;
- accès frontend gardé pour l’ergonomie, mais autorité conservée dans les middlewares Laravel ;
- désactivation logique des utilisateurs, voitures et catégories plutôt que suppression destructive.

## 9. Limites architecturales

- absence de CI/CD, proxy TLS, healthchecks applicatifs complets et supervision ;
- composants Vue volumineux, particulièrement AuthView et WorkspaceView ;
- authentification first-party par jeton localStorage plutôt que cookie HttpOnly ;
- intégrations externes sans validation opérationnelle ;
- aucune stratégie documentée de reprise après sinistre ou de rotation des secrets ;
- aucune journalisation d’audit métier dédiée.

