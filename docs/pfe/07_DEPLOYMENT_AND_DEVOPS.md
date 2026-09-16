# 07 — Déploiement et DevOps

## 1. État vérifié

Le dépôt fournit un environnement Docker Compose local. La commande de validation de configuration réussit. Aucun pipeline CI/CD, manifeste Kubernetes, reverse proxy TLS, fichier Nginx, hébergement permanent ou preuve de déploiement de production n’a été trouvé.

## 2. Topologie locale

~~~mermaid
flowchart LR
    U[Navigateur] --> F[Frontend Vue/Vite]
    F --> A[API Laravel]
    A --> D[(MySQL 8.4)]
    A --> R[Laravel Reverb]
    F -. WebSocket .-> R
    A -. API externe .-> S[Stripe]
    A -. OAuth .-> G[Google]
~~~

Services Compose observés :

| Service | Responsabilité | Dépendances |
|---|---|---|
| database | Persistance MySQL | volume et healthcheck |
| api | Application Laravel/API | database |
| reverb | Serveur WebSocket | application/configuration |
| frontend | SPA Vue en développement | api/reverb |

Les services étaient arrêtés au moment de l’audit. Leur bon fonctionnement simultané n’a donc pas été revendiqué.

## 3. Configuration par environnement

Variables à fournir, par nom uniquement :

- application : APP_ENV, APP_DEBUG, APP_URL, APP_KEY;
- base : DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD;
- authentification/CORS : FRONTEND_URL, SANCTUM_STATEFUL_DOMAINS, CORS_ALLOWED_ORIGINS selon stratégie retenue;
- temps réel : BROADCAST_CONNECTION, REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET, REVERB_HOST, REVERB_PORT, REVERB_SCHEME;
- OAuth : GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI;
- paiement : STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET;
- mail : MAIL_MAILER et paramètres du fournisseur;
- filesystems, queue, cache et logs selon la cible.

Les valeurs ne doivent pas être commitées. Des exemples neutres peuvent figurer dans un fichier d’exemple.

## 4. Cible de production recommandée

Une architecture minimale professionnelle comprend :

1. DNS personnalisé et certificat TLS;
2. reverse proxy;
3. build statique Vue servi par CDN ou serveur web;
4. API Laravel sous PHP-FPM;
5. worker de queue supervisé;
6. Reverb supervisé si le temps réel est conservé;
7. MySQL managé ou durci, non exposé publiquement;
8. stockage persistant des médias;
9. gestionnaire de secrets;
10. logs centralisés, métriques, alertes et sauvegardes.

## 5. Pipeline CI/CD proposé

~~~mermaid
flowchart LR
    C[Commit/PR] --> I[Installation verrouillée]
    I --> L[Analyse statique et lint]
    L --> T[Tests Laravel + Vitest]
    T --> B[Build Vue]
    B --> E[E2E sur environnement éphémère]
    E --> S[Scan dépendances/images]
    S --> A[Artefacts versionnés]
    A --> D[Déploiement staging]
    D --> M[Migrations contrôlées]
    M --> P[Validation puis production]
~~~

Garde-fous :

- aucune valeur secrète dans les logs;
- artefacts immuables;
- migrations sauvegardées et compatibles avec rollback applicatif;
- approbation avant production;
- smoke tests après déploiement;
- rollback documenté.

## 6. Checklist avant production

- [ ] Domaine définitif et certificat TLS.
- [ ] APP_ENV production et APP_DEBUG false.
- [ ] APP_KEY et secrets stockés dans un coffre.
- [ ] CORS limité aux origines attendues.
- [ ] OAuth Google configuré et callback sécurisé.
- [ ] Stripe configuré; endpoint webhook HTTPS; événement signé testé.
- [ ] Fournisseur e-mail réel avec SPF/DKIM/DMARC.
- [ ] Queue worker et Reverb supervisés.
- [ ] Base privée, sauvegardes chiffrées et restauration testée.
- [ ] Stockage de fichiers persistant et politique antivirus/type/taille.
- [ ] Observabilité, alertes, rotation des journaux.
- [ ] CI verte, E2E isolés, scan sécurité.
- [ ] Politique de conservation et conformité validées.

## 7. Exploitation

### Sauvegarde

Définir RPO/RTO, fréquence, chiffrement, rétention, réplication hors site et exercice de restauration. Aucun de ces éléments n’est démontré par le dépôt.

### Observabilité

Suivre au minimum : latence API, taux 4xx/5xx, connexions Reverb, files d’attente, échecs de webhook, divergences de paiement, conflits de réservation, disponibilité MySQL, saturation disque et taux de connexion.

### Continuité

Prévoir page de maintenance, repli du temps réel vers polling, reprise des jobs, idempotence des appels externes et procédure d’incident.

## 8. Risques d’hébergement temporaire

Les domaines trycloudflare temporaires ne garantissent ni nom stable ni disponibilité permanente. Un lien ayant disparu ne constitue pas un bug de l’application. Pour « astra » et une disponibilité durable, il faut un domaine possédé, un hébergement permanent et un tunnel nommé ou, de préférence, un déploiement classique.

## 9. Conclusion

Docker Compose rend le projet facilement démontrable en local, mais la chaîne DevOps de production reste à construire. Le rapport doit présenter cette distinction comme une perspective d’industrialisation, sans prétendre à une disponibilité permanente existante.
