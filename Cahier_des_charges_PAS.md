**CAHIER DES CHARGES COMPLET**

**PAS - Prison Administrator System**

*Projet Symfony 6.x / 7.x & Twig - Sujet 2*

| **Thématique**        | **Technologie**             | **Groupe**            | **Version**               |
|-----------------------|-----------------------------|-----------------------|---------------------------|
| Gestion pénitentiaire | Symfony 6/7, Twig, Doctrine | Killian, Iness, David | Cahier des charges révisé |

# Sommaire

- 1\. Contexte et objectifs

- 2\. Périmètre fonctionnel

- 3\. Utilisateurs, rôles et droits

- 4\. Cas d’utilisation principaux

- 5\. Règles de gestion métier

- 6\. Architecture technique Symfony

- 7\. Architecture des données et MCD textuel

- 8\. API, communications et intégrations

- 9\. Interfaces Twig attendues

- 10\. Sécurité, traçabilité et conformité

- 11\. Jeux de données, tests, CI/CD et déploiement

- 12\. Organisation de l’équipe, planning et critères d’acceptation

# 1. Contexte et objectifs

PAS (Prison Administrator System) est une application web
professionnelle destinée à centraliser et moderniser les opérations d’un
établissement pénitentiaire. Elle remplace des processus dispersés ou
manuels par une plateforme unique, sécurisée et consultable depuis un
navigateur sur poste desktop et tablette.

## 1.1 Problématique

- Données détenus, cellules, bâtiments et activités actuellement
  fragmentées.

- Manque de visibilité en temps réel sur l’occupation des cellules et
  les statuts des détenus.

- Risque d’erreurs lors des affectations, transferts, sorties ou
  changements de statut.

- Besoin d’une traçabilité complète des actions sensibles et d’une
  séparation stricte des rôles.

## 1.2 Objectifs opérationnels

- Centraliser les dossiers détenus dans une base cohérente et
  historisée.

- Gérer bâtiments, ailes, cellules, affectations et capacités en
  respectant les contraintes métier.

- Permettre aux surveillants d’effectuer les opérations terrain sur
  tablette : présence, promenade, cantine, atelier, incident.

- Fournir un tableau de bord temps réel : occupation, alertes,
  incidents, mouvements, activités du jour.

- Sécuriser les accès, journaliser les actions et limiter les droits
  selon les rôles.

- Satisfaire les exigences techniques du projet Symfony : Doctrine,
  Twig, API JSON, Mailer, HttpClient, fixtures, tests et CI/CD.

# 2. Périmètre fonctionnel

## 2.1 Fonctionnalités incluses

- Gestion complète des détenus : création, consultation, modification,
  changement de statut, sortie.

- Gestion des bâtiments, ailes, cellules et capacités.

- Affectation d’un détenu à une cellule avec historique des
  affectations.

- Gestion des transferts internes et externes, avec motifs et suivi.

- Suivi des activités quotidiennes : cantine, promenade, atelier,
  rendez-vous, contrôle de présence.

- Déclaration et suivi des incidents.

- Gestion des utilisateurs applicatifs et de leurs rôles.

- Administration globale : CRUD, statistiques, filtres avancés et export
  éventuel.

- Notifications par e-mail pour les événements sensibles : transfert,
  incident grave, sortie programmée.

- Endpoint API JSON versionné pour exposer certaines données
  opérationnelles.

- Consommation d’une API externe via HttpClient, par exemple
  géocodage/adresse d’établissement ou service météo/sécurité pour
  planifier les promenades.

## 2.2 Hors périmètre

- Gestion judiciaire complète des dossiers pénaux et pièces
  confidentielles avancées.

- Système biométrique réel ou connexion à des portes physiques.

- Application mobile native ; l’usage tablette est couvert par une
  interface web responsive.

# 3. Utilisateurs, rôles et droits

Le système contient au minimum trois rôles afin de répondre aux
exigences du Sujet 2 et de cloisonner les fonctionnalités.

| **Rôle Symfony** | **Profil métier**                            | **Droits principaux**                                                                                                                     |
|------------------|----------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------|
| ROLE_ADMIN       | Administrateur                               | Accès complet : utilisateurs, détenus, bâtiments, cellules, affectations, transferts, statistiques, paramètres, journal d’audit.          |
| ROLE_MANAGER     | Responsable pénitentiaire / Chef de bâtiment | Supervision d’un périmètre : validation des transferts, consultation des tableaux de bord, gestion des incidents, suivi des affectations. |
| ROLE_GUARD       | Surveillant / Maton                          | Interface terrain tablette : consultation des détenus de sa zone, pointage activités, déclaration incident, consultation UID.             |
| ROLE_USER        | Utilisateur authentifié minimal              | Accès de base après connexion ; rôle parent éventuel dans la hiérarchie Symfony.                                                          |

## 3.1 Hiérarchie des rôles prévue

- ROLE_ADMIN hérite de ROLE_MANAGER, ROLE_GUARD et ROLE_USER.

- ROLE_MANAGER hérite de ROLE_GUARD et ROLE_USER.

- ROLE_GUARD hérite de ROLE_USER.

- Les droits fins sont complétés par un Voter personnalisé.

# 4. Cas d’utilisation principaux

| **ID** | **Cas d’utilisation**            | **Acteur**                    | **Scénario résumé**                                                                                         | **Résultat attendu**                                    |
|--------|----------------------------------|-------------------------------|-------------------------------------------------------------------------------------------------------------|---------------------------------------------------------|
| UC-01  | Se connecter / se déconnecter    | Utilisateur                   | L’utilisateur saisit ses identifiants ; Symfony Security authentifie et applique les rôles.                 | Session ouverte et droits chargés.                      |
| UC-02  | Créer un détenu                  | Admin, Manager autorisé       | Saisie identité, UID/numéro d’écrou, statut, niveau de sécurité, documents administratifs simples.          | Dossier détenu créé avec UID unique.                    |
| UC-03  | Affecter un détenu à une cellule | Admin, Manager                | Recherche cellule disponible, contrôle capacité, contrôle statut détenu, création affectation.              | Affectation active et historique conservé.              |
| UC-04  | Transférer un détenu             | Admin, Manager                | Sélection détenu, cellule source/cible ou établissement externe, motif, date, validation.                   | Transfert enregistré et notification envoyée.           |
| UC-05  | Pointer une activité quotidienne | Surveillant                   | Depuis tablette, le surveillant sélectionne la zone, le détenu et l’activité : cantine, promenade, atelier. | Participation enregistrée et visible dans l’historique. |
| UC-06  | Déclarer un incident             | Surveillant, Manager          | Saisie type, gravité, détenus concernés, lieu, description, pièces éventuelles.                             | Incident créé, responsable notifié si gravité élevée.   |
| UC-07  | Consulter le tableau de bord     | Admin, Manager                | Affichage occupation, alertes, incidents, activités du jour, mouvements récents.                            | Vision globale actualisée.                              |
| UC-08  | Administrer les référentiels     | Admin                         | CRUD utilisateurs, bâtiments, cellules, types d’activité, statuts.                                          | Données de référence maintenues.                        |
| UC-09  | Consommer l’API terrain          | Surveillant / client tablette | Appel /api/v1/cells/{id}/inmates ou /api/v1/inmates/{uid}.                                                  | Réponse JSON normalisée.                                |
| UC-10  | Auditer une action sensible      | Admin                         | Consultation filtrée par utilisateur, entité, date et action.                                               | Traçabilité exploitable.                                |

# 5. Règles de gestion métier

1.  Le UID d’un détenu correspond au numéro d’écrou et doit être unique,
    non vide et non réutilisé pour un autre détenu.

2.  Une cellule ne peut pas dépasser sa capacité maximale.

3.  Un détenu ne peut avoir qu’une seule affectation active à un instant
    donné.

4.  Un détenu au statut SORTI ou TRANSFERE_EXTERNE ne peut pas être
    affecté à une cellule active.

5.  Un transfert interne clôture automatiquement l’affectation
    précédente et en ouvre une nouvelle.

6.  Les incidents de gravité élevée déclenchent une notification e-mail
    aux responsables.

7.  Un surveillant ne peut modifier que les activités et incidents
    associés à sa zone ou à sa garde.

8.  Les suppressions de données sensibles doivent être limitées aux
    administrateurs et, si possible, remplacées par une
    désactivation/archivage.

9.  Toutes les actions sensibles sont inscrites dans AuditLog avec
    utilisateur, date, action, cible et adresse IP si disponible.

10. Les formulaires d’affectation et de transfert doivent être
    dynamiques : les cellules proposées dépendent du bâtiment, de la
    capacité et du statut.

# 6. Architecture technique Symfony

- Framework : Symfony 6.x ou 7.x, PHP 8.2+, Twig pour le rendu serveur.

- ORM : Doctrine ORM avec migrations et fixtures
  DoctrineFixturesBundle/Faker.

- Sécurité : Security Component, Password Hasher, formulaires
  login/logout natifs, voters, access_control.

- Administration : EasyAdminBundle ou back-office Twig sur mesure.

- API : contrôleurs /api/v1 avec Serializer, groupes de
  normalisation/dénormalisation et réponses JSON.

- Communication : Mailer/Notifier et Messenger pour les traitements
  asynchrones si retenu.

- Qualité : tests PHPUnit, WebTestCase, PHPStan niveau 5 minimum, linter
  Symfony dans CI.

- Déploiement : hébergement public type Render, Platform.sh, Heroku, VPS
  ou équivalent.

## 6.1 Découpage applicatif recommandé

| **Couche**      | **Responsabilité**                                                |
|-----------------|-------------------------------------------------------------------|
| Controller Twig | Pages HTML, formulaires, sécurité de route, orchestration simple. |
| Controller API  | Endpoints JSON versionnés et sérialisation.                       |
| Service métier  | Affectation, transfert, libération, notification, audit.          |
| Repository      | Requêtes QueryBuilder optimisées, filtres et statistiques.        |
| Entity          | Modèle Doctrine, relations, contraintes de validation.            |
| FormType        | Formulaires Symfony dynamiques et validation.                     |

# 7. Architecture des données et MCD textuel

Le modèle proposé contient plus de 10 entités Doctrine et respecte les
exigences : héritage d’entités, au moins 2 relations ManyToMany et au
moins 8 relations OneToMany/ManyToOne.

| **Entité**            | **Type**                        | **Champs principaux**                                                                     | **Rôle**                                                  |
|-----------------------|---------------------------------|-------------------------------------------------------------------------------------------|-----------------------------------------------------------|
| User                  | Entité parente abstraite ou STI | id, email, password, roles, firstName, lastName, isActive, createdAt                      | Héritage Doctrine avec AdminUser, ManagerUser, GuardUser. |
| AdminUser             | Sous-type User                  | service, superAdmin                                                                       | Profil administrateur.                                    |
| ManagerUser           | Sous-type User                  | managedBuilding                                                                           | Chef de bâtiment ou responsable.                          |
| GuardUser             | Sous-type User                  | badgeNumber, assignedZone                                                                 | Surveillant terrain.                                      |
| Inmate                | Entité métier                   | id, uid, firstName, lastName, birthDate, status, securityLevel, arrivalDate, releaseDate  | UID unique obligatoire.                                   |
| Building              | Structure                       | id, name, code, address, active                                                           | Contient des ailes et cellules.                           |
| Wing                  | Structure                       | id, name, floor, building                                                                 | Aile ou étage.                                            |
| Cell                  | Structure                       | id, number, capacity, status, wing                                                        | Capacité et état.                                         |
| Assignment            | Historique                      | id, inmate, cell, startAt, endAt, reason, createdBy                                       | Affectation détenu-cellule.                               |
| Transfer              | Mouvement                       | id, inmate, fromCell, toCell, externalDestination, type, reason, scheduledAt, validatedBy | Transfert interne/externe.                                |
| Activity              | Activité                        | id, type, label, scheduledAt, location, createdBy                                         | Promenade, atelier, cantine.                              |
| ActivityParticipation | Liaison enrichie                | id, inmate, activity, status, checkedAt, checkedBy                                        | Participation et pointage.                                |
| Incident              | Sécurité                        | id, title, description, severity, occurredAt, cell, reportedBy, status                    | Déclaration d’incident.                                   |
| AuditLog              | Traçabilité                     | id, actor, action, entityClass, entityId, createdAt, ipAddress, details                   | Journal des actions sensibles.                            |
| Notification          | Communication                   | id, recipient, subject, channel, status, sentAt                                           | Historique Mailer/Notifier.                               |

## 7.1 Héritage Doctrine obligatoire

L’héritage est implémenté sur l’entité User, afin de factoriser
l’authentification et de spécialiser les profils applicatifs. Deux
stratégies possibles : Single Table Inheritance avec un champ
discriminator type, ou Class Table Inheritance si l’équipe souhaite
séparer les tables par profil.

- User : champs communs et implémentation
  UserInterface/PasswordAuthenticatedUserInterface.

- AdminUser : droits complets et paramètres d’administration.

- ManagerUser : responsable d’un bâtiment ou périmètre.

- GuardUser : badge, zone et actions terrain.

## 7.2 Relations principales

| **Relation**                                    | **Type Doctrine**         | **Description**                                                           |
|-------------------------------------------------|---------------------------|---------------------------------------------------------------------------|
| Building 1 - N Wing                             | OneToMany / ManyToOne     | Un bâtiment contient plusieurs ailes.                                     |
| Wing 1 - N Cell                                 | OneToMany / ManyToOne     | Une aile contient plusieurs cellules.                                     |
| Cell 1 - N Assignment                           | OneToMany / ManyToOne     | Une cellule reçoit plusieurs affectations dans le temps.                  |
| Inmate 1 - N Assignment                         | OneToMany / ManyToOne     | Un détenu possède un historique d’affectations.                           |
| Inmate 1 - N Transfer                           | OneToMany / ManyToOne     | Un détenu peut être transféré plusieurs fois.                             |
| User 1 - N AuditLog                             | OneToMany / ManyToOne     | Un utilisateur produit plusieurs traces.                                  |
| User 1 - N Incident                             | OneToMany / ManyToOne     | Un surveillant ou manager déclare plusieurs incidents.                    |
| Cell 1 - N Incident                             | OneToMany / ManyToOne     | Une cellule peut être liée à plusieurs incidents.                         |
| Inmate N - N Activity via ActivityParticipation | ManyToMany avec attributs | Pointage d’activité, statut et surveillant.                               |
| Inmate N - N Incident                           | ManyToMany                | Plusieurs détenus peuvent être concernés par un incident, et inversement. |

## 7.3 Contraintes et index

- Index unique sur Inmate.uid.

- Contrainte unique possible sur Cell.number + Wing.

- Index sur Assignment.endAt pour retrouver rapidement l’affectation
  active.

- Index sur Incident.severity, Incident.occurredAt et
  Transfer.scheduledAt pour les tableaux de bord.

- Validation Symfony : NotBlank, Length, Choice, PositiveOrZero,
  LessThanOrEqual selon les champs.

# 8. API, communications et intégrations

## 8.1 Endpoints API JSON proposés

| **Méthode** | **Endpoint**                           | **Usage**                                          | **Accès**            |
|-------------|----------------------------------------|----------------------------------------------------|----------------------|
| GET         | /api/v1/inmates/{uid}                  | Retourne la fiche synthétique d’un détenu par UID. | ROLE_GUARD minimum   |
| GET         | /api/v1/cells/{id}/occupancy           | Retourne occupation, capacité, détenus présents.   | ROLE_GUARD minimum   |
| POST        | /api/v1/activities/{id}/participations | Enregistre un pointage terrain.                    | ROLE_GUARD minimum   |
| POST        | /api/v1/incidents                      | Déclare un incident depuis tablette.               | ROLE_GUARD minimum   |
| GET         | /api/v1/dashboard/summary              | Statistiques d’occupation et alertes.              | ROLE_MANAGER minimum |

Le Serializer Symfony utilisera des groupes comme inmate:read,
cell:read, activity:write, incident:write afin de contrôler finement les
champs exposés en lecture/écriture.

## 8.2 Envoi de courriels

- Notification aux managers lors d’un incident de gravité élevée.

- Notification lors de la validation d’un transfert ou d’une sortie
  programmée.

- Option asynchrone via Messenger pour ne pas ralentir les formulaires
  métier.

## 8.3 API externe via HttpClient

- Option prioritaire : API de géocodage pour normaliser l’adresse d’un
  établissement ou d’une destination de transfert.

- Option alternative : API météo pour afficher les conditions avant les
  promenades extérieures.

- Les appels externes doivent être encapsulés dans un service dédié,
  testable et sécurisé par variables d’environnement.

# 9. Interfaces Twig attendues

L’application doit comporter au moins 10 pages distinctes générées via
Twig avec héritage de templates, blocs, composants réutilisables et UX
soignée.

| **\#** | **Page**                           | **Description**                                                         |
|--------|------------------------------------|-------------------------------------------------------------------------|
| 1      | Page de connexion                  | Authentification sécurisée.                                             |
| 2      | Dashboard administrateur           | Statistiques, alertes, occupation, incidents récents.                   |
| 3      | Liste des détenus                  | Recherche UID, filtres statut/niveau, pagination.                       |
| 4      | Fiche détenu                       | Informations, affectation active, activités, incidents, historique.     |
| 5      | Création/modification détenu       | Formulaire validé Symfony.                                              |
| 6      | Liste bâtiments/ailes/cellules     | Occupation et états.                                                    |
| 7      | Fiche cellule                      | Capacité, occupants, historique, incidents.                             |
| 8      | Formulaire d’affectation/transfert | Formulaire dynamique dépendant du bâtiment et des cellules disponibles. |
| 9      | Interface tablette surveillant     | Pointage terrain rapide par zone et UID.                                |
| 10     | Gestion des activités              | Cantine, promenade, ateliers, présences.                                |
| 11     | Gestion des incidents              | Création, suivi, filtrage par gravité/statut.                           |
| 12     | Journal d’audit                    | Recherche par utilisateur, entité, action et date.                      |
| 13     | Administration utilisateurs        | CRUD rôles et activation des comptes.                                   |

## 9.1 Formulaires dynamiques obligatoires

- Formulaire d’affectation : choix du bâtiment puis chargement des
  ailes/cellules disponibles via Form Events PRE_SET_DATA et PRE_SUBMIT.

- Formulaire de transfert : champs différents selon transfert interne ou
  externe.

- Formulaire d’incident : si gravité élevée, champs complémentaires
  obligatoires et notification automatique.

# 10. Sécurité, traçabilité et conformité

- Mots de passe hachés via Password Hasher Symfony.

- Contrôle d’accès par routes et par rôles dans security.yaml.

- Voter personnalisé AssignmentVoter ou IncidentVoter pour vérifier les
  permissions dynamiques.

- Protection CSRF sur tous les formulaires Twig.

- Validation serveur systématique ; aucune confiance dans les données
  envoyées par le client.

- Journalisation AuditLog des actions sensibles : création/suppression
  détenu, transfert, changement cellule, incident, modification rôle.

- Données minimisées dans les réponses API grâce aux groupes Serializer.

- Gestion des erreurs : messages utilisateur non sensibles, logs
  techniques côté serveur.

## 10.1 Voter personnalisé retenu

IncidentVoter : un administrateur peut tout consulter et modifier ; un
manager peut traiter les incidents de son bâtiment ; un surveillant peut
créer un incident et modifier uniquement les incidents qu’il a déclarés
tant qu’ils sont au statut BROUILLON ou OUVERT.

# 11. Jeux de données, tests, CI/CD et déploiement

## 11.1 Fixtures obligatoires

- Comptes de test : admin@pas.test, manager@pas.test, guard@pas.test
  avec mot de passe documenté dans README.

- Au moins 2 bâtiments, 4 ailes, 20 cellules avec capacités variées.

- Au moins 50 détenus générés avec Faker et UID unique.

- Affectations actives et historiques, transferts, activités, incidents
  et audit logs réalistes.

- Jeu de données chargeable via doctrine:fixtures:load.

## 11.2 Tests obligatoires

- Au moins 1 test unitaire : service AssignmentService empêchant le
  dépassement de capacité.

- Au moins 1 test fonctionnel WebTestCase : connexion puis accès au
  dashboard selon rôle.

- Tests complémentaires recommandés : IncidentVoter, endpoint API
  /api/v1/inmates/{uid}, formulaires dynamiques.

## 11.3 Pipeline CI

- Installation des dépendances Composer.

- Linter Symfony : config, container et Twig.

- PHPStan niveau 5 minimum.

- Exécution PHPUnit.

- Option : build assets et contrôle de style PHP CS Fixer.

## 11.4 README d’installation attendu

- Prérequis : PHP, Composer, Symfony CLI, base de données, Node si
  assets.

- Configuration .env.local et DATABASE_URL.

- Commandes : composer install, doctrine:database:create,
  doctrine:migrations:migrate, doctrine:fixtures:load, symfony serve.

- Commandes tests et CI locales.

- Identifiants des comptes de test par rôle.

- URL de déploiement public.

# 12. Organisation de l’équipe, planning et critères d’acceptation

| **Membre**                                     | **Responsabilités**                                                                                                                                                                                                                                                |
|------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Killian - Lead Tech & backend partagé          | Architecture Symfony, initialisation du repository, base de données, authentification, conventions de développement, entités principales, migrations, CI/CD et déploiement. Collaboration avec David sur les services métier d’affectation, transfert et incident. |
| Iness - Lead Frontend & UX/UI                  | Maquettes, intégration Twig, dashboard, interface tablette, ergonomie terrain, composants visuels et parcours utilisateur.                                                                                                                                         |
| David - Lead Backend & logique métier partagée | API JSON, repositories QueryBuilder, fixtures, tests, règles métier d’affectation/transfert/incident, intégration Mailer/HttpClient et validation de la cohérence des données. Collaboration avec Killian sur les entités, services et migrations.                 |

## 12.1 Planning indicatif

| **Période** | **Objectifs**                                                                |
|-------------|------------------------------------------------------------------------------|
| Semaine 1   | Cadrage, MCD, initialisation Symfony, sécurité, base du README.              |
| Semaine 2   | Entités Doctrine, migrations, fixtures, CRUD principaux, dashboard initial.  |
| Semaine 3   | Affectations, transferts, activités, incidents, formulaires dynamiques.      |
| Semaine 4   | API JSON, Mailer, HttpClient, audit logs, interface tablette.                |
| Semaine 5   | Tests, PHPStan, CI, corrections UX, déploiement, finalisation documentation. |

## 12.2 Critères d’acceptation

- Le cahier des charges décrit clairement le projet, les rôles et les
  cas d’utilisation.

- Le modèle de données contient au moins 10 entités Doctrine, un
  héritage d’entités, 2 ManyToMany et 8 OneToMany/ManyToOne.

- L’application propose au moins 10 pages Twig distinctes.

- L’authentification, les rôles et au moins un Voter personnalisé sont
  opérationnels.

- Une API JSON /api/v1 existe avec Serializer et groupes.

- Mailer/Notifier et HttpClient sont intégrés.

- Fixtures, README, tests unitaires/fonctionnels, CI et déploiement sont
  fournis.

# 13. Bonus techniques envisageables

| **Bonus**         | **Mise en œuvre PAS**                                                         |
|-------------------|-------------------------------------------------------------------------------|
| Temps réel        | Mercure pour actualiser occupation, incidents et mouvements sur le dashboard. |
| Asynchronisme     | Messenger pour notifications e-mail et tâches lourdes.                        |
| Commandes CLI     | Commande pas:archive-old-audit-logs ou pas:release-scheduled-inmates.         |
| Tests de mutation | Infection PHP sur services critiques.                                         |
| DDD / TDD         | Découpage Domain/Application/Infrastructure pour affectations et incidents.   |
