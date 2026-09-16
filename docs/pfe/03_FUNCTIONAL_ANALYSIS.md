# Analyse fonctionnelle

## 1. Acteurs

### Visiteur

Consulte le site, recherche la flotte, ouvre une fiche, vérifie une période, contacte l’agence, crée un compte Client et peut initier Google OAuth si ce dernier est configuré.

### Client

Hérite des possibilités publiques et gère son profil, ses propres réservations, paiements, reçus et notifications. Il ne peut ni consulter les données d’un autre client ni accéder aux espaces staff.

### Responsable ASTRA

Le rôle technique owner gère l’exploitation : catégories, parc, images, clients en lecture, réservations et paiements en lecture. Il reçoit un tableau de bord opérationnel. Il ne peut pas créer de staff, activer/désactiver un client, modifier les réglages ou rembourser.

### Administrateur

Dispose des opérations du Responsable et ajoute la gestion du staff, l’activation des comptes, les paramètres, l’état du fournisseur de paiement, les remboursements et les indicateurs stratégiques.

### Services externes

Google agit comme fournisseur d’identité, Stripe comme prestataire de paiement hébergé, Reverb comme transport WebSocket et le mailer comme canal de réinitialisation. Ils ne sont pas des utilisateurs humains.

## 2. Exigences fonctionnelles

| ID | Exigence | État |
|---|---|---|
| FR-01 | Consulter les voitures actives et disponibles | Implémenté |
| FR-02 | Rechercher/filtrer/trier/paginer la flotte | Implémenté |
| FR-03 | Consulter une fiche et un calendrier de disponibilité | Implémenté, avec limite sur le statut public de la fiche |
| FR-04 | Créer un compte public exclusivement Client | Implémenté et testé |
| FR-05 | Se connecter localement et refuser les comptes désactivés | Implémenté et testé |
| FR-06 | Réinitialiser le mot de passe | Implémenté ; envoi réel dépend du mailer |
| FR-07 | Utiliser Google OAuth | Présent mais non activé |
| FR-08 | Modifier profil, mot de passe, avatar et préférences | Implémenté et testé |
| FR-09 | Créer une demande de réservation avec prix serveur | Implémenté et testé |
| FR-10 | Empêcher les doubles réservations bloquantes | Implémenté et testé en logique transactionnelle |
| FR-11 | Gérer les transitions de réservation autorisées | Implémenté et testé |
| FR-12 | Annuler sa propre demande pending | Implémenté |
| FR-13 | Payer une réservation confirmée via Stripe Checkout | Présent, non activé dans l’environnement |
| FR-14 | Confirmer le paiement par webhook signé | Implémenté, testé avec double |
| FR-15 | Télécharger un reçu texte pour un paiement paid | Implémenté |
| FR-16 | Recevoir/lire des notifications privées | Implémenté et testé |
| FR-17 | Administrer catégories, voitures et images | Implémenté |
| FR-18 | Rechercher et consulter les clients | Implémenté ; synchronisation périodique frontend |
| FR-19 | Administrer le staff | Administrateur uniquement, implémenté |
| FR-20 | Consulter des tableaux de bord par rôle | Implémenté et testé |
| FR-21 | Gérer les paramètres d’agence | Administrateur uniquement, implémenté |
| FR-22 | Envoyer un message public de contact | Implémenté et testé |

## 3. Exigences non fonctionnelles

| ID | Exigence | Réponse actuelle | Évaluation |
|---|---|---|---|
| NFR-01 Sécurité | authentification, rôle et propriété côté serveur | Sanctum + EnsureRole + abort_unless | Solide, améliorations XSS/OAuth/CORS requises |
| NFR-02 Intégrité | éviter chevauchement et prix manipulé | transactions, verrous, calcul serveur | Solide |
| NFR-03 Disponibilité UI | actualiser les changements | Reverb + polling/focus/visibility | Conçu ; transport non vérifié |
| NFR-04 Maintenabilité | séparation métier | services, requests, resources, adapter paiement | Bonne base ; grands composants Vue |
| NFR-05 Testabilité | tests automatisés | PHPUnit, Vitest, Playwright | Backend/frontend unitaire vérifiés |
| NFR-06 Portabilité | environnement reproductible | Docker Compose | Développement confirmé |
| NFR-07 Accessibilité | labels, aria, navigation | plusieurs attributs présents | Audit WCAG complet absent |
| NFR-08 Performance | pagination/index/chargement | pagination et index, images responsives par endroits | Aucun benchmark |
| NFR-09 Observabilité | logs et santé | logs Laravel/Docker seulement | Partiel |
| NFR-10 Confidentialité | minimiser exposition | ressources client-safe et propriété | Politique de conservation/RGPD absente |

## 4. Matrice des permissions

| Action | Visiteur | Client | Responsable | Admin |
|---|:---:|:---:|:---:|:---:|
| Voir catalogue/catégories | Oui | Oui | Oui | Oui |
| Vérifier disponibilité | Oui | Oui | Oui | Oui |
| S’inscrire | Oui, comme Client | — | — | — |
| Créer réservation | Non | Propre compte | Non via routes client | Non via routes client |
| Lire réservations | Non | Propres | Toutes | Toutes |
| Confirmer/refuser/terminer | Non | Non | Oui | Oui |
| Annuler pending propre | Non | Oui | — | — |
| Annuler côté staff | Non | Non | Oui | Oui |
| Checkout/reçu | Non | Propre paiement | Non | Non |
| Voir paiements | Non | Propres | Tous | Tous |
| Rembourser | Non | Non | Non | Oui |
| Gérer flotte/catégories | Non | Non | Oui | Oui |
| Consulter clients | Non | Non | Oui | Oui |
| Activer client | Non | Non | Non | Oui |
| Gérer staff | Non | Non | Non | Oui |
| Paramètres agence | Non | Non | Non | Oui |
| Analytics opérationnels | Non | Non | Oui | Oui via routes compatibles |
| Analytics stratégiques | Non | Non | Non | Oui |

## 5. Cas d’utilisation principaux

### UC-01 — Inscription publique sécurisée

**Précondition** : e-mail non utilisé.  
**Scénario** :

1. Le visiteur fournit identité, téléphone et mot de passe confirmé.
2. Vue envoie uniquement les champs autorisés.
3. Laravel rejette explicitement tout champ de rôle ou d’activation.
4. Le contrôleur crée un utilisateur role=client et is_active=true.
5. Sanctum délivre un jeton.

**Alternatives** : validation 422 ; tentative d’injection de rôle refusée.  
**Postcondition** : un Client authentifié existe.

### UC-02 — Réservation d’un véhicule

**Préconditions** : Client actif et authentifié ; voiture active/opérationnelle ; dates futures valides.  
**Scénario** :

1. Le client demande un devis.
2. Le serveur normalise les dates et cherche les périodes pending/confirmed.
3. Le serveur calcule jours, tarif journalier et total.
4. À la confirmation, une transaction verrouille la voiture et répète le contrôle.
5. Une réservation pending est créée.
6. Le staff est notifié et les calendriers sont rafraîchis.

**Alternatives** : 409 en cas de conflit ; 422 pour date invalide ; 401/403 pour accès interdit.

### UC-03 — Traitement staff

1. Responsable/Admin consulte les demandes.
2. Il confirme, refuse ou annule selon l’état courant.
3. Le service verrouille l’enregistrement et contrôle la transition.
4. Pour une confirmation, le conflit est recalculé.
5. Le Client reçoit une notification ; la disponibilité est actualisée.

### UC-04 — Paiement

1. Le Client choisit sa réservation confirmed et non payée.
2. Laravel vérifie propriété, configuration, état et disponibilité.
3. Le montant de la base est transmis à Stripe Checkout.
4. Stripe envoie un webhook signé.
5. Laravel vérifie signature, session, montant et devise puis marque paid.

**État d’audit** : logique présente ; aucun flux Stripe réel exécuté.

### UC-05 — Administration des clients

1. Admin/Responsable demande une page de clients triée par identifiant décroissant.
2. L’API renvoie les métadonnées de pagination.
3. Le frontend permet recherche et détail.
4. Un rafraîchissement périodique fait apparaître les nouvelles inscriptions.
5. Seul l’Admin dispose de l’action d’activation.

## 6. Machine à états de réservation

~~~mermaid
stateDiagram-v2
    [*] --> pending
    pending --> confirmed
    pending --> rejected
    pending --> cancelled
    confirmed --> cancelled
    confirmed --> completed
    rejected --> [*]
    cancelled --> [*]
    completed --> [*]
~~~

Les états pending et confirmed bloquent les dates. Les états rejected, cancelled et completed libèrent la période.

## 7. Machine à états de paiement

~~~mermaid
stateDiagram-v2
    [*] --> pending
    pending --> processing: Checkout créé
    processing --> paid: webhook valide
    processing --> failed
    processing --> cancelled
    paid --> refunded: Admin + Stripe
~~~

Les branches failed/cancelled existent dans le schéma, mais le service courant ne traite explicitement que checkout.session.completed pour la confirmation ; les autres événements constituent une extension.

## 8. Règles métier essentielles

- l’inscription publique ne choisit jamais un rôle ;
- seul le backend fixe le prix et le nombre de jours ;
- date début < date fin et date début au moins aujourd’hui ;
- période semi-ouverte [début, fin) ;
- pending et confirmed sont bloquants ;
- une réservation ne peut être payée que si elle est confirmed ;
- le succès navigateur ne confirme pas un paiement ;
- les opérations staff et admin sont autorisées par middleware ;
- les ressources client sont filtrées par user_id ;
- la désactivation remplace la suppression des comptes.

## 9. Limites fonctionnelles

- « Se souvenir de moi » n’a pas d’effet serveur.
- Google, Stripe et e-mail de production sont non disponibles dans la configuration auditée.
- aucune gestion de contrat signé, caution, état des lieux, amende, assurance ou disponibilité multi-agence ;
- aucun module d’export comptable ou PDF de facture ; le reçu est un fichier texte ;
- aucune suppression/archivage RGPD ni vérification d’e-mail ;
- aucune preuve de support multilingue complet ;
- aucun engagement réel « assistance 24/7 » ne peut être déduit du code.

