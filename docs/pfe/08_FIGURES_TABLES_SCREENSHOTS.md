# 08 — Figures, tableaux et captures d’écran

## 1. Inventaire existant

| Fichier | Résolution réelle | Usage conseillé |
|---|---:|---|
| docs/screenshots/home-1440x900.png | 1440 × 7426 | Vue complète desktop, à recadrer par section |
| docs/screenshots/home-1920x1080.png | 1920 × 7612 | Version haute résolution |
| docs/screenshots/home-390x844.png | 390 × 8648 | Responsive mobile |
| docs/screenshots/home-768x1024.png | 768 × 8451 | Responsive tablette |

Ces captures datent du 24 août 2026 d’après les métadonnées du système. Leur contenu doit être vérifié visuellement avant insertion finale.

## 2. Règles de capture

1. Utiliser un jeu de données de démonstration fictif.
2. Masquer e-mails, téléphones, noms réels, identifiants, jetons, URLs de callback et valeurs de paiement.
3. Ne jamais capturer la console avec secrets ou en-têtes Authorization.
4. Utiliser la même largeur, le même zoom et le même navigateur pour une série.
5. Conserver PNG pour l’interface; SVG pour les diagrammes lorsque possible.
6. Nommer fig-XX-sujet-role-resolution.png.
7. Ajouter une légende numérotée et citer chaque figure dans le texte avant son apparition.
8. Éviter les pages entières illisibles : recadrer sur la fonction démontrée.

## 3. Plan de captures

| ID | Écran | Acteur/données | Section du rapport | Légende proposée |
|---|---|---|---|---|
| F01 | Accueil desktop | Public, données fictives | Ch. 3 UI publique | Figure 3.1 — Page d’accueil publique d’ASTRA |
| F02 | Catalogue et filtres | Public | Ch. 3 recherche | Figure 3.2 — Consultation et filtrage des véhicules disponibles |
| F03 | Détail véhicule | Public | Ch. 3 catalogue | Figure 3.3 — Fiche détaillée d’un véhicule |
| F04 | Connexion | Anonyme | Ch. 3 authentification | Figure 3.4 — Interface de connexion |
| F05 | Inscription | Anonyme, formulaire vide | Ch. 3 authentification | Figure 3.5 — Création d’un compte client |
| F06 | Tableau de bord client | Compte fictif; masquer e-mail | Ch. 3 espace client | Figure 3.6 — Synthèse de l’espace client |
| F07 | Création réservation | Client; dates fictives | Ch. 3 réservation | Figure 3.7 — Saisie d’une réservation |
| F08 | Conflit de disponibilité | Client | Ch. 3 concurrence | Figure 3.8 — Retour utilisateur lors d’un conflit |
| F09 | Historique réservations | Client; masquer identifiants | Ch. 3 espace client | Figure 3.9 — Suivi des réservations |
| F10 | Paiement indisponible/configuré | Client; aucune donnée bancaire | Ch. 3 paiement | Figure 3.10 — Point d’entrée vers le paiement hébergé |
| F11 | Dashboard Responsable ASTRA (owner) | Responsable fictif | Ch. 3 pilotage | Figure 3.11 — Tableau de bord opérationnel |
| F12 | Gestion flotte | Responsable | Ch. 3 administration | Figure 3.12 — Gestion des véhicules |
| F13 | Traitement réservation | Responsable | Ch. 3 workflow | Figure 3.13 — Validation d’une demande |
| F14 | Dashboard administrateur | Admin fictif | Ch. 3 analytique | Figure 3.14 — Indicateurs stratégiques |
| F15 | Annuaire clients | Admin; données masquées | Ch. 3 administration | Figure 3.15 — Recherche et pagination des clients |
| F16 | Paramètres | Admin; valeurs sensibles masquées | Annexe | Figure E.1 — Paramètres applicatifs |
| F17 | Responsive mobile | Public | Ch. 3 UX | Figure 3.16 — Adaptation mobile de l’accueil |
| F18 | Notifications temps réel | Acteur fictif | Ch. 3 temps réel | Figure 3.17 — Notification d’un événement métier |

## 4. Diagrammes à produire

| ID | Diagramme | Source de vérité | Emplacement |
|---|---|---|---|
| D01 | Architecture générale | docker-compose, routes, Echo | Ch. 2 |
| D02 | Cas d’utilisation par acteur | routes et gardes | Ch. 1/2 |
| D03 | Modèle relationnel | migrations | Ch. 2 |
| D04 | Séquence de réservation | contrôleur, transaction, tests | Ch. 2 |
| D05 | Machine d’état réservation | logique de transition | Ch. 2 |
| D06 | Séquence Stripe webhook | gateway/contrôleur | Ch. 2 |
| D07 | Déploiement local | docker-compose | Ch. 3 |
| D08 | Pipeline cible | proposition, clairement étiquetée | Perspectives/annexe |

## 5. Tableaux principaux

| ID | Tableau | Emplacement |
|---|---|---|
| T01 | Comparatif des solutions/état de l’art | Ch. 1 |
| T02 | Besoins fonctionnels par acteur | Ch. 1 |
| T03 | Besoins non fonctionnels | Ch. 1 |
| T04 | Choix technologiques justifiés | Ch. 2 |
| T05 | Entités et responsabilités | Ch. 2 |
| T06 | Endpoints principaux | Ch. 2 ou annexe A |
| T07 | Contrôles de sécurité | Ch. 2 |
| T08 | Résultats des tests | Ch. 3 |
| T09 | Limites et perspectives | Conclusion |
| T10 | Traçabilité exigences/tests | Annexe C |

## 6. Contrôle avant insertion

- [ ] La capture correspond à la version finale.
- [ ] Aucune donnée personnelle ou secrète n’est visible.
- [ ] Le texte reste lisible à l’impression.
- [ ] La figure est citée dans le corps.
- [ ] La légende est descriptive et non décorative.
- [ ] La source est « Élaborée par l’auteur » ou une référence vérifiée.
- [ ] Le numéro respecte l’ordre du rapport.

## 7. Limite de preuve

Les captures existantes prouvent un rendu à une date donnée, pas le bon fonctionnement de tous les flux. Les écrans de Stripe, Google et Reverb ne doivent être présentés comme opérationnels qu’après une exécution réelle documentée.
