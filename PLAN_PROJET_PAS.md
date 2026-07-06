# Plan de realisation - PAS

Ce plan decoupe le cahier des charges en etapes livrables. Chaque bloc correspond a un commit logique pour garder un historique Git lisible.

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

- [ ] Commit 10 - `Ajouter la connexion et la deconnexion`
  - [ ] Ajouter le formulaire de connexion Twig.
  - [ ] Ajouter logout.
  - [ ] Protections CSRF.
  - [ ] Redirection selon role apres connexion.

- [ ] Commit 11 - `Ajouter le controle d acces par roles`
  - [ ] Proteger dashboard, admin, manager, guard.
  - [ ] Ajouter les `access_control`.
  - [ ] Verifier la hierarchy ROLE_ADMIN > ROLE_MANAGER > ROLE_GUARD > ROLE_USER.

- [ ] Commit 12 - `Ajouter le voter des incidents`
  - [ ] Creer `IncidentVoter`.
  - [ ] Admin : tout.
  - [ ] Manager : incidents de son batiment.
  - [ ] Guard : creation et modification de ses incidents ouverts/brouillons.
  - [ ] Ajouter tests unitaires du voter.

## Phase 4 - Services metier

- [ ] Commit 13 - `Ajouter le service d affectation`
  - [ ] Empecher depassement de capacite.
  - [ ] Empecher affectation si statut SORTI ou TRANSFERE_EXTERNE.
  - [ ] Garantir une seule affectation active par detenu.
  - [ ] Journaliser l'action dans `AuditLog`.
  - [ ] Ajouter test unitaire obligatoire.

- [ ] Commit 14 - `Ajouter le service de transfert`
  - [ ] Gerer transfert interne.
  - [ ] Gerer transfert externe.
  - [ ] Cloturer l'affectation source.
  - [ ] Creer nouvelle affectation si transfert interne.
  - [ ] Preparer notification email.

- [ ] Commit 15 - `Ajouter les services incidents et notifications`
  - [ ] Creer incidents via service.
  - [ ] Notifier les managers en gravite elevee.
  - [ ] Historiser les notifications.
  - [ ] Journaliser creation et traitement.

- [ ] Commit 16 - `Ajouter le service d information externe`
  - [ ] Integrer HttpClient.
  - [ ] Encapsuler une API externe meteo ou geocodage.
  - [ ] Configurer les variables d'environnement.
  - [ ] Ajouter un fallback propre si API indisponible.

## Phase 5 - Back-office Twig

- [ ] Commit 17 - `Ajouter la mise en page Twig principale`
  - [ ] Template base.
  - [ ] Navigation selon role.
  - [ ] Flash messages.
  - [ ] Styles simples et responsive desktop/tablette.

- [ ] Commit 18 - `Ajouter le tableau de bord`
  - [ ] Statistiques occupation.
  - [ ] Incidents recents.
  - [ ] Mouvements recents.
  - [ ] Activites du jour.
  - [ ] Alertes capacite/incidents.

- [ ] Commit 19 - `Ajouter les pages de gestion des detenus`
  - [ ] Liste detenus avec recherche UID.
  - [ ] Filtres statut/niveau.
  - [ ] Fiche detenu.
  - [ ] Formulaire creation/modification.
  - [ ] Historique affectations, activites, incidents.

- [ ] Commit 20 - `Ajouter les pages de structure penitentiaire`
  - [ ] Liste batiments/ailes/cellules.
  - [ ] Fiche cellule.
  - [ ] Occupants actifs.
  - [ ] Historique cellule.
  - [ ] CRUD admin des referentiels principaux.

- [ ] Commit 21 - `Ajouter les formulaires d affectation et transfert`
  - [ ] Formulaire affectation dynamique.
  - [ ] Cellules disponibles selon batiment/aile/capacite.
  - [ ] Formulaire transfert interne/externe.
  - [ ] Validation serveur complete.

- [ ] Commit 22 - `Ajouter les vues activites et tablette surveillant`
  - [ ] Interface tablette surveillant.
  - [ ] Pointage activite par UID/zone.
  - [ ] Gestion activites : cantine, promenade, atelier.
  - [ ] UX rapide et responsive.

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
- [ ] Les notifications email sont historisees.
- [ ] HttpClient est utilise via un service testable.
- [ ] La CI passe.
- [ ] Le README permet a une personne externe de lancer le projet.
