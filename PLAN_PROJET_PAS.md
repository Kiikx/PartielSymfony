# Plan de realisation - PAS

Ce plan decoupe le cahier des charges en etapes livrables. Chaque bloc correspond a un commit logique pour garder un historique Git lisible.

## Phase 0 - Socle projet

- [x] Commit 01 - `chore: finalize docker and environment setup`
  - [x] Garder `.env` comme configuration par defaut versionnee.
  - [x] Ignorer `.env.local` et `.env.*.local`.
  - [x] Corriger Docker pour Git `safe.directory`.
  - [x] Verifier `docker compose up --build`.
  - [x] Mettre a jour le README avec le lancement Docker.

- [x] Commit 02 - `chore: add project roadmap`
  - [x] Ajouter ce plan de suivi.
  - [x] Relier le plan au cahier des charges dans le README.

## Phase 1 - Dependances et configuration Symfony

- [x] Commit 03 - `chore: install core Symfony bundles`
  - [x] Installer Doctrine ORM, Migrations, MakerBundle.
  - [x] Installer Twig, SecurityBundle, Validator, Form, Asset.
  - [x] Installer DoctrineFixturesBundle et Faker en dev.
  - [x] Installer Serializer, Mailer, HttpClient.
  - [x] Ajouter PHPUnit, PHPStan et outils de lint.

- [ ] Commit 04 - `config: configure database and security defaults`
  - [ ] Configurer Doctrine avec MySQL.
  - [ ] Ajouter `security.yaml` avec hierarchy des roles.
  - [ ] Configurer le password hasher.
  - [ ] Preparer les routes publiques et protegees.

## Phase 2 - Modele de donnees Doctrine

- [ ] Commit 05 - `feat: add user inheritance model`
  - [ ] Creer `User` avec email, password, roles, nom, prenom, actif.
  - [ ] Ajouter l'heritage Doctrine pour `AdminUser`, `ManagerUser`, `GuardUser`.
  - [ ] Ajouter les champs specifiques : service, superAdmin, managedBuilding, badgeNumber, assignedZone.
  - [ ] Ajouter contraintes de validation et index utiles.

- [ ] Commit 06 - `feat: add prison structure entities`
  - [ ] Creer `Building`, `Wing`, `Cell`.
  - [ ] Ajouter les relations Building 1-N Wing et Wing 1-N Cell.
  - [ ] Ajouter capacite, statut cellule, code batiment, activation.
  - [ ] Ajouter contrainte unique cellule par aile.

- [ ] Commit 07 - `feat: add inmate and movement entities`
  - [ ] Creer `Inmate`.
  - [ ] Creer `Assignment`.
  - [ ] Creer `Transfer`.
  - [ ] Ajouter UID unique et statuts detenus.
  - [ ] Ajouter historique cellule/detenu et dates d'affectation.

- [ ] Commit 08 - `feat: add activity incident audit entities`
  - [ ] Creer `Activity`.
  - [ ] Creer `ActivityParticipation`.
  - [ ] Creer `Incident`.
  - [ ] Creer `AuditLog`.
  - [ ] Creer `Notification`.
  - [ ] Ajouter les ManyToMany : Inmate-Incident et Inmate-Activity via participation.

- [ ] Commit 09 - `db: add initial Doctrine migration`
  - [ ] Generer la migration.
  - [ ] Relire la migration.
  - [ ] Tester `doctrine:migrations:migrate`.

## Phase 3 - Authentification et droits

- [ ] Commit 10 - `feat: add login and logout`
  - [ ] Ajouter le formulaire de connexion Twig.
  - [ ] Ajouter logout.
  - [ ] Protections CSRF.
  - [ ] Redirection selon role apres connexion.

- [ ] Commit 11 - `feat: add role based access control`
  - [ ] Proteger dashboard, admin, manager, guard.
  - [ ] Ajouter les `access_control`.
  - [ ] Verifier la hierarchy ROLE_ADMIN > ROLE_MANAGER > ROLE_GUARD > ROLE_USER.

- [ ] Commit 12 - `feat: add incident voter`
  - [ ] Creer `IncidentVoter`.
  - [ ] Admin : tout.
  - [ ] Manager : incidents de son batiment.
  - [ ] Guard : creation et modification de ses incidents ouverts/brouillons.
  - [ ] Ajouter tests unitaires du voter.

## Phase 4 - Services metier

- [ ] Commit 13 - `feat: add assignment service`
  - [ ] Empecher depassement de capacite.
  - [ ] Empecher affectation si statut SORTI ou TRANSFERE_EXTERNE.
  - [ ] Garantir une seule affectation active par detenu.
  - [ ] Journaliser l'action dans `AuditLog`.
  - [ ] Ajouter test unitaire obligatoire.

- [ ] Commit 14 - `feat: add transfer service`
  - [ ] Gerer transfert interne.
  - [ ] Gerer transfert externe.
  - [ ] Cloturer l'affectation source.
  - [ ] Creer nouvelle affectation si transfert interne.
  - [ ] Preparer notification email.

- [ ] Commit 15 - `feat: add incident and notification services`
  - [ ] Creer incidents via service.
  - [ ] Notifier les managers en gravite elevee.
  - [ ] Historiser les notifications.
  - [ ] Journaliser creation et traitement.

- [ ] Commit 16 - `feat: add external information service`
  - [ ] Integrer HttpClient.
  - [ ] Encapsuler une API externe meteo ou geocodage.
  - [ ] Configurer les variables d'environnement.
  - [ ] Ajouter un fallback propre si API indisponible.

## Phase 5 - Back-office Twig

- [ ] Commit 17 - `feat: add base Twig layout`
  - [ ] Template base.
  - [ ] Navigation selon role.
  - [ ] Flash messages.
  - [ ] Styles simples et responsive desktop/tablette.

- [ ] Commit 18 - `feat: add dashboard`
  - [ ] Statistiques occupation.
  - [ ] Incidents recents.
  - [ ] Mouvements recents.
  - [ ] Activites du jour.
  - [ ] Alertes capacite/incidents.

- [ ] Commit 19 - `feat: add inmate management pages`
  - [ ] Liste detenus avec recherche UID.
  - [ ] Filtres statut/niveau.
  - [ ] Fiche detenu.
  - [ ] Formulaire creation/modification.
  - [ ] Historique affectations, activites, incidents.

- [ ] Commit 20 - `feat: add prison structure pages`
  - [ ] Liste batiments/ailes/cellules.
  - [ ] Fiche cellule.
  - [ ] Occupants actifs.
  - [ ] Historique cellule.
  - [ ] CRUD admin des referentiels principaux.

- [ ] Commit 21 - `feat: add assignment and transfer forms`
  - [ ] Formulaire affectation dynamique.
  - [ ] Cellules disponibles selon batiment/aile/capacite.
  - [ ] Formulaire transfert interne/externe.
  - [ ] Validation serveur complete.

- [ ] Commit 22 - `feat: add activity and guard tablet views`
  - [ ] Interface tablette surveillant.
  - [ ] Pointage activite par UID/zone.
  - [ ] Gestion activites : cantine, promenade, atelier.
  - [ ] UX rapide et responsive.

- [ ] Commit 23 - `feat: add incident and audit pages`
  - [ ] Liste incidents avec filtres.
  - [ ] Creation incident.
  - [ ] Traitement incident selon role.
  - [ ] Journal d'audit filtre par utilisateur, entite, action, date.

- [ ] Commit 24 - `feat: add user administration`
  - [ ] Liste utilisateurs.
  - [ ] Creation/modification.
  - [ ] Roles.
  - [ ] Activation/desactivation.
  - [ ] Hash password.

## Phase 6 - API JSON

- [ ] Commit 25 - `feat: add API serialization groups`
  - [ ] Ajouter groupes Serializer sur entites exposees.
  - [ ] Masquer donnees sensibles.
  - [ ] Normaliser les erreurs JSON.

- [ ] Commit 26 - `feat: add operational API endpoints`
  - [ ] `GET /api/v1/inmates/{uid}`.
  - [ ] `GET /api/v1/cells/{id}/occupancy`.
  - [ ] `POST /api/v1/activities/{id}/participations`.
  - [ ] `POST /api/v1/incidents`.
  - [ ] `GET /api/v1/dashboard/summary`.
  - [ ] Proteger endpoints par roles.

## Phase 7 - Fixtures et donnees de demonstration

- [ ] Commit 27 - `feat: add demo fixtures`
  - [ ] Comptes `admin@pas.test`, `manager@pas.test`, `guard@pas.test`.
  - [ ] Mot de passe documente dans README.
  - [ ] 2 batiments, 4 ailes, 20 cellules.
  - [ ] 50 detenus avec UID unique.
  - [ ] Affectations, transferts, activites, incidents, audit logs.

## Phase 8 - Tests et qualite

- [ ] Commit 28 - `test: cover assignment business rules`
  - [ ] Tester depassement capacite.
  - [ ] Tester statut non affectable.
  - [ ] Tester affectation active unique.

- [ ] Commit 29 - `test: add functional security coverage`
  - [ ] WebTestCase login admin.
  - [ ] Acces dashboard selon role.
  - [ ] Refus acces routes admin pour guard.

- [ ] Commit 30 - `test: cover API and dynamic forms`
  - [ ] Tester `/api/v1/inmates/{uid}`.
  - [ ] Tester creation incident API.
  - [ ] Tester formulaire affectation dynamique.

- [ ] Commit 31 - `ci: add lint phpstan and tests workflow`
  - [ ] Ajouter workflow CI.
  - [ ] Installer dependances.
  - [ ] Lancer lint container/config/Twig.
  - [ ] Lancer PHPStan niveau 5.
  - [ ] Lancer PHPUnit.

## Phase 9 - Documentation et livraison

- [ ] Commit 32 - `docs: complete installation and usage guide`
  - [ ] Prerequis Docker et hors Docker.
  - [ ] Configuration `.env.local`.
  - [ ] Commandes database, migrations, fixtures.
  - [ ] Commandes tests/qualite.
  - [ ] Identifiants de demo.
  - [ ] Architecture du projet.

- [ ] Commit 33 - `docs: add deployment notes`
  - [ ] Choisir plateforme de deploiement.
  - [ ] Documenter variables d'environnement de production.
  - [ ] Ajouter URL publique si disponible.
  - [ ] Documenter limites connues.

- [ ] Commit 34 - `release: prepare final project delivery`
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
- [ ] Les notifications email sont historisees.
- [ ] HttpClient est utilise via un service testable.
- [ ] La CI passe.
- [ ] Le README permet a une personne externe de lancer le projet.
