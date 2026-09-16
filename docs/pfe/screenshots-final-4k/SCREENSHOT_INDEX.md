# ASTRA PFA — Index des captures finales 4K

**Projet :** ASTRA  
**Étudiant :** Ilyass Laamarchi  
**Entreprise d’accueil :** RELENTIA  
**Établissement :** ENSI — Génie Informatique  
**Année universitaire :** 2025–2026

## Paramètres de capture

- **Captures desktop :** Chromium via Playwright, viewport CSS 1920 × 1080, facteur de périphérique 2, zoom navigateur 100 %, sortie PNG native 3840 × 2160.
- **Captures mobiles :** viewport CSS 480 × 1040, facteur de périphérique 3, sortie PNG native 1440 × 3120.
- **Données :** base MySQL locale isolée et jeu de démonstration fictif ; aucune donnée de l’instance de développement existante n’a été modifiée.
- **Services externes :** aucune capture ne présente Google OAuth, Stripe ou Reverb comme validé en conditions réelles.
- **Traitement d’image :** aucun étirement, agrandissement ou montage ; chaque PNG provient directement de l’application ASTRA exécutée localement.

## Index

| ID | File | Resolution | Role | Screen | Report Caption | Status |
|---|---|---:|---|---|---|---|
| F01 | [required/F01_HOME_PUBLIC_4K.png](required/F01_HOME_PUBLIC_4K.png) | 3840 × 2160 | Visiteur déconnecté | Accueil public, navigation, proposition de service et recherche | Figure 3.1 — Page d’accueil publique d’ASTRA | READY |
| F02 | [required/F02_CATALOGUE_VEHICLES_4K.png](required/F02_CATALOGUE_VEHICLES_4K.png) | 3840 × 2160 | Visiteur déconnecté | Catalogue, filtres et cartes de véhicules | Figure 3.2 — Consultation et filtrage des véhicules disponibles | READY |
| F03 | [required/F03_INSCRIPTION_CLIENT_4K.png](required/F03_INSCRIPTION_CLIENT_4K.png) | 3840 × 2160 | Anonyme | Formulaire d’inscription sans sélection de rôle | Figure 3.3 — Création d’un compte Client | READY |
| F04 | [required/F04_CREATION_RESERVATION_4K.png](required/F04_CREATION_RESERVATION_4K.png) | 3840 × 2160 | Client — Amine El Mansouri | Véhicule, disponibilité, dates, durée et total avant confirmation | Figure 3.4 — Création d’une réservation dans l’espace Client | READY |
| F05 | [required/F05_DASHBOARD_RESPONSABLE_4K.png](required/F05_DASHBOARD_RESPONSABLE_4K.png) | 3840 × 2160 | Responsable ASTRA (`owner`) | Pilotage opérationnel, indicateurs et alertes | Figure 3.5 — Tableau de bord opérationnel du Responsable ASTRA | READY |
| F06 | [required/F06_DASHBOARD_ADMIN_4K.png](required/F06_DASHBOARD_ADMIN_4K.png) | 3840 × 2160 | Administrateur ASTRA | Indicateurs stratégiques, flotte, clients et revenu de démonstration | Figure 3.6 — Tableau de bord Administrateur | READY |
| O01 | [optional/O01_CAR_DETAIL_4K.png](optional/O01_CAR_DETAIL_4K.png) | 3840 × 2160 | Visiteur déconnecté | Fiche Peugeot 208 et calendrier ouvert | Fiche détaillée d’un véhicule | READY |
| O02 | [optional/O02_LOGIN_4K.png](optional/O02_LOGIN_4K.png) | 3840 × 2160 | Visiteur déconnecté | Formulaire de connexion vide | Interface de connexion à ASTRA | READY |
| O03 | [optional/O03_CLIENT_DASHBOARD_4K.png](optional/O03_CLIENT_DASHBOARD_4K.png) | 3840 × 2160 | Client — Amine El Mansouri | Synthèse personnelle et prochaines réservations | Tableau de bord du Client | READY |
| O04 | [optional/O04_CLIENT_RESERVATIONS_4K.png](optional/O04_CLIENT_RESERVATIONS_4K.png) | 3840 × 2160 | Client — Amine El Mansouri | Historique des réservations fictives | Suivi des réservations du Client | READY |
| O05 | [optional/O05_RESERVATION_CONFLICT_4K.png](optional/O05_RESERVATION_CONFLICT_4K.png) | 3840 × 2160 | Client — Amine El Mansouri | Refus réel d’une réservation concurrente par l’API, HTTP 409 | Gestion d’un conflit de disponibilité | READY |
| O06 | [optional/O06_FLEET_MANAGEMENT_4K.png](optional/O06_FLEET_MANAGEMENT_4K.png) | 3840 × 2160 | Responsable ASTRA (`owner`) | Liste et gestion de la flotte fictive | Interface de gestion de la flotte | READY |
| O07 | [optional/O07_RESERVATION_MANAGEMENT_4K.png](optional/O07_RESERVATION_MANAGEMENT_4K.png) | 3840 × 2160 | Responsable ASTRA (`owner`) | Réservations en attente et actions métier | Traitement opérationnel des réservations | READY |
| O08 | [optional/O08_CLIENT_DIRECTORY_4K.png](optional/O08_CLIENT_DIRECTORY_4K.png) | 3840 × 2160 | Administrateur ASTRA | Annuaire de clients fictifs | Administration et consultation des clients | READY |
| O09 | [optional/O09_SETTINGS_4K.png](optional/O09_SETTINGS_4K.png) | 3840 × 2160 | Administrateur ASTRA | Paramètres du profil avec adresse `example.test` | Paramètres administratifs d’ASTRA | READY |
| O10 | [optional/O10_NOTIFICATIONS_4K.png](optional/O10_NOTIFICATIONS_4K.png) | 3840 × 2160 | Administrateur ASTRA | Centre de notifications applicatif | Centre de notifications d’ASTRA | READY |
| M01 | [mobile/M01_HOME_MOBILE.png](mobile/M01_HOME_MOBILE.png) | 1440 × 3120 | Visiteur déconnecté | Accueil en mise en page mobile | Page d’accueil publique d’ASTRA sur mobile | READY |
| M02 | [mobile/M02_CATALOGUE_MOBILE.png](mobile/M02_CATALOGUE_MOBILE.png) | 1440 × 3120 | Visiteur déconnecté | Catalogue et filtres en mise en page mobile | Catalogue des véhicules sur mobile | READY |
| M03 | [mobile/M03_RESERVATION_MOBILE.png](mobile/M03_RESERVATION_MOBILE.png) | 1440 × 3120 | Client — Amine El Mansouri | Dates, durée et total en mise en page mobile | Création d’une réservation sur mobile | READY |

## Contrôle qualité

- **19 fichiers PNG ouverts et contrôlés :** 19/19.
- **Dimensions conformes :** 19/19.
- **Images vides ou chargements échoués :** 0.
- **Doublons binaires :** 0.
- **Captures desktop 4K natives :** 16/16.
- **Données visibles :** identités et références fictives uniquement.
- **Secrets, mots de passe, jetons ou clés visibles :** aucun.
- **Interface système, terminal, outils de développement ou barre du navigateur :** aucun.

O05 montre volontairement le message produit par un conflit de réservation réel. O10 prouve l’existence de l’interface et des notifications persistées ; cette image ne constitue pas une validation du transport réseau Reverb.
