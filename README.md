# Projet Cyna IT - API Backend & Documentation Technique

Ce projet constitue le backend de l'application SaaS/E-commerce Cyna IT. Il s'agit d'une API RESTful stateless développée avec Symfony, chargée de la gestion du catalogue, des utilisateurs, du tunnel de commande et de la facturation.

## 1. Fonctionnalités Principales

- **Authentification et Sécurité :** Inscription, connexion et protection des routes via Token JWT.
- **Gestion du Profil :** Récupération des informations de l'utilisateur connecté.
- **Mot de passe oublié :** Réinitialisation de mot de passe par email avec token à usage unique (expiration 1h).
- **Catalogue :** Mise à disposition des produits de cybersécurité et de leurs catégories (tarification mensuelle et annuelle).
- **Tunnel d'Achat :** Validation du panier, recalcul automatique des tarifs côté serveur par mesure de sécurité, et enregistrement de la commande.
- **Facturation Automatique :** Génération à la volée des factures liées aux commandes avec calcul de la TVA (20 %), du montant HT et TTC.
- **Carnet d'Adresses :** Opérations de type CRUD sécurisées (un utilisateur ne peut gérer que ses propres adresses de facturation).

## 2. Stack Technique

- **Framework :** Symfony 8.1
- **PHP :** >= 8.4
- **Base de données :** MySQL 8.0 (via Doctrine ORM 3.6)
- **API :** API Platform 4.3 (doctrine-orm + symfony)
- **Authentification :** JWT (LexikJWTAuthenticationBundle 3.2)
- **CORS :** NelmioCorsBundle 2.6
- **Emails :** Symfony Mailer 8.1
- **Migrations :** Doctrine Migrations Bundle 4.0
- **Fixtures (dev) :** Doctrine Fixtures Bundle 4.3 + FakerPHP 1.24
- **Outils de développement (Docker) :**
    - Adminer (interface d'administration MySQL)
    - Mailpit (capture des emails en local, sans envoi réel)

## 3. Prérequis

Avant de commencer, assurez-vous de disposer des éléments suivants sur votre machine :

- PHP 8.4 ou supérieur
- Composer
- Docker et Docker Compose (pour MySQL, Adminer et Mailpit)
- OpenSSL (requis pour la génération des clés JWT)
- Symfony CLI (recommandé pour lancer le serveur de développement)

## 4. Configuration de l'environnement (.env)

Le dépôt contient un fichier `.env.example`. Pour configurer votre environnement local, vous devez dupliquer ce fichier et le renommer en `.env.local`.

Ouvrez ensuite votre nouveau fichier `.env.local` et modifiez/ajoutez les variables suivantes :

- **Sécurité globale Symfony :**
  Le champ `APP_SECRET` est vide par défaut. Renseignez une chaîne de caractères aléatoire.
  `APP_SECRET=une_chaine_de_caracteres_aleatoire_et_securisee`

- **La base de données :**
  Attention, le fichier `.env.example` propose une configuration PostgreSQL par défaut. Remplacez la ligne `DATABASE_URL` par vos identifiants MySQL locaux (le conteneur Docker `db` expose le port `3307`). Par exemple :
  `DATABASE_URL="mysql://cyna_user:cyna_password@127.0.0.1:3307/cyna?serverVersion=8.0.32&charset=utf8mb4"`
  _(Veillez à bien adapter le port - 3306 ou 3307 -, l'utilisateur et le mot de passe si vous avez modifié le `docker-compose.yml`.)_

- **Configuration CORS :**
  Cette variable est déjà présente dans l'exemple, vérifiez simplement qu'elle correspond à vos besoins pour autoriser le Front-end.
  `CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'`

- **URL du Front-end (À AJOUTER) :**
  Utilisée pour construire le lien de réinitialisation de mot de passe envoyé par email.
  `FRONTEND_URL=http://localhost:3000`

- **Emails (À AJOUTER) :**
  En développement, les emails sont interceptés par Mailpit plutôt qu'envoyés réellement.
  `MAILER_DSN=smtp://localhost:1025`

- **Sécurité JWT (À AJOUTER) :**
  Ces variables ne sont pas présentes dans le fichier `.env.example`. **Vous devez les ajouter manuellement** à la fin de votre fichier `.env.local` pour que l'authentification fonctionne :

```env
    ###> lexik/jwt-authentication-bundle ###
    JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
    JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
    JWT_PASSPHRASE=votre_phrase_secrete_complexe_a_modifier
    ###< lexik/jwt-authentication-bundle ###
```

## 5. Installation

Suivez ces étapes pour initialiser le projet en local :

    Cloner le répertoire :
    git clone https://github.com/projet-fil-rouge-h3/backend
    cd backend

    Installer les dépendances PHP :
    composer install

    Préparer l'environnement :
    Copiez le fichier d'exemple et configurez-le comme expliqué à l'étape 4.
    cp .env.example .env.local

    Générer les clés SSL pour l'authentification JWT :
    Cette commande va créer les fichiers private.pem et public.pem dans le dossier config/jwt/ en utilisant la passphrase que vous venez d'ajouter dans votre fichier .env.local.
    php bin/console lexik:jwt:generate-keypair

    Démarrer les services Docker (MySQL, Adminer, Mailpit) :
    docker compose up -d

    Initialiser la base de données :
    Créez la base de données :
    php bin/console doctrine:database:create

    Exécutez les migrations pour créer les tables :
    php bin/console doctrine:migrations:migrate

    Chargez les données factices (fixtures) pour avoir un catalogue et des utilisateurs de test :
    php bin/console doctrine:fixtures:load

## 6. Lancement du Projet

Pour démarrer le serveur de développement Symfony, exécutez la commande suivante à la racine du projet :
symfony server:start

L'API sera alors accessible, par défaut, à l'adresse http://127.0.0.1:8000.

## 7. Outils de développement (Docker)

En plus de l'API, `docker compose up -d` démarre deux interfaces web utiles en développement :

| Outil       | URL                   | Description                                                                                                                                          |
| ----------- | --------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Adminer** | http://localhost:8081 | Interface d'administration de la base de données MySQL. Serveur : `db`, utilisateur/mot de passe : voir `docker-compose.yml`.                        |
| **Mailpit** | http://localhost:8025 | Boîte de réception locale qui capture tous les emails envoyés par l'application (ex : réinitialisation de mot de passe) sans les envoyer réellement. |

## 8. Comptes de test (fixtures)

Après avoir exécuté `php bin/console doctrine:fixtures:load`, les comptes suivants sont disponibles :

| Rôle           | Email               | Mot de passe |
| -------------- | ------------------- | ------------ |
| Administrateur | `admin@cyna-it.fr`  | `admin123`   |
| Client         | `client@cyna-it.fr` | `client123`  |

Ces comptes et mots de passe sont uniquement destinés à l'environnement de développement/démo et ne doivent jamais être utilisés en production.

## 9. Routes de l'API (Endpoints)

Toutes les routes de l'API sont préfixées par `/api`.
Les routes marquées par la mention `[JWT]` nécessitent l'envoi du Token JWT dans le header (`Authorization: Bearer <token>`).

### Authentification & Profil

- `POST /api/auth/login` : Connexion (récupération du Token JWT).
- `POST /api/auth/register` : Inscription d'un nouvel utilisateur.
- `GET  /api/auth/me` [JWT] : Récupération des informations du profil connecté.
- `POST /api/auth/forgot-password` : Demande de réinitialisation de mot de passe (envoi d'un email contenant un lien à durée limitée).
- `POST /api/auth/reset-password` : Réinitialisation du mot de passe à partir du token reçu par email.

### Catalogue

- `GET  /api/products` : Liste des produits disponibles (tarifs et détails).
- `GET  /api/categories` : Liste des catégories de produits.

### Tunnel de Commande

- `POST /api/orders` [JWT] : Création d'une commande à partir d'un panier et génération de la facture.
- `GET  /api/orders` [JWT] : Récupération de l'historique des commandes de l'utilisateur.

### Carnet d'Adresses

- `GET    /api/addresses` [JWT] : Liste les adresses de l'utilisateur.
- `POST   /api/addresses` [JWT] : Ajoute une nouvelle adresse.
- `PUT    /api/addresses/{id}` [JWT] : Modifie une adresse existante.
- `DELETE /api/addresses/{id}` [JWT] : Supprime une adresse.
