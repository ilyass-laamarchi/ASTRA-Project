# 06 — Tests et résultats

## 1. Politique d’exécution

Les commandes retenues sont reproductibles et sans mutation durable des données métier. Les tests Laravel ont été exécutés dans un environnement isolé. Le build frontend a créé temporairement frontend/dist, puis ce répertoire généré a été supprimé. Les tests E2E Playwright n’ont pas été relancés car les services étaient arrêtés et les scénarios existants écrivent dans MySQL persistant.

## 2. Résultats réellement obtenus le 1 septembre 2026

| Vérification | Commande/cible | Résultat | Mesure |
|---|---|---|---|
| Configuration Compose | docker compose config --quiet | PASS | Configuration syntaxiquement valide |
| Backend Laravel Feature | suite Feature isolée en conteneur | PASS | 48 tests, 193 assertions, 50,401 s |
| Frontend unitaire | npm run test:run | PASS | 5 fichiers, 10 tests, 55,17 s |
| Build frontend | npm run build | PASS | 1 847 modules, 36,65 s |
| E2E Playwright | Non exécuté pendant l’audit | NON VÉRIFIÉ | 14 scénarios présents dans 11 fichiers |

## 3. Couverture fonctionnelle backend observée

Les 48 tests Feature sont répartis entre quatre fichiers :

- AstraApiTest : authentification, inscription client, protection contre injection de rôle, comptes inactifs, annuaire client, isolation, tarification, conflits, autorisations et OAuth;
- AvailabilitySystemTest : calcul de disponibilité, bornes temporelles, chevauchements, transitions et conflits;
- DashboardAnalyticsTest : indicateurs des tableaux de bord;
- FunctionalCompletionTest : fonctions complémentaires, paiements et flux métier.

Les assertions importantes couvrent :

1. inscription publique exclusivement cliente;
2. refus des champs de privilège;
3. isolement des ressources entre clients;
4. calcul du prix par le serveur;
5. intervalle de réservation semi-ouvert;
6. prévention des chevauchements;
7. restrictions de rôles;
8. comptes inactifs;
9. préconditions de paiement;
10. comportements OAuth sans configuration et conservation du rôle existant.

## 4. Couverture frontend observée

Les cinq fichiers Vitest totalisent dix tests et ciblent les stores/flux principaux du client. La suite réussit. Aucun taux de couverture de lignes, branches ou fonctions n’a été généré pendant l’audit; il ne faut donc pas transformer le nombre de tests en pourcentage de couverture.

## 5. Inventaire E2E

Onze spécifications Playwright et quatorze scénarios sont présents. Ils visent les parcours publics, l’authentification, les rôles, l’administration et la réservation. Leur présence prouve une intention de validation de bout en bout, pas un résultat d’exécution actuel.

Motif de non-exécution :

- frontend, API et base étaient arrêtés;
- les scénarios créent des données persistantes;
- le prompt impose d’éviter les opérations destructrices ou non maîtrisées.

Prérequis pour une relance fiable :

1. base E2E dédiée et jetable;
2. seed déterministe sans mot de passe exposé;
3. nettoyage transactionnel;
4. services healthcheckés;
5. capture des traces, vidéos et captures uniquement en cas d’échec.

## 6. Matrice qualité

| Axe | Niveau observé | Justification |
|---|---|---|
| Métier réservation | Bon | Conflits, limites, prix et états testés |
| Autorisation | Bon | Cas positifs et négatifs par rôle |
| Authentification | Bon mais perfectible | Flux principaux testés; remember me non fonctionnel |
| Paiement | Partiel | Architecture et tests applicatifs; aucun Stripe réel configuré |
| Temps réel | Partiel | Code et repli présents; transport Reverb non exercé |
| UI unitaire | Modéré | 10 tests, sans couverture mesurée |
| E2E | Non vérifié dans cet audit | Suite présente mais non exécutée |
| Performance | Non mesuré | Aucun test de charge |
| Accessibilité | Non mesuré | Aucun audit automatique ou manuel formalisé |
| Sécurité dynamique | Non mesuré | Aucun pentest/SAST/DAST exécuté |

## 7. Stratégie recommandée

- Ajouter une CI qui exécute lint, tests Laravel, Vitest, build et E2E sur base éphémère.
- Mesurer la couverture sans imposer un seuil artificiel au départ, puis augmenter progressivement.
- Ajouter des tests de contrat API et de webhook Stripe idempotent.
- Tester Reverb avec déconnexion/reconnexion et vérifier le repli polling.
- Introduire tests d’accessibilité avec axe et parcours clavier.
- Ajouter charge concurrente sur la création de réservations.

## 8. Reproductibilité

Les versions exactes des principaux composants sont documentées dans 01_PROJECT_FACTS_AND_EVIDENCE.md et 02_ARCHITECTURE_AND_TECH_STACK.md. Les commandes doivent être exécutées depuis la racine du projet avec les mêmes dépendances verrouillées. Les valeurs de secrets ne doivent jamais figurer dans un rapport ou une sortie de CI.

## 9. Conclusion

Le résultat vérifié est solide pour le noyau fonctionnel : toutes les suites sûres relancées passent. La soutenance doit distinguer clairement ces résultats des éléments simplement présents dans le dépôt, en particulier les E2E, Stripe réel, Google réel, Reverb opérationnel, performance et sécurité dynamique.
