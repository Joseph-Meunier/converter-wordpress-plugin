# WebP Converter
## EN

This project is a fork of [codepen](https://codepen.io/nosilleg/pen/XMdvoM) by [nosilleg](https://codepen.io/nosilleg).

It allows you to convert images to WebP format directly in the browser, without needing to download any additional software or limits.

## FR

Ce projet est un fork de [codepen](https://codepen.io/nosilleg/pen/XMdvoM) par [nosilleg](https://codepen.io/nosilleg).

Il permet de convertir des images au format WebP directement dans le navigateur, sans avoir besoin de télécharger un logiciel supplémentaire ou de limite.



faire 
Je vais repartir de zéro et tout dérouler, étape par étape, avec un choix simple :
HTML/CSS statique servi par un container **Caddy** derrière **Traefik**, déployé via **GitHub Actions** sur ton VPS.

---

## Vue d’ensemble

Chaîne complète :

1. Ton code (HTML/CSS) est sur GitHub.
2. GitHub Actions **build** une image Docker avec Caddy qui sert tes fichiers.
3. GitHub Actions **push** l’image dans un registry (GitHub Container Registry).
4. GitHub Actions se connecte en **SSH** à ton VPS et fait :

   * `docker compose pull frontend`
   * `docker compose up -d frontend`
5. Traefik route `https://ton-domaine` vers le container `frontend`.

---

## Étape 1 – Organiser ton projet

Sur ton repo GitHub, organise ton code statique comme ceci :

```text
.
├─ public/
│  ├─ index.html
│  ├─ style.css
│  └─ images/...
├─ Dockerfile
├─ .dockerignore
└─ (éventuellement README.md, etc.)
```

Tu mets **tous tes fichiers HTML/CSS/JS** dans `public/`.

---

## Étape 2 – Créer l’image Docker (Caddy)

### 2.1. `Dockerfile`

À la racine du repo :

```dockerfile
# Image Caddy officielle
FROM caddy:alpine

# On copie ton site statique dans le dossier servi par Caddy
COPY public /usr/share/caddy
```

C’est tout.
Par défaut, Caddy sert ce répertoire sur le **port 80** dans le container.

### 2.2. `.dockerignore`

Pour ne pas embarquer n’importe quoi dans l’image :

```gitignore
.git
.github
Dockerfile
.dockerignore
README.md
node_modules
```

(adapte si besoin)

---

## Étape 3 – Préparer le VPS (docker-compose + Traefik)

Sur ton VPS, tu as déjà Docker, docker-compose et Traefik.
On va ajouter un service `frontend` dans un `docker-compose.yml` dédié, par exemple dans `/opt/frontend-site`.

### 3.1. Dossier et fichier compose

Sur le VPS :

```bash
mkdir -p /opt/frontend-site
cd /opt/frontend-site
nano docker-compose.yml
```

Contenu minimal (à adapter avec ton domaine, ton user GitHub, etc.) :

```yaml
services:
  frontend:
    image: ghcr.io/<user-github>/<nom-image>:latest
    container_name: frontend
    restart: unless-stopped
    networks:
      - web
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.frontend.rule=Host(`ton-domaine.com`)"
      - "traefik.http.routers.frontend.entrypoints=websecure"
      - "traefik.http.routers.frontend.tls.certresolver=letsencrypt"
      - "traefik.http.services.frontend.loadbalancer.server.port=80"

networks:
  web:
    external: true
```

Points importants :

* `image: ghcr.io/<user-github>/<nom-image>:latest`
  → remplace `<user-github>` par ton pseudo GitHub, et `<nom-image>` par un nom de ton choix (ex: `site-static`).
* `web` est un réseau **externe** existant que Traefik utilise déjà. Si tu ne l’as pas encore :

```bash
docker network create web
```

### 3.2. Premier lancement à la main (plus tard)

Tu ne pourras la lancer qu’une fois que l’image aura été envoyée dans le registry, mais le fichier est prêt.

---

## Étape 4 – Configurer GitHub Container Registry (GHCR)

Ton image sera poussée en `ghcr.io/<user-github>/<nom-image>:tag`.

### 4.1. Créer un PAT (Personal Access Token)

1. Sur GitHub, va dans **Settings → Developer settings → Personal access tokens → Fine-grained tokens** (ou classic).
2. Crée un token avec au minimum les droits :

   * `read:packages`
   * `write:packages`
3. Copie le token (une seule fois visible).

### 4.2. Ajouter les secrets dans le repo GitHub

Dans ton repo → **Settings → Secrets and variables → Actions → New repository secret** :

Crée les secrets suivants :

* `REGISTRY_USERNAME` = ton pseudo GitHub
* `REGISTRY_TOKEN` = le PAT que tu viens de créer
* `VPS_HOST` = IP ou domaine de ton VPS (ex. `1.2.3.4` ou `vps.mondomaine.com`)
* `VPS_USER` = utilisateur SSH sur le VPS (ex. `debian`, `root`, ou ce que tu utilises)
* `VPS_SSH_KEY` = contenu de ta clé privée **qui correspond à la clé publique présente dans `~/.ssh/authorized_keys` sur le VPS**

Exemple pour récupérer la clé privée :

```bash
cat ~/.ssh/id_rsa
```

(colle TOUT le contenu dans `VPS_SSH_KEY`)

---

## Étape 5 – Workflow GitHub Actions

On ajoute un workflow qui :

1. Build l’image Docker.
2. La push dans GHCR.
3. Se connecte en SSH au VPS.
4. Fait `docker compose pull` + `docker compose up`.

### 5.1. Fichier `.github/workflows/deploy.yml`

Dans ton repo :

```yaml
name: CI / CD Static Frontend

on:
  push:
    branches:
      - main   # branche que tu veux déployer

env:
  IMAGE_NAME: ghcr.io/<user-github>/<nom-image>

jobs:
  build-and-push:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Log in to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ secrets.REGISTRY_USERNAME }}
          password: ${{ secrets.REGISTRY_TOKEN }}

      - name: Build Docker image
        run: |
          docker build -t $IMAGE_NAME:latest .

      - name: Push Docker image
        run: |
          docker push $IMAGE_NAME:latest

  deploy:
    needs: build-and-push
    runs-on: ubuntu-latest

    steps:
      - name: Add SSH key
        uses: webfactory/ssh-agent@v0.9.0
        with:
          ssh-private-key: ${{ secrets.VPS_SSH_KEY }}

      - name: Déployer sur le VPS
        run: |
          ssh -o StrictHostKeyChecking=no ${{ secrets.VPS_USER }}@${{ secrets.VPS_HOST }} << 'EOF'
            cd /opt/frontend-site
            docker compose pull frontend
            docker compose up -d frontend
            docker image prune -f
          EOF
```

Pense à remplacer dans `env.IMAGE_NAME` :

```yaml
IMAGE_NAME: ghcr.io/<user-github>/<nom-image>
```

---

## Étape 6 – Premier déploiement

1. Tu commits/push ton code sur la branche `main`.
2. GitHub Actions va :

   * builder l’image
   * la pousser sur GHCR
   * se connecter au VPS
   * tirer l’image et relancer le service `frontend`
3. Traefik route ton domaine `ton-domaine.com` vers le container `frontend` (port 80 → Caddy → tes fichiers HTML/CSS).

---

## Résumé ultra-condensé

1. Mettre ton site statique dans `public/`.
2. Ajouter un `Dockerfile` avec Caddy :

   ```dockerfile
   FROM caddy:alpine
   COPY public /usr/share/caddy
   ```
3. Sur le VPS, créer `/opt/frontend-site/docker-compose.yml` avec un service `frontend` et les labels Traefik.
4. Créer un PAT GitHub pour GHCR, ajouter les secrets (`REGISTRY_*`, `VPS_*`).
5. Ajouter le workflow `deploy.yml` dans `.github/workflows/`.
6. Pousser sur `main` → déploiement automatique.

Si tu veux, tu peux me donner :

* ton pseudo GitHub,
* ton domaine,
* le chemin exact de ton `index.html`,

et je te retourne les 3 fichiers déjà remplis (`Dockerfile`, `docker-compose.yml`, `deploy.yml`) prêts à copier/coller.

