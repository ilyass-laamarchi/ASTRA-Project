# 11 — Plan bibliographique

## 1. Principes

- Citer une source au moment où une décision, une définition ou une pratique est discutée.
- Privilégier documentation officielle, normes et références de sécurité reconnues.
- Indiquer auteur institutionnel, titre, URL et date de consultation.
- Éviter les blogs non sourcés pour justifier l’architecture.
- Ne pas citer un outil uniquement parce qu’il figure dans package.json; expliquer son rôle.
- Uniformiser le style bibliographique demandé par l’ENSI.

Date de consultation des sources ci-dessous : **1 septembre 2026**.

## 2. Sources techniques vérifiées

1. Laravel, « Directory Structure », documentation Laravel 13.x.  
   https://laravel.com/docs/13.x/structure  
   Usage : structure backend et séparation des responsabilités.

2. Laravel, « Broadcasting », documentation Laravel 13.x.  
   https://laravel.com/docs/13.x/broadcasting  
   Usage : conception des événements, canaux et Reverb.

3. Laravel, « Laravel Sanctum », documentation Laravel 13.x.  
   https://laravel.com/docs/13.x/sanctum  
   Usage : authentification API et jetons.

4. Vue.js, « Introduction », guide officiel.  
   https://vuejs.org/guide/introduction  
   Usage : justification de l’interface réactive et de la SPA.

5. Pinia, « Introduction », documentation officielle.  
   https://pinia.vuejs.org/introduction.html  
   Usage : gestion d’état frontend.

6. Axios, « Getting Started », documentation officielle.  
   https://axios.rest/pages/getting-started/first-steps  
   Usage : client HTTP.

7. Docker, « Docker Compose ».  
   https://docs.docker.com/compose/  
   Usage : environnement multi-conteneur reproductible.

8. Docker, « Compose file reference — services ».  
   https://docs.docker.com/reference/compose-file/services/  
   Usage : services et healthchecks.

9. Docker, « Control startup and shutdown order in Compose ».  
   https://docs.docker.com/compose/how-tos/startup-order/  
   Usage : dépendances et disponibilité de MySQL.

10. Oracle, « InnoDB Locking », MySQL 8.4 Reference Manual.  
    https://dev.mysql.com/doc/refman/8.4/en/innodb-locking.html  
    Usage : justification du verrouillage transactionnel.

11. Stripe, « Checkout Sessions API ».  
    https://docs.stripe.com/api/checkout/sessions  
    Usage : création de session de paiement hébergée.

12. Stripe, « Webhooks ».  
    https://docs.stripe.com/webhooks  
    Usage : traitement asynchrone de l’état du paiement.

13. Stripe, « Resolve webhook signature verification errors ».  
    https://docs.stripe.com/webhooks/signature  
    Usage : validation de signature.

14. OWASP, « Session Management Cheat Sheet ».  
    https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html  
    Usage : discussion sur le stockage et la durée de session.

15. OWASP, « OAuth 2.0 Protocol Cheat Sheet ».  
    https://cheatsheetseries.owasp.org/cheatsheets/OAuth2_Cheat_Sheet.html  
    Usage : state, PKCE et retour OAuth.

16. OWASP, « HTML5 Security Cheat Sheet ».  
    https://cheatsheetseries.owasp.org/cheatsheets/HTML5_Security_Cheat_Sheet.html  
    Usage : risque localStorage et contrôles navigateur.

17. Microsoft, « Playwright documentation — Introduction ».  
    https://playwright.dev/docs/intro  
    Usage : stratégie E2E.

18. Vitest, « Getting Started ».  
    https://vitest.dev/guide/index.html  
    Usage : tests frontend.

## 3. Emplacement recommandé des citations

| Sujet | Chapitre | Sources |
|---|---|---|
| SPA et composants | Ch. 2 architecture | Vue, Pinia |
| Authentification API | Ch. 2 sécurité | Sanctum, OWASP Session |
| Concurrence | Ch. 2 réservation | MySQL InnoDB |
| Temps réel | Ch. 2 | Laravel Broadcasting |
| Paiement | Ch. 2 | Stripe Checkout, Webhooks, Signature |
| Conteneurisation | Ch. 3 | Docker Compose |
| Tests | Ch. 3 | Vitest, Playwright |
| Limites OAuth | Ch. 3/discussion | OWASP OAuth |

## 4. Recherches complémentaires à faire

Les sujets suivants n’ont pas été documentés par une source vérifiée dans cet audit :

- cadre juridique marocain applicable aux données personnelles;
- obligations fiscales et contractuelles de location automobile;
- réglementation des paiements et remboursements;
- étude de marché locale et concurrents;
- méthodologie de gestion de projet réellement adoptée;
- documents internes de l’entreprise.

Ces éléments doivent être recherchés par l’étudiant et validés avec l’encadrant. Ils ne doivent pas être inventés.

## 5. Exemple de format

Format indicatif à adapter :

> [n] Organisme, « Titre de la ressource », année ou version. [En ligne]. Disponible : URL. [Consulté le : 1 septembre 2026].

Chaque entrée doit être appelée dans le corps. Une bibliographie contenant des sources jamais citées doit être nettoyée.
