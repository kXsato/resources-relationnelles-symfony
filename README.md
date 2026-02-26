# Ressources Relationnelles

## Prérequis

- [Docker](https://docs.docker.com/get-docker/)
- [Make](https://www.gnu.org/software/make/)
- [mkcert](https://github.com/FiloSottile/mkcert/releases)

## Configuration HTTPS

Le projet utilise un certificat TLS local signé par [mkcert](https://github.com/FiloSottile/mkcert/releases).

### 1. Installer mkcert

Télécharge le binaire pour ton OS sur la [page des releases GitHub](https://github.com/FiloSottile/mkcert/releases).

**Linux**

```bash
sudo apt install libnss3-tools
chmod +x mkcert-linux-amd64
sudo mv mkcert-linux-amd64 /usr/local/bin/mkcert
```

**Windows** — Renomme le binaire en `mkcert.exe` et place-le dans un dossier du PATH (ex: `C:\Windows\System32\`).

### 2. Installer le CA local dans le navigateur

```bash
mkcert -install
```

### 3. Générer le certificat du projet

```bash
cd .docker/caddy/certs
mkcert resources-relationnelles.test
```

### 4. Configurer le fichier hosts

> **Linux** : ajoute cette ligne dans `/etc/hosts`

> **Windows** : ajoute cette ligne dans `C:\Windows\System32\drivers\etc\hosts` (en tant qu'administrateur)

```
127.0.0.1 resources-relationnelles.test
```

## Installation

**1. Copier le fichier d'environnement local**

```bash
cp .env .env.local
```

Modifier les valeurs dans `.env.local` si nécessaire.

**2. Lancer l'installation**

```bash
make install
```

Cette commande effectue dans l'ordre :

- Construction des images Docker
- Démarrage des conteneurs
- Installation des dépendances PHP (`composer install`)
- Compilation du thème Tailwind
- Initialisation de la base de données (drop, create, migrate)
- Chargement des fixtures

## Commandes disponibles

```bash
make help
```
