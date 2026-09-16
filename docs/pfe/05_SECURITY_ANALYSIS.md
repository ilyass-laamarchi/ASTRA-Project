# 05 — Analyse de sécurité

## 1. Périmètre et méthode

Cette analyse couvre le code Laravel, le client Vue, les routes, la configuration versionnée, les migrations et les tests automatisés. Elle ne constitue ni un test d’intrusion externe ni une certification. Les secrets locaux n’ont pas été copiés dans ce dossier.

## 2. Contrôles effectivement observés

| Domaine | Contrôle observé | Preuve principale | Évaluation |
|---|---|---|---|
| Authentification | Jetons personnels Sanctum protégés par middleware | routes/api.php, AuthController | Implémenté |
| Comptes inactifs | Connexion refusée si le compte est désactivé | AuthController, AstraApiTest | Testé |
| Inscription publique | Rôle client imposé côté serveur, champs de rôle interdits | RegisterRequest, AuthController, AstraApiTest | Fort |
| Autorisation | Middlewares de rôle côté API; gardes côté routeur Vue | routes/api.php, router/index.js | Implémenté |
| Isolation client | Accès aux réservations limité au propriétaire de la ressource | ReservationPolicy/contrôleurs, tests | Testé |
| Validation | Form Requests et validations serveur | app/Http/Requests | Implémenté |
| Concurrence réservation | Transaction, verrou de ligne et deuxième vérification de chevauchement | ReservationController/service concerné | Fort |
| Prix | Montant et durée recalculés côté serveur | logique de réservation, tests | Testé |
| Paiement | Montant issu de la base; signature webhook et montant/devise vérifiés | StripePaymentGateway, PaymentController | Bien conçu |
| Mot de passe | Hachage Laravel; règle serveur minimale de 10 caractères | modèles/requêtes | Implémenté |
| Réinitialisation | Flux de mot de passe oublié présent | contrôleurs/routes d’authentification | Implémenté |
| Limitation | Throttling sur six routes sensibles | routes/api.php | Présent |
| Téléversement | Validation de l’avatar | profil/utilisateur | Présent |

## 3. Modèle de menace synthétique

Actifs principaux : comptes, rôles, données personnelles, véhicules, réservations, paiements, notifications et paramètres applicatifs.

Frontières de confiance :

1. navigateur vers API Laravel;
2. API vers MySQL;
3. API vers Google OAuth;
4. API vers Stripe;
5. API/Reverb vers clients temps réel;
6. administrateurs et responsables vers opérations privilégiées.

Menaces principales : vol de jeton, élévation de privilèges, réservation concurrente, falsification du prix, webhook forgé, exposition de données entre clients, mauvaise configuration de production et indisponibilité.

## 4. Risques et limites

| Priorité | Constat vérifié | Risque | Recommandation |
|---|---|---|---|
| Critique avant production | Configuration locale en mode debug | Fuite d’informations détaillées en cas d’erreur | Désactiver APP_DEBUG, contrôler APP_ENV et les logs au déploiement |
| Haute | Jeton Bearer conservé dans localStorage | Un XSS pourrait exfiltrer le jeton | Préférer cookie HttpOnly/Secure/SameSite avec stratégie CSRF, ou renforcer fortement CSP et hygiène XSS |
| Haute | OAuth utilise stateless et transmet le jeton applicatif dans l’URL de retour | Absence de protection state et traces possibles dans historique, logs ou référent | Employer state/PKCE et échanger un code à usage unique côté serveur |
| Haute | CORS autorise une origine générique | Surface d’accès inter-origines trop large | Restreindre aux domaines exacts par environnement |
| Haute | Aucune couche TLS/reverse proxy de production versionnée | Confidentialité et durcissement non démontrés | Déployer derrière HTTPS, HSTS et en-têtes de sécurité |
| Moyenne | « Se souvenir de moi » est seulement visuel | Comportement trompeur et durée de session non maîtrisée | Implémenter une politique de durée explicite ou retirer l’option |
| Moyenne | Règle HTML minlength 8, serveur 10 | Validation tardive et mauvaise UX | Aligner le client sur la règle serveur |
| Moyenne | L’activation d’un utilisateur par l’admin ne vérifie pas explicitement que la cible est cliente | Action possible sur un rôle non prévu si l’identifiant est manipulé | Ajouter une policy ou un contrôle de rôle cible |
| Moyenne | La route publique de détail d’un véhicule vérifie actif mais pas explicitement disponible, contrairement à la liste | Visibilité incohérente d’un véhicule indisponible | Harmoniser le filtre opérationnel |
| Moyenne | Remboursement fournisseur puis mise à jour locale sans journal d’idempotence explicite | État divergent lors d’une panne intermédiaire | Clé d’idempotence, journal d’événements et procédure de rapprochement |
| Moyenne | Pas d’audit log métier des actions privilégiées | Traçabilité administrative limitée | Journal immuable des changements critiques |
| Moyenne | Pas de MFA ni vérification d’adresse e-mail observées | Risque accru de prise de compte | Ajouter vérification e-mail et MFA pour rôles privilégiés |
| Moyenne | Pas de CSP ni jeu documenté d’en-têtes de sécurité | Défense navigateur incomplète | CSP, frame-ancestors, nosniff, Referrer-Policy, Permissions-Policy |
| Moyenne | Identifiants locaux de base de données présents dans Compose | Mauvaise réutilisation possible en production | Secrets de déploiement hors dépôt et rotation par environnement |
| Faible à moyenne | E-mail configuré vers le journal local | Aucun envoi réel en production | Fournisseur mail, SPF/DKIM/DMARC et tests |

## 5. Référentiel OWASP

- L’usage de localStorage pour un identifiant de session doit être réévalué selon les recommandations OWASP Session Management et HTML5 Security.
- Le flux OAuth devrait suivre les recommandations OWASP OAuth 2.0 : validation de state, PKCE lorsque pertinent, limitation des redirections et absence de jeton sensible dans l’URL.
- La séparation entre gardes Vue et autorisation Laravel est correcte dans son principe : le serveur reste l’autorité.
- Les entrées, erreurs, journaux et téléversements nécessitent une campagne dédiée avant mise en production.

## 6. Plan de remédiation

### Avant toute mise en production

1. HTTPS, variables de production, debug désactivé et CORS restreint.
2. Refonte du retour OAuth sans jeton dans l’URL et avec state/PKCE.
3. Choix documenté du mécanisme de session; suppression du jeton de localStorage si cookie sécurisé retenu.
4. Configuration et test réel de Stripe, Google et du courrier sans exposer les secrets.
5. Tests d’autorisation négatifs, scan de dépendances et test E2E isolé.

### Court terme

1. Corriger les incohérences de validation et de visibilité des véhicules.
2. Ajouter policies de cibles administratives et audit log.
3. Ajouter CSP et en-têtes.
4. Introduire idempotence et rapprochement des remboursements.

### Moyen terme

1. MFA administrateur/responsable.
2. Vérification e-mail.
3. SAST, DAST et analyse de dépendances en CI.
4. Politique de sauvegarde, restauration et conservation.

## 7. Conclusion

Les contrôles métier les plus importants — rôle client forcé, isolation, calcul serveur, transaction de réservation et validation de webhook — sont bien représentés et couverts par des tests. La maturité reste celle d’un projet fonctionnel de PFE : plusieurs durcissements d’exploitation sont indispensables avant une exposition publique réelle.
