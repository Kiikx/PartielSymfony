# PartielSymfony

Projet Symfony initialise et dockerise.

## Prerequis

- Docker lance
- Docker Compose

Sous Windows avec WSL, active aussi l'integration de ta distribution Ubuntu dans Docker Desktop:
`Settings > Resources > WSL Integration`.

Composer et PHP ne sont pas obligatoires sur la machine hote: ils sont fournis par le conteneur `app`.

## Lancer le projet

Depuis la racine du projet:

```bash
docker compose up --build
```

L'application sera disponible sur:

```text
http://localhost:8000
```

La base MySQL est exposee sur le port `3306` avec ces identifiants:

```text
Host: 127.0.0.1
Database: symfony
User: symfony
Password: symfony
Root password: root
```

## Donnees de demonstration

Appliquer les migrations puis charger les fixtures:

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

Les fixtures creent une base de demonstration complete: 2 batiments, 4 ailes, 20 cellules, 50 detenus, affectations, transferts, activites, incidents et audit.

```text
Admin: admin@pas.test
Manager: manager@pas.test
Surveillant: guard@pas.test
Mot de passe commun: Password123!
```

Comptes supplementaires disponibles dans les fixtures:

```text
Manager Seine Est: manager.seine@pas.test
Surveillant Paris: guard.paris@pas.test
Mot de passe commun: Password123!
```

## Commandes utiles

Installer ou mettre a jour les dependances Composer:

```bash
docker compose exec app composer install
```

Lancer la console Symfony:

```bash
docker compose exec app php bin/console
```

Vider le cache:

```bash
docker compose exec app php bin/console cache:clear
```

Lancer les verifications qualite:

```bash
docker compose exec app ./vendor/bin/phpunit
docker compose exec app ./vendor/bin/phpstan analyse --no-progress
docker compose exec app php bin/console lint:yaml config --env=test
docker compose exec app php bin/console lint:twig templates --env=test
docker compose exec app php bin/console lint:container --env=test
```

Entrer dans le conteneur applicatif:

```bash
docker compose exec app bash
```

Arreter les conteneurs:

```bash
docker compose down
```

Arreter les conteneurs et supprimer le volume de base de donnees:

```bash
docker compose down -v
```

## Structure Docker

- `Dockerfile`: image PHP 8.3 avec Apache, Composer et extensions PHP utiles a Symfony.
- `docker-compose.yml`: service web Symfony et base MySQL.
- `docker/vhost.conf`: virtual host Apache pointant vers `public/`.
- `docker/entrypoint.sh`: installe automatiquement les dependances si `vendor/` est absent.

## Deploiement production Infomaniak

Le site de production doit pointer vers le dossier `public/` du projet Symfony.

```text
Document root: ~/sites/ESGI-4IWA-Symfony-PAS.davidmgr.fr/PartielSymfony/public
URL publique: https://esgi-4iwa-symfony-pas.davidmgr.fr
```

Variables minimales a definir dans `.env.local` sur le serveur:

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=change-me
DATABASE_URL="mysql://USER:PASSWORD@HOST:3306/DATABASE?charset=utf8mb4"
```

Commandes de mise a jour:

```bash
git pull origin main
APP_ENV=prod APP_DEBUG=0 composer install --no-dev --optimize-autoloader
APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:migrate --no-interaction
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup
```

Creation d'un administrateur sans fixtures:

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console app:create-admin-user admin@pas.test 'Password123!' David Mgr --super-admin --service=Direction
```

Les fixtures de demonstration utilisent `DoctrineFixturesBundle`, installe en dependance de developpement. Pour les charger ponctuellement sur une base de demonstration:

```bash
APP_ENV=dev APP_DEBUG=0 php bin/console doctrine:fixtures:load --no-interaction
```

Cette commande purge la base avant de charger les donnees.

## Integration continue

Le workflow GitHub Actions `.github/workflows/ci.yml` s'execute sur chaque pull request et chaque push sur `main`.

Il lance:

- validation Composer;
- lint YAML, Twig et container Symfony;
- analyse statique PHPStan;
- tests PHPUnit.
