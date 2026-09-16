# 15 — Traçabilité des exigences

## Légende

- **Vérifié** : preuve de code et test exécuté ou observation directe suffisante.
- **Implémenté** : code présent, sans validation opérationnelle complète.
- **Partiel** : une partie du besoin manque ou reste incohérente.
- **Architecture only** : intégration structurée dans le code sans configuration externe validée.
- **Non vérifié** : preuve d’exécution absente.

## Matrice

| ID | Exigence | Acteur | Preuve d’implémentation | Preuve de vérification | Statut |
|---|---|---|---|---|---|
| FR-01 | Consulter les voitures actives et disponibles | Visiteur | CarController, catalogue | tests sur véhicules inactifs | Vérifié |
| FR-02 | Rechercher, filtrer, trier et paginer la flotte | Visiteur | vue catalogue et paramètres API | code + build; E2E non relancé | Implémenté |
| FR-03 | Consulter une fiche et son calendrier | Visiteur | route publique show, disponibilité | filtre operational_status moins strict | Partiel |
| FR-04 | Créer un compte public exclusivement Client | Visiteur | RegisterRequest, AuthController | tests rôle client et injections | Vérifié |
| FR-05 | Se connecter et refuser les comptes désactivés | Utilisateur | AuthController, store auth | tests login/inactif; remember non appliqué | Partiel |
| FR-06 | Réinitialiser le mot de passe | Utilisateur | routes/contrôleurs password | pas d’envoi mail réel | Partiel |
| FR-07 | Utiliser Google OAuth | Utilisateur | Socialite et callbacks | test sans config; clés absentes | Architecture only |
| FR-08 | Gérer profil, mot de passe, avatar et préférences | Authentifié | routes et contrôleurs profil | test avatar présent | Vérifié |
| FR-09 | Créer une réservation avec prix serveur | Client | transaction, verrou, calcul | tests prix, bornes et visibilité | Vérifié |
| FR-10 | Empêcher les doubles réservations bloquantes | Client | logique de chevauchement/verrou | AvailabilitySystemTest | Vérifié |
| FR-11 | Appliquer les transitions de réservation autorisées | Client/owner/admin | ReservationStatusService | tests de workflow | Vérifié |
| FR-12 | Annuler sa propre demande pending | Client | endpoint et contrôle de propriété | tests d’isolation/workflow | Vérifié |
| FR-13 | Payer une réservation confirmée via Checkout | Client | PaymentGateway, Stripe adapter | précondition testée; Stripe absent | Partiel |
| FR-14 | Confirmer le paiement par webhook signé | Système | vérification signature/montant/devise | passerelle simulée; Stripe réel absent | Partiel |
| FR-15 | Télécharger un reçu texte d’un paiement paid | Client | endpoint de reçu | code présent; E2E non relancé | Implémenté |
| FR-16 | Recevoir et lire des notifications privées | Authentifié | table, service, Echo/Reverb | persistance testée; transport non exercé | Partiel |
| FR-17 | Administrer catégories, véhicules et images | Owner/admin | routes owner et contrôleurs | code présent | Implémenté |
| FR-18 | Rechercher et consulter les clients | Owner/admin | ManagementController/ClientManager | test annuaire, pagination et recherche | Vérifié |
| FR-19 | Administrer le staff | Admin | routes/méthodes admin | autorisation testée partiellement | Implémenté |
| FR-20 | Consulter les tableaux de bord selon le rôle | Client/owner/admin | endpoints et vues dashboard | DashboardAnalyticsTest | Vérifié |
| FR-21 | Gérer les paramètres d’agence | Admin | routes et vues de paramètres | code présent | Implémenté |
| FR-22 | Envoyer un message public de contact | Visiteur | ContactController, contact_inquiries | FunctionalCompletionTest | Vérifié |

## Exigences non fonctionnelles

| ID | Exigence | Preuve | Statut |
|---|---|---|---|
| NFR-01 | Autorisation serveur | Sanctum + middlewares de rôle | Vérifié |
| NFR-02 | Cohérence concurrente | transaction + verrou + tests | Vérifié |
| NFR-03 | Validation des entrées | Form Requests/validate | Vérifié |
| NFR-04 | Responsive | styles et captures 390/768/1440/1920 | Implémenté |
| NFR-05 | Maintenabilité | séparation frontend/API/gateway | Implémenté |
| NFR-06 | Testabilité | PHPUnit, Vitest, Playwright | Vérifié pour PHPUnit/Vitest |
| NFR-07 | Reproductibilité locale | Docker Compose valide | Vérifié |
| NFR-08 | Sécurité de production | TLS, secrets, CSP, CORS | Non vérifié |
| NFR-09 | Haute disponibilité | aucune preuve | Non vérifié |
| NFR-10 | Performance | aucune mesure | Non vérifié |
| NFR-11 | Accessibilité | aucun audit | Non vérifié |
| NFR-12 | Observabilité | aucune pile documentée | Non vérifié |

## Couverture des exigences critiques

Les exigences les mieux couvertes sont l’inscription client, la disponibilité, la création de réservation, l’isolation, les transitions et l’analytique de base. Les écarts prioritaires sont la session « remember », OAuth, intégrations externes réelles, activation ciblée, idempotence du remboursement et production.

## Règle de maintenance

Toute nouvelle exigence doit recevoir :

1. un identifiant;
2. un critère d’acceptation;
3. une preuve de conception;
4. une preuve de code;
5. un test ou une justification;
6. un statut daté.
