# Plan de realisation - PAS

Ce plan decoupe le cahier des charges en etapes livrables. Chaque bloc correspond a un commit logique pour garder un historique Git lisible.

## Source de verite et suivi courant

- Source de verite fonctionnelle : `Sujet.md`.
- Le plan ci-dessous sert de feuille de route operationnelle et doit rester aligne avec les criteres du sujet.
- Branche de travail courante : `feature/inmate-management`.
- Priorite immediate : Phase 5B (Commits 19-21) en cours ; 19 et 20 termines, 21 termine, reste 22-24 (Phase 5C : tablette complete, incidents/audit, admin utilisateurs).

## Rappels des criteres du sujet

- Documentation : cahier des charges, schema BDD, fixtures, README d'installation et comptes de test.
- Donnees : au moins 10 entites, heritage d'entite, 2 relations ManyToMany, 8 relations OneToMany.
- Securite : authentification securisee, 3 roles differents, au moins 1 voter personnalise.
- Fonctionnalites : API JSON dediee, envoi de mail, API externe, forms dynamiques, espace admin, 10 pages Twig minimum.
- Qualite : au moins 1 test unitaire, 1 test fonctionnel, repositories avec QueryBuilder, CI avec tests/lint/PHPStan.

## Phase 0 - Socle projet

- [x] Commit 01 - `Finaliser la configuration Docker et environnement`
  - [x] Garder `.env` comme configuration par defaut versionnee.
  - [x] Ignorer `.env.local` et `.env.*.local`.
  - [x] Corriger Docker pour Git `safe.directory`.
  - [x] Verifier `docker compose up --build`.
  - [x] Mettre a jour le README avec le lancement Docker.

- [x] Commit 02 - `Ajouter la feuille de route du projet`
  - [x] Ajouter ce plan de suivi.
  - [x] Relier le plan au cahier des charges dans le README.

## Phase 1 - Dependances et configuration Symfony

- [x] Commit 03 - `Installer les bundles Symfony principaux`
  - [x] Installer Doctrine ORM, Migrations, MakerBundle.
  - [x] Installer Twig, SecurityBundle, Validator, Form, Asset.
  - [x] Installer DoctrineFixturesBundle et Faker en dev.
  - [x] Installer Serializer, Mailer, HttpClient.
  - [x] Ajouter PHPUnit, PHPStan et outils de lint.

- [x] Commit 04 - `Configurer la base de donnees et la securite`
  - [x] Configurer Doctrine avec MySQL.
  - [x] Ajouter `security.yaml` avec hierarchy des roles.
  - [x] Configurer le password hasher.
  - [x] Preparer les routes publiques et protegees.

## Phase 2 - Modele de donnees Doctrine

- [x] Commit 05 - `Ajouter le modele utilisateur avec heritage`
  - [x] Creer `User` avec email, password, roles, nom, prenom, actif.
  - [x] Ajouter l'heritage Doctrine pour `AdminUser`, `ManagerUser`, `GuardUser`.
  - [x] Ajouter les champs specifiques : service, superAdmin, managedBuilding, badgeNumber, assignedZone.
  - [x] Ajouter contraintes de validation et index utiles.

- [x] Commit 06 - `Ajouter les entites de structure penitentiaire`
  - [x] Creer `Building`, `Wing`, `Cell`.
  - [x] Ajouter les relations Building 1-N Wing et Wing 1-N Cell.
  - [x] Ajouter capacite, statut cellule, code batiment, activation.
  - [x] Ajouter contrainte unique cellule par aile.

- [x] Commit 07 - `Ajouter les entites detenus et mouvements`
  - [x] Creer `Inmate`.
  - [x] Creer `Assignment`.
  - [x] Creer `Transfer`.
  - [x] Ajouter UID unique et statuts detenus.
  - [x] Ajouter historique cellule/detenu et dates d'affectation.

- [x] Commit 08 - `Ajouter les entites activites incidents et audit`
  - [x] Creer `Activity`.
  - [x] Creer `ActivityParticipation`.
  - [x] Creer `Incident`.
  - [x] Creer `AuditLog`.
  - [x] Creer `Notification`.
  - [x] Ajouter les ManyToMany : Inmate-Incident et Inmate-Activity via participation.

- [x] Commit 09 - `Ajouter la migration Doctrine initiale`
  - [x] Generer la migration.
  - [x] Relire la migration.
  - [x] Tester `doctrine:migrations:migrate`.

## Phase 3 - Authentification et droits

- [x] Commit 10 - `Ajouter la connexion et la deconnexion`
  - [x] Ajouter le formulaire de connexion Twig.
  - [x] Ajouter logout.
  - [x] Protections CSRF.
  - [x] Redirection selon role apres connexion.
  - [x] Ajouter des layouts Twig de base `auth` et `app`.
  - [x] Ajouter un CSS global dedie dans `public/styles/app.css`.
  - [x] Ajouter les pages protegees minimales `/admin`, `/manager`, `/guard`.
  - [x] Verifier `lint:twig`, `lint:yaml`, `lint:container`, `debug:router` et les reponses HTTP principales.

- [x] Commit 10b - `Ajouter les comptes de demonstration`
  - [x] Creer les comptes `admin@pas.test`, `manager@pas.test`, `guard@pas.test`.
  - [x] Hasher les mots de passe dans les fixtures.
  - [x] Charger une structure minimale compatible avec `ManagerUser` et `GuardUser`.
  - [x] Documenter les identifiants de demo dans le README.
  - [x] Verifier un login reel et les redirections par role.

- [x] Commit 11 - `Ajouter le controle d acces par roles`
  - [x] Proteger dashboard, admin, manager, guard.
  - [x] Ajouter les `access_control` (incluant `/inmates`, `/cells`, `/incidents`, `/tablet` merges depuis la PR #10, plus un garde-fou generique `ROLE_USER`).
  - [x] Verifier la hierarchy ROLE_ADMIN > ROLE_MANAGER > ROLE_GUARD > ROLE_USER.

- [x] Commit 12 - `Ajouter le voter des incidents`
  - [x] Creer `IncidentVoter`.
  - [x] Admin : tout.
  - [x] Manager : incidents de son batiment.
  - [x] Guard : creation et modification de ses incidents ouverts/brouillons.
  - [x] Ajouter tests unitaires du voter.

## Phase 4 - Services metier

- [x] Commit 13 - `Ajouter le service d affectation`
  - [x] Empecher depassement de capacite.
  - [x] Empecher affectation si statut SORTI ou TRANSFERE_EXTERNE.
  - [x] Garantir une seule affectation active par detenu.
  - [x] Journaliser l'action dans `AuditLog`.
  - [x] Ajouter test unitaire obligatoire.

- [x] Commit 14 - `Ajouter le service de transfert`
  - [x] Gerer transfert interne.
  - [x] Gerer transfert externe.
  - [x] Cloturer l'affectation source.
  - [x] Creer nouvelle affectation si transfert interne.
  - [x] Preparer notification email.

- [x] Commit 15 - `Ajouter les services incidents et notifications`
  - [x] Creer incidents via service.
  - [x] Notifier les managers en gravite elevee.
  - [x] Historiser les notifications.
  - [x] Journaliser creation et traitement.

- [x] Commit 16 - `Ajouter le service d information externe`
  - [x] Integrer HttpClient.
  - [x] Encapsuler une API externe meteo ou geocodage.
  - [x] Configurer les variables d'environnement.
  - [x] Ajouter un fallback propre si API indisponible.

## Phase 5 - Back-office Twig

- [x] Commit 17 - `Ajouter la mise en page Twig principale`
  - [x] Template base.
  - [x] Navigation selon role.
  - [x] Flash messages.
  - [x] Styles simples et responsive desktop/tablette.

- [x] Commit 18 - `Ajouter le tableau de bord`
  - [x] Statistiques occupation.
  - [x] Incidents recents.
  - [x] Mouvements recents.
  - [x] Activites du jour.
  - [x] Alertes capacite/incidents.
  - [x] Bonus : squelette tablette surveillant `/guard/tablet` (recherche UID reelle, pointage a venir au Commit 22).

- [x] Reprise et reparation des pages ajoutees par la PR #10 (`feature/Front`), fusionnee directement sur `main` :
  - [x] `templates/base.html.twig` etait casse (markup hors de `<body>`, bloc `body` duplique) ; restaure a sa version saine.
  - [x] Routes `/inmates`, `/cells`, `/incidents`, `/tablet` etaient accessibles sans role (aucune entree `access_control` ne les couvrait) ; corrige au Commit 11.
  - [x] Controleurs adaptes : repositories typees au lieu de requetes brutes via `EntityManagerInterface`, suppression du pattern `try/catch` generique.
  - [x] Templates `inmate/index`, `incident/index`, `structure/cells` migres vers `layout/app.html.twig` et le design system `app.css` (suppression de `public/styles/pas.css`, doublon de feuille de style).
  - [x] Deux `TabletController` en conflit ; fusionnes en un seul controleur securise (`/guard/tablet` et `/tablet`).
  - [x] `HomeController` simplifie en redirection par role (l'ancien dashboard dedie faisait doublon avec `DashboardService`).

- [x] Commit 19 - `Ajouter les pages de gestion des detenus`
  - [x] Liste detenus avec recherche UID.
  - [x] Filtres statut/niveau.
  - [x] Fiche detenu.
  - [x] Formulaire creation/modification (premier formulaire dynamique Symfony du projet, `InmateType`).
  - [x] Historique affectations, transferts, activites, incidents (relations existantes de `Inmate`).
  - [x] Acces : consultation `ROLE_GUARD`, creation/modification `ROLE_MANAGER`.

- [x] Commit 20 - `Ajouter les pages de structure penitentiaire`
  - [x] Liste batiments/ailes/cellules (`/buildings`, fiche batiment avec ses ailes, `/cells`).
  - [x] Fiche cellule (`/cells/{id}`).
  - [x] Occupants actifs.
  - [x] Historique cellule (affectations passees et en cours).
  - [x] CRUD admin des referentiels principaux : `Building`, `Wing`, `Cell` (`BuildingType`, `WingType`, `CellType`), creation/modification `ROLE_ADMIN`, suppression bloquee si des enfants existent (pas de cascade destructive).

- [x] Commit 21 - `Ajouter les formulaires d affectation et transfert`
  - [x] Formulaire affectation dynamique (`AssignmentRequestType`, non lie a l'entite, delegue au `AssignmentService` existant).
  - [x] Cellules disponibles selon batiment/aile/capacite (`CellRepository::createAvailableForAssignmentQueryBuilder`, groupe par batiment/aile dans le select).
  - [x] Formulaire transfert interne/externe (`TransferRequestType`), bascule des champs via JS selon le type choisi.
  - [x] Validation serveur complete : contraintes de formulaire + regles metier de `TransferService`/`AssignmentService` (exceptions capturees et affichees).
  - [x] Boutons "Affecter" / "Transferer" sur la fiche detenu selon son etat d'affectation.

- [x] Commit 22 - `Ajouter les vues activites et tablette surveillant`
  - [x] Interface tablette surveillant (`/guard/tablet`).
  - [x] Pointage activite par UID/zone (`ActivityParticipationService`, cree ou met a jour la participation).
  - [x] Gestion activites : formulaire rapide `ActivityQuickType` couvrant tous les types (`CANTINE`, `PROMENADE`, `ATELIER`, etc.).
  - [x] UX rapide et responsive : reutilise le layout responsive existant, formulaires courts en 2 colonnes desktop / 1 colonne tablette.

- [ ] Commit 23 - `Ajouter les pages incidents et audit`
  - [ ] Liste incidents avec filtres.
  - [ ] Creation incident.
  - [ ] Traitement incident selon role.
  - [ ] Journal d'audit filtre par utilisateur, entite, action, date.

- [ ] Commit 24 - `Ajouter l administration des utilisateurs`
  - [ ] Liste utilisateurs.
  - [ ] Creation/modification.
  - [ ] Roles.
  - [ ] Activation/desactivation.
  - [ ] Hash password.

## Phase 6 - API JSON

- [ ] Commit 25 - `Ajouter les groupes de serialisation API`
  - [ ] Ajouter groupes Serializer sur entites exposees.
  - [ ] Masquer donnees sensibles.
  - [ ] Normaliser les erreurs JSON.

- [ ] Commit 26 - `Ajouter les endpoints API operationnels`
  - [ ] `GET /api/v1/inmates/{uid}`.
  - [ ] `GET /api/v1/cells/{id}/occupancy`.
  - [ ] `POST /api/v1/activities/{id}/participations`.
  - [ ] `POST /api/v1/incidents`.
  - [ ] `GET /api/v1/dashboard/summary`.
  - [ ] Proteger endpoints par roles.

## Phase 7 - Fixtures et donnees de demonstration

- [ ] Commit 27 - `Ajouter les fixtures de demonstration`
  - [ ] Comptes `admin@pas.test`, `manager@pas.test`, `guard@pas.test`.
  - [ ] Mot de passe documente dans README.
  - [ ] 2 batiments, 4 ailes, 20 cellules.
  - [ ] 50 detenus avec UID unique.
  - [ ] Affectations, transferts, activites, incidents, audit logs.

## Phase 8 - Tests et qualite

- [ ] Commit 28 - `Tester les regles metier d affectation`
  - [ ] Tester depassement capacite.
  - [ ] Tester statut non affectable.
  - [ ] Tester affectation active unique.

- [ ] Commit 29 - `Ajouter les tests fonctionnels de securite`
  - [ ] WebTestCase login admin.
  - [ ] Acces dashboard selon role.
  - [ ] Refus acces routes admin pour guard.

- [ ] Commit 30 - `Tester l API et les formulaires dynamiques`
  - [ ] Tester `/api/v1/inmates/{uid}`.
  - [ ] Tester creation incident API.
  - [ ] Tester formulaire affectation dynamique.

- [ ] Commit 31 - `Ajouter le workflow de qualite et tests`
  - [ ] Ajouter workflow CI.
  - [ ] Installer dependances.
  - [ ] Lancer lint container/config/Twig.
  - [ ] Lancer PHPStan niveau 5.
  - [ ] Lancer PHPUnit.

## Phase 9 - Documentation et livraison

- [ ] Commit 32 - `Completer le guide d installation et d utilisation`
  - [ ] Prerequis Docker et hors Docker.
  - [ ] Configuration `.env.local`.
  - [ ] Commandes database, migrations, fixtures.
  - [ ] Commandes tests/qualite.
  - [ ] Identifiants de demo.
  - [ ] Architecture du projet.

- [ ] Commit 33 - `Ajouter les notes de deploiement`
  - [ ] Choisir plateforme de deploiement.
  - [ ] Documenter variables d'environnement de production.
  - [ ] Ajouter URL publique si disponible.
  - [ ] Documenter limites connues.

- [ ] Commit 34 - `Preparer la livraison finale du projet`
  - [ ] Verifier criteres d'acceptation.
  - [ ] Verifier au moins 10 pages Twig.
  - [ ] Verifier au moins 10 entites, heritage, relations attendues.
  - [ ] Verifier API, Mailer, HttpClient.
  - [ ] Lancer CI localement.
  - [ ] Nettoyer README et captures si necessaire.

## Definition of done globale

- [ ] `docker compose up --build` fonctionne.
- [ ] Les migrations passent sur base vide.
- [ ] Les fixtures chargent un jeu coherent.
- [ ] Les trois roles peuvent se connecter.
- [ ] Les regles metier critiques sont testees.
- [ ] Les pages Twig principales sont accessibles selon droits.
- [ ] Les endpoints API repondent en JSON.
- [x] Les notifications email sont historisees.
- [ ] HttpClient est utilise via un service testable.
- [ ] La CI passe.
- [ ] Le README permet a une personne externe de lancer le projet.
