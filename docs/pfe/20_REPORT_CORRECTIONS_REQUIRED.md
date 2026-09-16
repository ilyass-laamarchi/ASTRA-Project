# ASTRA PFA — Corrections requises

## BLOCKER

### B-01 — Compléter l'identité et les préliminaires

- **Emplacement :** couverture, page physique 1 ; dédicace, page physique 2 ; remerciements, page physique 3.
- **Problème actuel :** nom de l'étudiant, filière/spécialité, encadrants, jury et date exacte sont des placeholders. La dédicace est vide et les remerciements citent des personnes et une entreprise non identifiées.
- **Correction exacte :** renseigner les champs à partir de `19_FINAL_STUDENT_INPUT_FORM.md`, appliquer le modèle officiel ENSI, écrire la dédicace finale et remplacer les formulations génériques des remerciements par des contributions réelles validées.
- **Preuve :** `13_MISSING_INFORMATION.md` §§1 et 9 ; `17_FINAL_PFE_CHECKLIST.md` §§A–B.

### B-02 — Compléter l'entreprise, l'existant et la méthodologie

- **Emplacement :** introduction, page imprimée 1 ; sections 1.2.1 à 1.2.3, page imprimée 3 ; section 1.8, page imprimée 7 ; annexe F, pages imprimées 30–31.
- **Problème actuel :** raison sociale, activité, organisation, service d'accueil, processus avant ASTRA, problèmes observés et méthode de travail restent à compléter. La rédaction actuelle donne des consignes au lieu d'apporter le contenu académique attendu.
- **Correction exacte :** insérer uniquement les faits, dates, acteurs, pratiques et preuves fournis par l'étudiant et validés par l'entreprise ; supprimer tous les marqueurs et phrases d'instruction après intégration.
- **Preuve :** `13_MISSING_INFORMATION.md` §§2–4 ; `17_FINAL_PFE_CHECKLIST.md` §B.

### B-03 — Remplacer les six zones de capture

- **Emplacement :** figures 3.1 à 3.6, pages physiques 28 à 31, pages imprimées 19 à 22.
- **Problème actuel :** F01 à F06 sont encore des cadres « ZONE RÉSERVÉE À VOTRE CAPTURE D'ÉCRAN ».
- **Correction exacte :** insérer les six écrans selon la matrice de `18_FINAL_REPORT_VERIFICATION.md` §6, avec comptes et données fictifs, recadrage lisible, anonymisation, légende finale et renvoi dans le texte. Mettre ensuite à jour la pagination et les listes.
- **Preuve :** `08_FIGURES_TABLES_SCREENSHOTS.md` §§2–3 ; placeholders Word F01–F06.

## HIGH

### H-01 — Supprimer PFE des métadonnées Word

- **Emplacement :** propriétés internes `docProps/core.xml`, visibles par Fichier > Informations > Propriétés.
- **Problème actuel :** `PFE` apparaît dans le titre, les mots-clés et la description. L'auteur est vide et les dates internes sont héritées de 2013.
- **Correction exacte :** remplacer chaque `PFE` par `PFA`, définir un titre et un sujet PFA cohérents, renseigner l'auteur si l'ENSI le demande, supprimer la description de brouillon et actualiser ou nettoyer les dates avant l'export PDF.
- **Preuve :** recherche OOXML du document ; aucune occurrence interdite n'est visible dans le corps.

### H-02 — Régénérer la table des matières et les listes

- **Emplacement :** table des matières, page physique 6 ; liste des figures, page 7 ; liste des tableaux, page 8.
- **Problème actuel :** les trois listes sont du texte statique et le document ne contient aucun champ Word. `1.7` et `1.9` sont mal paginés ; le chapitre 2, le chapitre 3 et leurs entrées sont indiqués une page trop tard ; conclusion, références et annexes sont affichées 26/27/28 au lieu de 25/26/27. `Tableau 1.2` est page 6, non 7. Les entrées des chapitres 2 et 3 dans les listes sont également une page trop tard.
- **Correction exacte :** appliquer des styles de titre ou des champs TC aux titres de chapitre, utiliser des légendes `SEQ`, insérer de vraies tables Word, puis mettre à jour tous les champs après les captures et avant l'export. Ajouter les figures B.1/D.1 et les tableaux A.1/C.1/D.1/E.1.
- **Preuve :** rendu Word de 40 pages et inspection des instructions de champs OOXML : aucune instruction de champ présente.

### H-03 — Vérifier ou retirer les affirmations métier non prouvées

- **Emplacement :** remerciements, page physique 3 ; introduction, page imprimée 1 ; sections 1.2 et 1.3, pages imprimées 3–4.
- **Problème actuel :** le rapport affirme une agence de location située à Tanger et évoque des retours ayant orienté ASTRA vers les besoins du terrain, sans preuve fournie. Le code prouve les fonctions, pas le contexte commercial réel.
- **Correction exacte :** obtenir une validation écrite ou une source d'entreprise pour chaque affirmation. Sans preuve, supprimer la localisation et les retours supposés, et décrire ASTRA sans attribuer de pratique ou résultat à une entreprise réelle.
- **Preuve :** `13_MISSING_INFORMATION.md` §§2, 4 et 5 ; `01_PROJECT_FACTS_AND_EVIDENCE.md` §8.

### H-04 — Transformer l'état de l'art en comparaison sourcée

- **Emplacement :** section 1.6, pages imprimées 5–6.
- **Problème actuel :** la section décrit surtout les technologies ASTRA à partir de documentations éditeur. Elle ne compare pas de solutions ou d'approches selon des critères explicites et ne remplit pas le besoin d'un état de l'art comparatif.
- **Correction exacte :** ajouter une comparaison vérifiée des approches pertinentes, avec critères, limites et justification des choix ASTRA ; utiliser des sources académiques, normatives ou sectorielles adaptées et citer chaque conclusion.
- **Preuve :** `17_FINAL_PFE_CHECKLIST.md` §B laisse « État de l'art comparatif sourcé » ouvert ; `08_FIGURES_TABLES_SCREENSHOTS.md` prévoit le tableau T01.

### H-05 — Finaliser la bibliographie et les sources manquantes

- **Emplacement :** références bibliographiques, page imprimée 26.
- **Problème actuel :** les 18 références techniques sont bien citées, mais une note « À compléter » demande encore les sources du droit marocain, des données personnelles, du contexte entreprise et de l'étude de marché. Le format ENSI n'est pas confirmé.
- **Correction exacte :** ajouter uniquement les sources réellement consultées et vérifiées, placer leurs appels dans les sections concernées, harmoniser les 18 références et les nouvelles références avec le style ENSI, puis supprimer la note éditoriale.
- **Preuve :** `11_BIBLIOGRAPHY_PLAN.md` ; `13_MISSING_INFORMATION.md` §8 ; `17_FINAL_PFE_CHECKLIST.md` §B.

### H-06 — Retirer les éléments de fabrication du rapport final

- **Emplacement :** encadrés « Périmètre factuel », « Conformité ENSI » et « Information manquante », pages imprimées 1, 3 et 7 ; note éditoriale de la bibliographie, page 26 ; annexes E et F, pages 30–31.
- **Problème actuel :** le rapport conserve des consignes de rédaction, un plan de captures et une liste d'informations à compléter. Ces éléments signalent un brouillon.
- **Correction exacte :** convertir les réserves utiles en limites académiques rédigées, supprimer toutes les consignes après remplissage, retirer l'annexe E après insertion des captures et retirer l'annexe F après validation des données.
- **Preuve :** contrôle du texte et du rendu des pages indiquées.

## MEDIUM

### M-01 — Citer et sourcer les figures et tableaux

- **Emplacement :** figures 1.1 à 3.7 et tableaux 1.1 à 3.4 ; annexes A à E.
- **Problème actuel :** les objets ont des légendes, mais sont rarement annoncés par un renvoi explicite dans le texte et ne portent pas de source.
- **Correction exacte :** ajouter avant chaque objet une phrase du type « La figure 2.4 présente… » ou « Le tableau 3.2 synthétise… », utiliser des renvois Word et ajouter « Source : élaborée par l'auteur à partir de… » ou une référence vérifiée.
- **Preuve :** `08_FIGURES_TABLES_SCREENSHOTS.md` §§2 et 6 ; `17_FINAL_PFE_CHECKLIST.md` §B.

### M-02 — Remplacer les paragraphes académiques génériques

- **Emplacement :** ouverture de l'introduction, page imprimée 1 ; remerciements, page physique 3 ; conclusion générale, page imprimée 25.
- **Problème actuel :** plusieurs passages emploient des généralités non sourcées ou des formules génériques, par exemple la transformation numérique des services et la « principale valeur ajoutée ».
- **Correction exacte :** rattacher l'ouverture à une source ou à un constat d'entreprise vérifié ; personnaliser les remerciements ; formuler la conclusion à partir des résultats mesurés et des limites déjà établies.
- **Preuve :** absence de source locale à proximité de ces affirmations ; `13_MISSING_INFORMATION.md` §§2, 5 et 9.

### M-03 — Uniformiser les niveaux de complétude en français

- **Emplacement :** tableau 3.3, pages imprimées 23–24.
- **Problème actuel :** les statuts `Complete`, `Mostly complete`, `Architecture only`, `Unable to verify` et `Missing` sont en anglais dans un rapport français et peuvent paraître absolus hors de leur justification.
- **Correction exacte :** employer `Complet dans le périmètre testé`, `Partiellement complet`, `Architecture uniquement`, `Non vérifié` et `Absent`, en conservant la justification et la limite de preuve pour chaque ligne.
- **Preuve :** `16_FEATURE_COMPLETENESS.md` fournit la maturité exacte à préserver.

### M-04 — Réduire les doublons visuels

- **Emplacement :** figures 1.1 et 2.1 ; figures 2.3 et B.1 ; figures 3.7 et D.1.
- **Problème actuel :** trois diagrammes sont répétés, ce qui alourdit le rapport sans nouvelle information.
- **Correction exacte :** conserver la version la plus utile dans le corps et utiliser un renvoi depuis l'annexe. Si une reprise est exigée pour rendre l'annexe autonome, l'indiquer explicitement et éviter une seconde explication identique.
- **Preuve :** comparaison visuelle des pages imprimées 6/9, 12/28 et 22/29.

## LOW

### L-01 — Corriger l'article anglais

- **Emplacement :** Abstract, page physique 5, dernier paragraphe.
- **Problème actuel :** « suitable for a end-of-year project ».
- **Correction exacte :** écrire « suitable for an end-of-year project ».

### L-02 — Harmoniser le vocabulaire technique français

- **Emplacement :** ensemble du rapport, notamment tableaux 2.2, 3.1 et 3.3, puis perspectives.
- **Problème actuel :** alternance entre `frontend`, `backend`, `full stack`, `polling`, `owner/admin`, `first-party` et les équivalents français ; « ASTRA Manager » apparaît dans l'Abstract alors que le rôle source est Responsable ASTRA (`owner`).
- **Correction exacte :** choisir des termes français cohérents ou définir le terme anglais à la première occurrence ; traduire le rôle anglais par `ASTRA Manager (technical role: owner)` de façon uniforme.

### L-03 — Aligner le titre du chapitre 2 avec son contenu

- **Emplacement :** couverture et titre du chapitre 2, page imprimée 8 ; section 1.8, page imprimée 7.
- **Problème actuel :** le chapitre 2 s'intitule « Méthodologie, analyse et conception », alors que la méthodologie est traitée en 1.8 et le chapitre 2 porte essentiellement sur l'analyse et la conception.
- **Correction exacte :** renommer le chapitre 2 « Analyse et conception » ou déplacer la méthodologie réelle vers le chapitre 2, puis régénérer la table des matières.

TOTAL BLOCKERS: 3

TOTAL HIGH: 6

TOTAL MEDIUM: 4

TOTAL LOW: 3

REPORT TECHNICALLY READY: YES

REPORT ACADEMICALLY READY: NO

READY FOR FINAL WORD/PDF: NO
