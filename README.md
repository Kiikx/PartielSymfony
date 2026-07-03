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
