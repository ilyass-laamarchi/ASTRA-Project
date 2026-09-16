# Base de données et API REST

## 1. Modèle de données

### 1.1 Entités métier

| Table | Rôle | Champs structurants |
|---|---|---|
| users | identité, rôle, activation, profil | email unique, role enum, is_active, avatar_path, notification_preferences |
| categories | segmentation de flotte | name unique, is_active |
| cars | véhicule louable | category_id, registration_number unique, attributs, daily_price, operational_status, is_active |
| car_images | galerie de voiture | car_id, path, is_primary, sort_order |
| reservations | demande/location | reservation_number unique, user_id, car_id, dates, prix figé, statut, notes |
| payments | transaction externe | références uniques, reservation_id, user_id, montant/devise, statut, métadonnées |
| astra_notifications | notification privée | user_id, type, contenu, data JSON, read_at |
| application_settings | paramètres administrables | key primaire, value |
| contact_inquiries | messages publics | coordonnées, message, statut |

### 1.2 Tables techniques

password_reset_tokens, personal_access_tokens, sessions, cache, cache_locks, jobs, job_batches et failed_jobs sont fournies par l’architecture Laravel et ses migrations.

## 2. Relations

- User 1—N Reservation.
- User 1—N Payment.
- User 1—N AstraNotification.
- Category 1—N Car.
- Car 1—N CarImage.
- Car 1—N Reservation.
- Reservation N—1 User.
- Reservation N—1 Car.
- Reservation 1—N Payment.
- Payment N—1 User.

Les suppressions de catégories liées, utilisateurs liés et voitures réservées sont restrictives. Les images et notifications sont supprimées en cascade avec leur parent.

## 3. ERD

~~~mermaid
erDiagram
    USERS ||--o{ RESERVATIONS : effectue
    USERS ||--o{ PAYMENTS : possede
    USERS ||--o{ ASTRA_NOTIFICATIONS : recoit
    CATEGORIES ||--o{ CARS : classe
    CARS ||--o{ CAR_IMAGES : illustre
    CARS ||--o{ RESERVATIONS : concerne
    RESERVATIONS ||--o{ PAYMENTS : regroupe

    USERS {
      bigint id PK
      string email UK
      enum role
      boolean is_active
      json notification_preferences
    }
    CATEGORIES {
      bigint id PK
      string name UK
      boolean is_active
    }
    CARS {
      bigint id PK
      bigint category_id FK
      string registration_number UK
      decimal daily_price
      enum operational_status
      boolean is_active
    }
    CAR_IMAGES {
      bigint id PK
      bigint car_id FK
      string path
      boolean is_primary
      int sort_order
    }
    RESERVATIONS {
      bigint id PK
      string reservation_number UK
      bigint user_id FK
      bigint car_id FK
      date start_date
      date end_date
      int rental_days
      decimal total_amount
      enum status
    }
    PAYMENTS {
      bigint id PK
      bigint reservation_id FK
      bigint user_id FK
      string payment_reference UK
      string provider_session_id UK
      decimal amount
      char currency
      enum status
    }
    ASTRA_NOTIFICATIONS {
      bigint id PK
      bigint user_id FK
      string type
      json data
      timestamp read_at
    }
~~~

## 4. Contraintes d’intégrité importantes

- identifiants fonctionnels uniques : e-mail, immatriculation, numéro de réservation, référence et identifiants de paiement ;
- clés étrangères explicites ;
- index sur états, dates, prix, marque/modèle et activation ;
- index composite reservations(car_id, status, start_date, end_date) ;
- montant de réservation figé lors de la création ;
- enums SQL pour états principaux ;
- verrouillage pessimiste applicatif au-dessus des contraintes relationnelles.

Le schéma ne possède pas de contrainte SQL native empêchant deux intervalles de dates de se chevaucher ; cette garantie est assurée par le service et les transactions. Une charge concurrente MySQL doit faire partie de la recette finale.

## 5. Convention API

- Préfixe : /api.
- Format principal : JSON.
- Authentification : Authorization: Bearer pour Sanctum.
- Validation Laravel : statut 422.
- Non authentifié : 401.
- Non autorisé/propriété : 403.
- Introuvable : 404.
- Conflit de disponibilité ou état concurrent : 409.
- Service externe non configuré : 503.
- Pagination Laravel : data, current_page, last_page, per_page, total et liens.

## 6. Endpoints publics et d’authentification

| Méthode | URI | Fonction | Protection |
|---|---|---|---|
| POST | /api/register | créer un Client | throttle |
| POST | /api/login | obtenir jeton/utilisateur | throttle |
| POST | /api/forgot-password | demander réinitialisation | throttle |
| POST | /api/reset-password | appliquer jeton reset | throttle |
| GET | /api/auth/google/redirect | URL de consentement | public, échoue fermé sans clés |
| GET | /api/auth/google/callback | callback Google | public |
| GET | /api/cars | catalogue filtré/paginé | public |
| GET | /api/cars/{car} | fiche véhicule | public |
| GET/POST | /api/cars/{car}/availability | devis disponibilité | public |
| GET | /api/cars/{car}/unavailable-periods | périodes bloquantes sûres | public |
| GET | /api/categories | catégories actives | public |
| POST | /api/contact | message contact | throttle |
| POST | /api/payments/webhook | webhook Stripe | throttle + signature dans le service |

## 7. Endpoints authentifiés communs

| Méthode | URI | Fonction |
|---|---|---|
| GET | /api/me | identité courante |
| PATCH | /api/profile | profil courant |
| POST | /api/profile/avatar | avatar image |
| PUT | /api/password | changer mot de passe |
| GET/PUT | /api/notification-preferences | préférences |
| GET | /api/notifications | flux privé |
| PATCH | /api/notifications/{notification}/read | lecture d’une notification possédée |
| PATCH | /api/notifications/read-all | lecture globale personnelle |
| POST | /api/logout | révoquer jeton courant |

## 8. Endpoints Client

| Méthode | URI | Fonction |
|---|---|---|
| GET/POST | /api/my-reservations | lister/créer ses réservations |
| GET | /api/my-reservations/{reservation} | détail possédé |
| PATCH | /api/my-reservations/{reservation}/cancel | annuler sa demande pending |
| GET | /api/payment-configuration | disponibilité masquée du checkout |
| POST | /api/reservations/{reservation}/checkout | démarrer son checkout |
| GET | /api/my-payments | lister ses paiements |
| GET | /api/my-payments/{payment} | détail possédé |
| GET | /api/payments/{payment}/receipt | reçu d’un paiement paid possédé |

## 9. Endpoints Responsable et Administrateur

Chaque URI ci-dessous existe sous /api/owner et /api/admin. Le préfixe owner autorise owner,admin ; le préfixe admin autorise admin uniquement.

| Méthode | Suffixe | Fonction |
|---|---|---|
| GET | /dashboard | analytics adaptées au rôle |
| GET/POST | /categories | lister/créer |
| PUT | /categories/{category} | modifier |
| PATCH | /categories/{category}/activation | activer/désactiver |
| GET/POST | /cars | lister/créer |
| GET/PUT | /cars/{car} | détail/modifier |
| PATCH | /cars/{car}/activation | activer/désactiver |
| PATCH | /cars/{car}/operational-status | maintenance/disponibilité |
| POST | /cars/{car}/images | ajouter jusqu’à huit images |
| PATCH | /cars/{car}/images/reorder | réordonner |
| PATCH | /cars/{car}/images/{image}/primary | définir principale |
| DELETE | /cars/{car}/images/{image} | supprimer |
| GET | /clients | recherche/pagination |
| GET | /clients/{user} | dossier Client |
| GET | /reservations | réservations paginées |
| GET | /reservations/{reservation} | détail staff |
| PATCH | /reservations/{reservation}/confirm | confirmer |
| PATCH | /reservations/{reservation}/reject | refuser |
| PATCH | /reservations/{reservation}/cancel | annuler |
| PATCH | /reservations/{reservation}/complete | terminer |
| GET | /payments | paiements paginés |
| GET | /payments/{payment} | détail paiement |

## 10. Endpoints Administrateur exclusifs

| Méthode | URI | Fonction |
|---|---|---|
| PATCH | /api/admin/clients/{user}/activation | activer/désactiver |
| GET/POST | /api/admin/staff | lister/créer Responsable |
| GET/PUT | /api/admin/staff/{user} | lire/modifier staff |
| PATCH | /api/admin/staff/{user}/activation | activer/désactiver |
| POST | /api/admin/payments/{payment}/refund | rembourser |
| GET/PUT | /api/admin/settings | lire/modifier paramètres |
| GET | /api/admin/settings/payment-status | état masqué Stripe |

## 11. Exemples de contrats

### Création de réservation

Entrée autorisée :

~~~json
{
  "car_id": 12,
  "start_date": "2026-09-10",
  "end_date": "2026-09-14",
  "client_message": "Arrivée prévue le matin."
}
~~~

Le client n’envoie ni rental_days, ni daily_price, ni total_amount, ni status.

### Devis de disponibilité

La réponse contient au minimum available, start_date, end_date, rental_days, daily_price et total_amount. En cas de conflit lors de la création finale, l’API renvoie 409.

### Inscription

Champs acceptés : first_name, last_name, email, phone, password, password_confirmation. Les champs role, role_id, is_admin, is_staff, user_type, permissions et is_active sont interdits.

## 12. Sérialisation et confidentialité

CarResource stabilise la vue publique d’une voiture. ReservationResource n’ajoute user et internal_note que pour un utilisateur staff ; rejection_reason est exposé au Client uniquement quand la réservation est rejected. Les périodes publiques n’exposent aucune identité de client.

## 13. Limites et recommandations base/API

1. Ajouter une politique ou une validation stricte du type de cible à l’activation Client.
2. Harmoniser la fiche publique avec la liste sur operational_status.
3. Définir une stratégie d’idempotence fournisseur et stocker l’identifiant d’événement Stripe.
4. Ajouter un journal d’audit des actions administratives.
5. Restreindre CORS par environnement.
6. Générer une spécification OpenAPI versionnée ; elle est absente.
7. Ajouter des tests contractuels MySQL dans un environnement jetable.
8. Documenter la rétention des contacts, notifications, paiements et jetons.

