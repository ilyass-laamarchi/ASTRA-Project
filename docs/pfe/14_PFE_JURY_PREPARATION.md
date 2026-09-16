# 14 — Préparation au jury PFE

## Pitch de 90 secondes

ASTRA est une plateforme full stack de location automobile destinée aux visiteurs, clients, responsables opérationnels et administrateurs. Le problème central n’était pas seulement de créer une belle interface, mais d’empêcher les doubles réservations, de protéger les privilèges et de garantir que les prix et paiements reposent sur des données serveur. L’architecture combine Vue, Laravel, MySQL et Reverb. La création d’une réservation s’exécute dans une transaction qui verrouille le véhicule, revérifie les conflits et calcule le montant côté serveur. L’inscription force le rôle client. Stripe est isolé derrière une passerelle et un paiement n’est confirmé qu’après webhook signé. L’audit final a validé 48 tests backend, 193 assertions, 10 tests frontend et le build. Les intégrations externes et la mise en production restent des perspectives clairement identifiées.

## Questions et réponses

### 1. Quelle est votre contribution principale ?

La conception et la réalisation d’un noyau transactionnel multi-rôle : disponibilité, réservation, prix serveur, workflow, paiements abstraits et interfaces dédiées.

### 2. Pourquoi Laravel et Vue ?

Laravel centralise validation, autorisation, ORM, transactions et intégrations. Vue fournit une SPA réactive et modulaire. La séparation facilite les tests et l’évolution.

### 3. Comment empêchez-vous une double réservation ?

Par une condition de chevauchement sur intervalle semi-ouvert, puis une transaction qui verrouille le véhicule et revérifie les réservations bloquantes avant insertion.

### 4. Pourquoi l’intervalle [début, fin) ?

Il permet à une réservation de commencer exactement à la fin de la précédente sans conflit, tout en restant simple à raisonner.

### 5. Pourquoi un verrou si une vérification existe déjà ?

Deux requêtes concurrentes pourraient toutes deux voir une disponibilité. Le verrou sérialise la section critique et la deuxième vérification décide avec un état cohérent.

### 6. Qui calcule le prix ?

Le serveur. Il utilise les dates validées et le prix du véhicule stocké. Le navigateur n’est pas une source de vérité.

### 7. Comment empêchez-vous un client de devenir admin ?

La requête d’inscription interdit les champs de rôle et le contrôleur impose client. Des tests couvrent plusieurs tentatives d’injection.

### 8. Les gardes Vue suffisent-elles ?

Non. Elles améliorent l’UX. Les middlewares et règles Laravel assurent l’autorisation réelle.

### 9. Pourquoi Sanctum ?

Il fournit une authentification adaptée à une SPA/API Laravel et s’intègre naturellement aux middlewares.

### 10. Quelle limite voyez-vous dans votre session actuelle ?

Le jeton Bearer est stocké dans localStorage, donc un XSS pourrait l’exfiltrer. Une évolution vers cookie HttpOnly sécurisé ou un durcissement CSP est prioritaire.

### 11. Le « se souvenir de moi » fonctionne-t-il ?

Non. Le contrôle existe dans l’interface mais sa valeur n’est pas transmise ni appliquée. C’est une lacune explicitement documentée.

### 12. Google OAuth est-il opérationnel ?

Le code existe, mais les identifiants n’étaient pas configurés pendant l’audit. Je le présente comme une architecture implémentée, non validée en conditions réelles.

### 13. Quel problème présente le retour OAuth ?

Il utilise stateless et place un jeton dans l’URL de retour. Cela doit être remplacé par state/PKCE et un code à usage unique échangé côté serveur.

### 14. Comment sécurisez-vous Stripe ?

Le montant vient de la base, la session est hébergée par Stripe et le webhook est signé. Le montant et la devise sont rapprochés avant de marquer le paiement payé.

### 15. Stripe a-t-il été testé réellement ?

Non dans l’environnement audité, car les clés et le secret webhook étaient absents. Les tests applicatifs passent, mais ce n’est pas une preuve d’intégration réelle.

### 16. Pourquoi une interface de passerelle de paiement ?

Elle sépare le métier du fournisseur, facilite les tests et permet une autre implémentation sans réécrire les cas d’usage.

### 17. Comment gérez-vous le remboursement ?

Il est limité à l’administrateur. Une amélioration importante est d’ajouter idempotence, journal d’événements et rapprochement après panne intermédiaire.

### 18. Le temps réel est-il indispensable ?

Non. Il améliore la réactivité. L’application conserve un repli par focus, visibilité et polling de trente secondes.

### 19. Reverb a-t-il été validé ?

Son architecture et sa configuration sont présentes, mais le transport n’a pas été exercé durant l’audit car les services étaient arrêtés.

### 20. Combien de routes avez-vous ?

99 au total, dont 93 API. Parmi elles, 80 sont protégées par Sanctum; 35 admin, 25 owner/admin et 9 client.

### 21. Quels tests ont réellement passé ?

48 tests Feature Laravel avec 193 assertions, 10 tests Vitest et le build Vue. La configuration Compose est valide.

### 22. Pourquoi ne pas annoncer que l’E2E passe ?

Quatorze scénarios sont présents, mais ils n’ont pas été relancés car ils écrivent dans une base persistante. Leur présence et leur résultat sont deux faits différents.

### 23. Avez-vous mesuré la performance ?

Non. Aucun test de charge ni mesure de latence n’a été exécuté. C’est une perspective.

### 24. Le projet est-il prêt pour la production ?

Pas encore. Il faut TLS, configuration sécurisée, CORS restreint, gestion des secrets, CI/CD, sauvegardes, monitoring et validation des intégrations.

### 25. Pourquoi Docker Compose ?

Pour reproduire localement les dépendances principales et documenter la topologie. Ce n’est pas à lui seul une architecture de production.

### 26. Quelle est la dette technique la plus urgente ?

Session/OAuth, configuration de production, tests E2E isolés et garanties d’idempotence des paiements/remboursements.

### 27. Comment l’annuaire admin voit-il les nouveaux clients ?

La liste provient du serveur, triée par identifiant décroissant et paginée par quinze. Le frontend recherche, pagine et rafraîchit périodiquement.

### 28. Comment protégez-vous les données entre clients ?

Les contrôleurs/policies limitent les ressources au propriétaire et des tests vérifient qu’un client ne lit pas les réservations d’un autre.

### 29. Qu’avez-vous appris ?

Qu’une application métier fiable dépend davantage de ses invariants, transitions, transactions et preuves que du nombre d’écrans.

### 30. Quelle serait la prochaine itération ?

Durcir l’authentification, créer une CI complète, isoler les E2E, tester les services externes en sandbox et déployer un staging observé.

## Démonstration recommandée

1. accueil et catalogue;
2. inscription d’un client;
3. connexion;
4. réservation et calcul;
5. tentative de conflit;
6. traitement par Responsable ASTRA;
7. visualisation admin;
8. résultats de tests;
9. architecture et limites.

Préparer les données avant la soutenance. Ne jamais improviser un paiement réel ou afficher des secrets.

## Diapositives recommandées

1. contexte et problématique;
2. objectifs;
3. acteurs;
4. architecture;
5. modèle de données;
6. algorithme de disponibilité;
7. sécurité des rôles;
8. interfaces;
9. tests et résultats;
10. limites;
11. perspectives;
12. conclusion.
