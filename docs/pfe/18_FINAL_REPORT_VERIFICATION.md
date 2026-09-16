# ASTRA PFA — Vérification finale du rapport

## Périmètre et verdict

Document vérifié : `ASTRA_PFA_ENSI_Rapport_Professionnel_FINAL_V2 (1).docx`, 40 pages physiques.

La vérification s'appuie d'abord sur `docs/pfe/00` à `17`. Le périmètre audité compte toujours 134 fichiers et aucun fichier de production de ce périmètre n'est postérieur au 27 août 2026 ; les preuves datées du 1er septembre 2026 restent donc applicables. Aucun fichier source n'a dû être ouvert et aucun code de production n'a été modifié.

Le rendu PDF Word a été contrôlé page par page. Aucun chevauchement, texte tronqué, glyphe manquant ou tableau cassé n'a été observé. Le rapport reste toutefois impropre au dépôt à cause des champs académiques et entreprise non remplis, de six zones de capture et d'une navigation statique non mise à jour.

## 1. Terminologie PFA

| Contrôle | Résultat |
|---|---|
| `PFE` dans le contenu visible | Aucun |
| `Projet de Fin d'Études` | Aucun |
| `final-year project` | Aucun |
| PFA / Projet de Fin d'Année | Correct dans le contenu visible |
| Métadonnées Word | Incorrect : `PFE` subsiste dans le titre, les mots-clés et la description du document |

L'anglais emploie correctement « end-of-year project », mais la phrase « suitable for a end-of-year project » doit devenir « suitable for an end-of-year project ».

## 2. Faits techniques

| Point vérifié | Rapport | Verdict | Preuve principale |
|---|---|---|---|
| Laravel | 13.23.0 | Conforme | `01_PROJECT_FACTS_AND_EVIDENCE.md` §3 ; `02_ARCHITECTURE_AND_TECH_STACK.md` §5 |
| Vue | 3.5.41 | Conforme | mêmes sources |
| MySQL | image 8.4, cible Docker Compose | Conforme | mêmes sources |
| Rôles | Visiteur, Client, Responsable ASTRA (`owner`), Administrateur | Conforme | `01_PROJECT_FACTS_AND_EVIDENCE.md` §7 ; routes et tests recensés |
| Inscription publique | rôle Client imposé ; champs de privilège rejetés | Conforme | `01_PROJECT_FACTS_AND_EVIDENCE.md` §3 ; `05_SECURITY_ANALYSIS.md` §2 |
| Sanctum | jetons API, version 4.3.3 | Conforme | `02_ARCHITECTURE_AND_TECH_STACK.md` §5 |
| Création de réservation | transaction, verrou pessimiste du véhicule, second contrôle | Conforme | `01_PROJECT_FACTS_AND_EVIDENCE.md` §3 ; `02_ARCHITECTURE_AND_TECH_STACK.md` §§3 et 7 |
| Détection de conflit | intervalles semi-ouverts ; `pending` et `confirmed` bloquants | Conforme | `02_ARCHITECTURE_AND_TECH_STACK.md` §3 |
| Prix | durée et montant calculés côté serveur | Conforme | `05_SECURITY_ANALYSIS.md` §2 |
| Statuts de réservation | `pending`, `confirmed`, `rejected`, `cancelled`, `completed` | Conforme | `00_PROJECT_AUDIT_SUMMARY.md` §2 ; `04_DATABASE_AND_API.md` |
| Stripe | architecture et doubles de test ; aucune transaction réelle validée | Conforme | `01_PROJECT_FACTS_AND_EVIDENCE.md` §§5–7 ; `16_FEATURE_COMPLETENESS.md` |
| Google OAuth | architecture Socialite ; identifiants absents ; aucun flux réel validé | Conforme | mêmes sources |
| Reverb | configuration locale et code Echo ; transport non exercé | Conforme | mêmes sources |
| API | 99 routes Laravel ; 93 API ; 80 Sanctum ; 35 admin ; 25 owner/admin ; 9 client ; 6 limitées | Conforme | `01_PROJECT_FACTS_AND_EVIDENCE.md` §2 |
| Tests | 48 backend, 193 assertions ; 10 frontend ; build 1 847 modules | Conforme | `06_TESTS_AND_RESULTS.md` §2 |
| E2E | 14 scénarios dans 11 fichiers, non exécutés lors de l'audit | Conforme | `06_TESTS_AND_RESULTS.md` §5 |

## 3. Maturité des affirmations

Le rapport ne présente pas comme validés : Google OAuth réel, Stripe réel, transport Reverb réel, E2E, déploiement de production, CI/CD, monitoring, sauvegardes, performance, accessibilité ou audit de sécurité dynamique. Ces éléments sont correctement indiqués comme non configurés, non exécutés, absents ou à réaliser.

L'expression « audit final » doit continuer à être qualifiée par « documentaire » ou « du code et des tests » afin de ne pas être confondue avec un audit de sécurité, une recette entreprise ou une validation de production.

## 4. Structure académique

| Élément | État |
|---|---|
| Couverture | Présente, mais informations étudiant, diplôme, encadrement, jury et date incomplètes |
| Dédicace | Présente sous forme de placeholder |
| Remerciements | Présents, mais noms et entreprise manquants ; texte générique à personnaliser |
| Résumé / Abstract | Présents et techniquement cohérents ; une faute grammaticale dans l'Abstract |
| Table des matières | Présente, statique et périmée |
| Listes des figures et tableaux | Présentes, statiques, pages périmées et annexes omises |
| Abréviations | Présentes |
| Introduction générale | Présente ; contexte entreprise non prouvé et ouverture générique non sourcée |
| Chapitres 1, 2 et 3 | Présents |
| Introductions et conclusions de chapitres | Présentes pour les trois chapitres |
| Conclusion générale / perspectives | Présentes ; limites techniques correctement séparées |
| Bibliographie | 18 références ; les 18 sont citées dans le texte, mais les sources entreprise, marché et droit restent absentes |
| Annexes | A à F présentes ; E et F sont encore des outils de préparation et doivent disparaître de la version déposée |

Le corps principal imprimé va de la page 1 à la page 26, puis les annexes commencent à la page 27 ; la limite de 60 pages hors annexes est respectée.

## 5. Navigation, citations et doublons

- La table des matières est correcte jusqu'à `1.6`. `1.7` se trouve page 6 et non 7 ; `1.9` page 7 et non 8. Le chapitre 2 commence page 8 et le chapitre 3 page 17, soit une page avant les valeurs affichées. La conclusion générale, les références et les annexes commencent respectivement pages 25, 26 et 27, non 26, 27 et 28.
- Dans les listes, `Tableau 1.2` est page 6 et non 7. Toutes les figures et tous les tableaux des chapitres 2 et 3 sont indiqués une page trop tard.
- Les figures B.1 et D.1 ainsi que les tableaux A.1, C.1, D.1 et E.1 ne figurent pas dans les listes.
- Le document ne contient aucun champ Word de table des matières, de liste ou de renvoi. Les numéros sont du texte statique.
- Les figures et tableaux ne sont généralement pas annoncés par un renvoi dans le paragraphe qui les précède et ne portent pas de ligne de source.
- Les figures 1.1 et 2.1 répètent le même diagramme. La figure B.1 reprend la figure 2.3 et la figure D.1 reprend la figure 3.7.

## 6. Captures à remplacer

| Zone | Écran exact | Rôle | État demandé | Informations à masquer | Légende finale |
|---|---|---|---|---|---|
| F01, p. physique 28 / p. 19 | Page d'accueil desktop, cadrée sur l'en-tête, la proposition de service et les véhicules mis en avant | Visiteur public | Session déconnectée ; données et véhicules de démonstration chargés | Coordonnées réelles non autorisées, immatriculations et toute donnée personnelle | Figure 3.1 — Page d'accueil publique d'ASTRA |
| F02, p. physique 28 / p. 19 | Catalogue avec recherche, filtres, tri, cartes et pagination visibles | Visiteur public | Session déconnectée ; filtre appliqué ; au moins un véhicule actif de démonstration | Immatriculations, médias ou coordonnées réels non autorisés | Figure 3.2 — Consultation et filtrage des véhicules disponibles |
| F03, p. physique 29 / p. 20 | Formulaire d'inscription Client sans champ de rôle | Utilisateur anonyme | Formulaire vide ou rempli uniquement avec des données fictives ; aucun message d'erreur parasite | E-mail, téléphone, mot de passe et identité réels | Figure 3.3 — Création d'un compte Client |
| F04, p. physique 30 / p. 21 | Écran de création d'une réservation avec véhicule, dates, durée et prix serveur | Client de démonstration | Authentifié ; véhicule disponible ; dates fictives sélectionnées ; devis serveur visible avant validation | Nom, e-mail, téléphone, jeton, identifiants internes, immatriculation réelle et donnée de paiement | Figure 3.4 — Création d'une réservation dans l'espace Client |
| F05, p. physique 30 / p. 21 | Tableau de bord du Responsable ASTRA | Responsable `owner` de démonstration | Authentifié ; indicateurs et demandes fictifs visibles ; aucun service externe présenté comme réel | Clients réels, e-mails, téléphones, identifiants, immatriculations et montants réels | Figure 3.5 — Tableau de bord opérationnel du Responsable ASTRA |
| F06, p. physique 31 / p. 22 | Tableau de bord Administrateur | Administrateur de démonstration | Authentifié ; indicateurs stratégiques fictifs visibles ; état fournisseur clairement qualifié | Données client/staff, références de paiement, URLs de callback, clés, secrets, jetons et montants réels | Figure 3.6 — Tableau de bord Administrateur |

Les captures longues existantes dans `docs/screenshots` peuvent servir à F01 et F02 après recadrage et contrôle de leur contenu. Elles ne prouvent aucun flux externe.

## 7. Résultat

TOTAL BLOCKERS: 3

TOTAL HIGH: 6

TOTAL MEDIUM: 4

TOTAL LOW: 3

REPORT TECHNICALLY READY: YES

REPORT ACADEMICALLY READY: NO

READY FOR FINAL WORD/PDF: NO
