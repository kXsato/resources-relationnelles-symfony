# Ressources Relationnelles

## Prérequis

- [Docker](https://docs.docker.com/get-docker/)
- [Make](https://www.gnu.org/software/make/)

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
