# Faits du projet et système de preuves

## 1. Méthode

Chaque affirmation de ce dossier est classée selon l’une des catégories suivantes :

- **Implémenté** : preuve directe dans le code courant.
- **Testé pendant l’audit** : commande exécutée le 1 septembre 2026 avec résultat observé.
- **Présent mais non activé** : code disponible, configuration runtime manquante.
- **NON VÉRIFIÉ / À CONFIRMER** : dépend d’une infrastructure, d’un tiers ou d’une information absente.
- **Absent** : aucun artefact correspondant trouvé dans le périmètre audité.

Le dépôt a été inspecté sans afficher les valeurs de .env. Seuls les noms de variables et des indicateurs booléens de présence ont été utilisés.

## 2. Mesures de l’instantané

| Mesure | Résultat | Méthode |
|---|---:|---|
| Fichiers de production couverts par le checksum initial | 134 | app, config, database, routes, tests, manifestes et frontend src/tests |
| Empreinte SHA-256 agrégée initiale | 31F5E03321AA855E8D77FB370EF8D97CD96CA9E0465D97655267BF10EB5E6252 | chemins triés + SHA-256 par fichier |
| Fichiers PHP applicatifs/tests comptés | 59 | backend/app, routes, database, tests |
| Lignes PHP comptées | 3 311 | mesure physique PowerShell |
| Fichiers Vue | 24 | frontend/src |
| Lignes Vue | 6 808 | mesure physique PowerShell |
| Fichiers JavaScript applicatifs/tests | 26 | frontend/src et frontend/tests |
| Lignes JavaScript | 906 | mesure physique PowerShell |
| Routes Laravel | 99 | artisan route:list --json |
| Routes API | 93 | filtrage du préfixe api/ |
| Routes protégées par Sanctum | 80 | analyse des middlewares résolus |
| Routes admin uniquement | 35 | EnsureRole:admin |
| Routes Responsable ou Admin | 25 | EnsureRole:owner,admin |
| Routes Client uniquement | 9 | EnsureRole:client |
| Routes explicitement limitées | 6 | middleware throttle |

Les nombres de lignes décrivent l’instantané ; ils ne constituent pas une mesure de qualité.

## 3. Carte des preuves

| Sujet | Preuve de conception/implémentation | Preuve de test |
|---|---|---|
| Composition multi-conteneurs | compose.yaml | docker compose config --quiet : succès |
| Versions backend | backend/composer.json ; backend/composer.lock | PHPUnit indique PHP 8.4.24 et PHPUnit 12.5.33 |
| Versions frontend | frontend/package.json ; frontend/package-lock.json | Vite 8.2.1 et Vitest 4.1.10 observés |
| API REST | backend/routes/api.php | 93 routes API résolues par Artisan |
| Middleware de rôle | backend/app/Http/Middleware/EnsureRole.php ; routes/api.php:31-91 | AstraApiTest ; DashboardAnalyticsTest |
| Inscription Client uniquement | RegisterRequest.php:17-31 ; AuthController.php:21-28 ; auth.js | AstraApiTest.php:64, 103, 111 ; auth-security.spec.js |
| Authentification Sanctum | User.php ; AuthController.php ; api.js | tests de login, désactivation, propriété |
| Catalogue | CarController.php ; CarsView.vue ; CarDetailView.vue | AstraApiTest ; public.spec.js ; fleet.spec.js |
| Calcul des dates | CarAvailabilityService.php | AvailabilitySystemTest, dont date Casablanca |
| Réservation transactionnelle | ReservationController.php:31-62 | AvailabilitySystemTest ; AstraApiTest |
| États de réservation | ReservationStatusService.php | AvailabilitySystemTest |
| Paiement | PaymentService.php ; StripePaymentGateway.php | test_checkout_configuration... ; test_client_cannot_pay... |
| Webhook signé | StripePaymentGateway.php:31-36 ; PaymentService.php:71-87 | passerelle simulée dans AvailabilitySystemTest |
| Temps réel | Events/* ; routes/channels.php ; realtime.js | événements simulés/factices backend ; E2E présent, non relancé |
| Notifications | NotificationService.php ; NotificationController.php | FunctionalCompletionTest |
| Tableaux de bord | DashboardAnalyticsService.php ; DashboardHome.vue | DashboardAnalyticsTest |
| Synchronisation des clients admin | ManagementController.php:120-148 ; ClientManager.vue | AstraApiTest.php:72 ; admin-client-sync.spec.js présent |
| Build frontend | Vite config et manifestes | npm run build : succès |

## 4. Architecture de fichiers observée

~~~
ASTRA PROJECT codex/
├── backend/
│   ├── app/
│   │   ├── Console/Commands/
│   │   ├── Contracts/
│   │   ├── Events/
│   │   ├── Http/Controllers/Api/
│   │   ├── Http/Middleware/
│   │   ├── Http/Requests/
│   │   ├── Http/Resources/
│   │   ├── Models/
│   │   └── Services/
│   ├── config/
│   ├── database/{migrations,seeders,factories}/
│   ├── routes/
│   └── tests/Feature/
├── frontend/
│   ├── public/assets/
│   ├── src/{components,composables,lib,router,stores,views}/
│   └── tests/e2e/
├── docs/
├── compose.yaml
└── README.md
~~~

## 5. Faits de configuration runtime

Contrôle exécuté par Laravel avec sortie limitée à des booléens et des noms de drivers :

| Élément | Valeur non sensible observée |
|---|---|
| Environnement | local |
| Debug | activé |
| Base | mysql |
| Queue | database |
| Broadcasting | reverb |
| Mailer | log |
| Identifiants Reverb | présents |
| Identifiants Google | absents |
| Clés publiques/secrètes/webhook Stripe | absentes |

Conséquence : Reverb est configuré localement, mais l’exécution réseau n’a pas été vérifiée ; Google et Stripe doivent être considérés comme non opérationnels dans cet environnement.

## 6. Commandes réellement exécutées

| Commande/contrôle | Résultat |
|---|---|
| docker compose ps --format json | aucune instance en cours |
| docker compose config --quiet | succès ; quatre services résolus |
| php vendor/bin/phpunit tests/Feature dans image isolée | 48 tests, 193 assertions, succès |
| npm test -- --run dans Node 24 | 5 fichiers, 10 tests, succès |
| npm run build dans Node 24 | succès, 1 847 modules transformés |
| php artisan route:list --json | succès, 99 routes |
| inspection des variables runtime | uniquement présence/absence, aucune valeur secrète |

Le répertoire dist généré pour le contrôle a été supprimé après le build.

## 7. Conflits ou ambiguïtés

- L’instantané n’est pas un dépôt Git : git status et l’historique ne sont pas disponibles.
- Les commentaires et guides existants peuvent décrire une intention ; les contrôleurs, routes, migrations et tests restent prioritaires.
- « Responsable ASTRA » correspond au rôle technique owner.
- Le mot « temps réel » désigne une architecture Reverb/Echo avec fallback ; son transport réseau n’a pas été exécuté pendant cet audit.
- « Paiement » désigne un flux Stripe codé et testé avec double de test ; aucun paiement externe réel n’a été effectué.
- Les comptes et données des seeders sont des données de démonstration, pas des utilisateurs ou résultats réels.

## 8. Règles de réutilisation dans le rapport

Une phrase peut être présentée comme résultat mesuré uniquement si elle renvoie à la section 6. Les métriques commerciales, le nombre de clients réels, l’uptime, les gains de temps et les performances restent **NON VÉRIFIÉ / À CONFIRMER**. Les références à des services externes doivent préciser « architecture implémentée » tant qu’un test avec la configuration cible n’est pas produit.

## 9. Contrôle de non-modification

Le second passage a confirmé qu’aucun fichier des répertoires de production audités (backend app/config/database/routes/tests et frontend src/tests) ne possède une date de modification postérieure à la création du premier livrable docs/pfe. Les seules écritures de cette phase concernent docs/pfe ; le répertoire frontend/dist produit par le build de vérification avait déjà été supprimé. Ce contrôle complète l’empreinte initiale dans un instantané dépourvu de métadonnées Git.
