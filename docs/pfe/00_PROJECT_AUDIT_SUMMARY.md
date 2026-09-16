# Audit exécutif du projet ASTRA

Date de l’audit : 1 septembre 2026  
Périmètre : instantané local du projet ASTRA, sans modification du code de production.

## 1. Nature réelle du projet

ASTRA est une application web monopage de location automobile destinée à une agence située à Tanger. Elle associe :

- une SPA Vue 3 pour le site public et les espaces métier ;
- une API REST Laravel 13 ;
- une base MySQL 8.4 en environnement Docker Compose ;
- une authentification par jetons Laravel Sanctum ;
- trois rôles : Client, Responsable ASTRA (valeur technique owner) et Administrateur ;
- un moteur de disponibilité et de réservation avec calcul tarifaire serveur ;
- une architecture de paiement Stripe Checkout ;
- une diffusion temps réel par Laravel Reverb/Echo, complétée par du polling ;
- des tests PHPUnit, Vitest et des scénarios Playwright présents dans le dépôt.

Le fuseau métier et technique attendu est Africa/Casablanca. Les montants sont exprimés en MAD.

## 2. Capacités effectivement implémentées

| Domaine | État constaté | Preuve principale |
|---|---|---|
| Catalogue public | Implémenté : recherche, filtres, tri, pagination, fiche véhicule | backend/app/Http/Controllers/Api/CarController.php ; frontend/src/views/CarsView.vue |
| Inscription publique | Implémentée et forcée au rôle Client | RegisterRequest.php ; AuthController.php ; AstraApiTest.php |
| Authentification locale | Implémentée avec jetons Sanctum, désactivation et révocation | AuthController.php ; frontend/src/stores/auth.js |
| Google OAuth | Code présent, mais clés absentes au moment de l’audit | AuthController.php ; configuration runtime vérifiée par booléens |
| Réservation | Implémentée avec période semi-ouverte, prix serveur, transactions et verrous | CarAvailabilityService.php ; ReservationController.php |
| Workflow de réservation | Implémenté : pending, confirmed, rejected, cancelled, completed | ReservationStatusService.php |
| Paiement Stripe | Architecture et adaptateur implémentés ; non activés faute de clés | PaymentService.php ; StripePaymentGateway.php |
| Notifications | Stockage privé, préférences, lecture et événements Reverb | NotificationController.php ; NotificationService.php |
| Espaces Client/Responsable/Admin | Implémentés avec vues et API adaptées au rôle | WorkspaceView.vue ; ManagementController.php |
| Administration des clients | Recherche, pagination, détail, activation ; nouveaux comptes en tête | ClientManager.vue ; ManagementController.php |
| Tests automatiques | 48 tests backend et 10 tests frontend exécutés avec succès | docs/pfe/06_TESTS_AND_RESULTS.md |
| Déploiement local | Stack Compose cohérente à quatre services | compose.yaml |

## 3. Niveau de maturité

ASTRA constitue un prototype fonctionnel avancé et défendable comme PFE. Le cœur métier — rôles, flotte, disponibilité, réservation, transitions, tableaux de bord et isolation des données — est soutenu par du code et des tests. Il ne faut cependant pas le présenter comme un service de production déjà exploité :

- aucun reverse proxy HTTPS, domaine, supervision, sauvegarde ou pipeline CI/CD n’est fourni ;
- l’instance auditée est arrêtée ;
- Stripe et Google OAuth ne disposent pas de leurs paramètres runtime ;
- le mailer est configuré sur log en local ;
- les scénarios Playwright dépendent d’une stack MySQL active et certains créent des données persistantes ; ils n’ont donc pas été relancés pendant cet audit documentaire non destructif ;
- aucune mesure réelle d’utilisateurs, de disponibilité, de temps de réponse ou de gain commercial n’est disponible.

Estimation qualitative de préparation technique du dossier PFE : **85 %**. Cette valeur est une appréciation documentaire, pas une métrique de qualité logicielle ni un taux d’avancement contractuel.

## 4. Contributions techniques les plus solides

1. **Protection des inscriptions publiques** : le navigateur n’envoie aucun rôle, le FormRequest interdit les champs d’élévation et le contrôleur impose Client.
2. **Prévention des conflits de réservation** : règle d’intersection unique, périodes semi-ouvertes, recalcul côté serveur, transaction et verrouillage pessimiste de la voiture.
3. **Machine à états métier** : transitions de réservation centralisées, validées et propagées aux interfaces.
4. **Paiement non fiable au navigateur** : montant issu de la réservation, Checkout hébergé et confirmation exclusivement par webhook signé.
5. **Isolation multi-rôle** : middleware Laravel, contrôles de propriété et ressources API filtrées.
6. **Synchronisation tolérante aux pannes** : Reverb/Echo pour la réactivité, polling/focus/visibility comme solution de repli.
7. **Tableaux de bord fondés sur les données** : indicateurs opérationnels pour le Responsable et stratégiques pour l’Administrateur.
8. **Validation automatisée** : tests de sécurité, disponibilité, concurrence logique, tableaux de bord, profils et complétude fonctionnelle.

## 5. Limitations majeures à déclarer

- Le bouton « Se souvenir de moi » est visuel : sa valeur n’est pas envoyée au backend et aucune durée de jeton distincte n’est appliquée.
- Les jetons Sanctum sont stockés dans localStorage, ce qui augmente l’impact potentiel d’une faille XSS.
- Google OAuth utilise stateless et transmet le jeton applicatif dans l’URL de retour ; la protection state et le transfert du jeton doivent être renforcés.
- La politique CORS backend accepte toutes les origines dans l’instantané.
- Le détail public d’une voiture ne vérifie que is_active, contrairement à la liste qui vérifie aussi operational_status=available.
- L’endpoint d’activation client n’impose pas dans la méthode que la cible soit effectivement un client.
- Le flux de remboursement appelle Stripe puis met à jour la base sans clé d’idempotence explicite ni journal d’événements fournisseur.
- Aucun journal métier d’audit, MFA, vérification d’e-mail, politique CSP ou en-tête de sécurité applicatif n’est visible.
- Le fichier Compose contient des identifiants locaux en clair. Aucune valeur n’est reproduite dans ce dossier.

Ces points sont des recommandations ou dettes techniques ; ils ne remettent pas en cause les résultats des tests exécutés.

## 6. Baseline vs Repository Differences

| Baseline fournie | Réalité du dépôt |
|---|---|
| Laravel backend | Confirmé, version verrouillée 13.23.0 |
| Vue 3 | Confirmé, version installée 3.5.41 |
| MySQL | Confirmé dans Compose, image 8.4 ; les tests isolés utilisent SQLite en mémoire |
| Docker/Docker Compose | Confirmé, quatre services : database, api, reverb, frontend |
| Google OAuth | Architecture confirmée, disponibilité runtime non confirmée car clés absentes |
| Stripe | Architecture confirmée, disponibilité runtime non confirmée car clés absentes |
| Temps réel Reverb/Echo | Code et paramètres locaux présents ; transport non exercé pendant cet audit car stack arrêtée |
| Dépôt Git | Différence importante : aucun répertoire .git n’est présent dans cet instantané |
| Responsable ASTRA | Valeur technique owner dans le code et les routes |

## 7. Conclusion d’audit

Le projet est suffisamment riche pour soutenir un rapport PFE centré sur la conception d’une plateforme transactionnelle de location automobile. Le rapport doit valoriser les garanties métier démontrées — sécurité des rôles, cohérence des réservations, calcul serveur, architecture de paiement et synchronisation — tout en distinguant clairement le code prêt à être configuré des services externes réellement activés.

