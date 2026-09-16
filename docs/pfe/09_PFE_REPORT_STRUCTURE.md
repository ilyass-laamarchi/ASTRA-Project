# 09 — Structure proposée du rapport PFE

## 1. Titres proposés

1. Conception et développement d’une plateforme web sécurisée de gestion de location automobile : cas d’ASTRA
2. Conception d’un système transactionnel de location automobile avec gestion des rôles et des disponibilités
3. Développement d’une plateforme full stack de location automobile fondée sur Laravel et Vue.js
4. ASTRA : conception d’une expérience numérique de réservation automobile avec paiement et suivi temps réel
5. Architecture et réalisation d’un système de gestion de flotte, de réservations et de paiements automobiles

**Titre recommandé :** Conception et développement d’une plateforme web sécurisée de gestion de location automobile : cas d’ASTRA.

Il est suffisamment précis, reste compréhensible par un jury non spécialiste et n’exagère ni l’état du paiement réel ni celui du déploiement.

## 2. Contraintes ENSI à appliquer

- 60 pages maximum hors annexes;
- présentation de l’entreprise : 4 pages maximum;
- interligne 1,15;
- marges gauche/droite 2 cm, haut/bas 1,5 cm, pied de page 1 cm;
- titres de chapitre 16 pt, sections 14 pt, sous-sections et corps 12 pt;
- texte justifié;
- préliminaires en chiffres romains, corps en chiffres arabes;
- impression recto uniquement et remise numérique PDF.

## 3. Ordre obligatoire

1. Page de garde
2. Dédicace
3. Remerciements
4. Résumé et mots-clés
5. Abstract et keywords
6. Table des matières
7. Liste des figures
8. Liste des tableaux
9. Liste des abréviations
10. Introduction générale
11. Chapitre 1 — Contexte, problématique et état de l’art
12. Chapitre 2 — Méthodologie, analyse et conception
13. Chapitre 3 — Réalisation et résultats
14. Conclusion générale et perspectives
15. Bibliographie
16. Annexes A, B, C, puis annexes complémentaires dans l’ordre de citation

## 4. Budget de pages proposé

| Partie | Pages cibles |
|---|---:|
| Préliminaires éditoriaux | 7–9 |
| Introduction générale | 2–3 |
| Chapitre 1 | 10–12 |
| Chapitre 2 | 16–18 |
| Chapitre 3 | 16–18 |
| Conclusion et perspectives | 2–3 |
| Bibliographie | 2–3 |
| Total indicatif | 55–60 |

Les annexes sont hors quota, mais doivent rester utiles et citées.

## 5. Plan détaillé

### Introduction générale

- transformation numérique du secteur de location;
- contexte de l’organisation d’accueil;
- problématique;
- objectifs;
- démarche;
- structure du mémoire.

### Chapitre 1 — Contexte, problématique et état de l’art

Introduction du chapitre.

1. Présentation de l’entreprise — maximum 4 pages  
   1.1 identité, secteur, activités  
   1.2 organisation et service d’accueil  
   1.3 processus existant
2. Étude de l’existant  
   2.1 parcours et difficultés  
   2.2 limites  
   2.3 enjeux
3. État de l’art  
   3.1 plateformes de location  
   3.2 architectures SPA/API  
   3.3 gestion de disponibilité et paiement
4. Analyse comparative
5. Problématique et objectifs
6. Besoins fonctionnels et non fonctionnels
7. Méthodologie de travail

Conclusion du chapitre.

### Chapitre 2 — Méthodologie, analyse et conception

Introduction du chapitre.

1. Acteurs et cas d’utilisation
2. Règles métier
3. Architecture générale
4. Choix technologiques
5. Conception de la base
6. Conception de l’API
7. Conception de la réservation concurrente
8. Conception du paiement
9. Conception du temps réel
10. Sécurité et autorisation
11. Stratégie de tests

Conclusion du chapitre.

### Chapitre 3 — Réalisation et résultats

Introduction du chapitre.

1. Environnement de développement
2. Réalisation du frontend
3. Réalisation du backend
4. Authentification et rôles
5. Catalogue et réservation
6. Gestion des opérations
7. Paiement : implémentation et limites de configuration
8. Notifications : implémentation et limites d’exécution
9. Tableaux de bord et analytique
10. Déploiement local Docker
11. Tests et résultats mesurés
12. Discussion, limites et dette technique

Conclusion du chapitre.

### Conclusion générale et perspectives

- synthèse des apports;
- réponse à la problématique;
- limites factuelles;
- sécurité et industrialisation;
- intégrations réelles;
- observabilité, CI/CD, accessibilité et mobile.

## 6. Répartition conception/réalisation

Le chapitre 2 explique les décisions indépendamment du code : modèles, règles, séquences, contraintes et architecture. Le chapitre 3 présente la matérialisation : composants, endpoints, captures, commandes, tests et résultats. Cette séparation évite de transformer la conception en catalogue de fichiers.

## 7. Figures et tableaux clés

- cas d’utilisation par acteur;
- architecture Laravel/Vue/MySQL/Reverb;
- modèle relationnel;
- séquence de réservation et verrouillage;
- machine d’état;
- séquence du webhook Stripe;
- résultats de tests;
- captures des quatre espaces;
- tableau des limites et perspectives.

## 8. Informations à personnaliser

- étudiant : [À COMPLÉTER PAR L'ÉTUDIANT]
- encadrant académique : [À COMPLÉTER PAR L'ÉTUDIANT]
- encadrant professionnel : [À COMPLÉTER PAR L'ÉTUDIANT]
- entreprise, adresse et logo autorisé : [À COMPLÉTER PAR L'ÉTUDIANT]
- année universitaire, spécialité, dates : [À COMPLÉTER PAR L'ÉTUDIANT]

## 9. Règle éditoriale

Chaque affirmation technique doit être reliée à une preuve du dépôt, un résultat d’exécution ou une référence. Les expressions « déployé », « sécurisé », « temps réel opérationnel » et « paiement fonctionnel » doivent être utilisées seulement avec une preuve correspondante.
