# DépôtScolaire - Share

Bienvenue dans le projet **DépôtScolaire** (ou **Share**), une plateforme web complète dédiée au dépôt et à la gestion des travaux scolaires pour les étudiants et les enseignants. Ce projet a été développé dans le cadre de l'épreuve E6 du BTS SIO.

## 🚀 Technologies Utilisées

- **Framework** : [Symfony 6.4](https://symfony.com/)
- **Frontend** : Twig & [Webpack Encore](https://symfony.com/doc/current/frontend.html)
- **Base de données** : MariaDB 11.4
- **Sécurité** : Authentification par formulaire avec gestion des rôles (Admin, Teacher, Student)
- **Docker** : Orchestration des services (Serveur web, Base de données, DbGate, Mailpit)
- **Fonctionnalités spécifiques** :
    - [QR Code Bundle](https://github.com/endroid/qr-code-bundle) pour le partage rapide.
    - [Verify Email Bundle](https://github.com/symfonycasts/verify-email-bundle) pour la validation des comptes.
    - [Doctrine Fixtures](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html) pour les données de test.

## 📋 Prérequis

- [Docker](https://www.docker.com/) et [Docker Compose](https://docs.docker.com/compose/)
- [Composer](https://getcomposer.org/) (si installation locale hors Docker)

## ⚙️ Installation

1. **Cloner le projet**
   ```bash
   git clone git@github.com:kaelianbaudelet/depotscolaire.git
   cd "Projet DepotScolaire"
   ```

2. **Lancer les services Docker**
   ```bash
   docker-compose up -d
   ```

3. **Installer les dépendances PHP** (via le conteneur)
   ```bash
   docker exec -it symfony_app composer install
   ```

4. **Initialiser la base de données**
   Exécutez les migrations et chargez les données de test :
   ```bash
   docker exec -it symfony_app php bin/console doctrine:migrations:migrate --no-interaction
   docker exec -it symfony_app php bin/console doctrine:fixtures:load --no-interaction
   ```

## 🛠 Services Inclus

- **Application Web** : Accessible sur [http://localhost:8080](http://localhost:8080)
- **DbGate** : Interface de gestion de base de données sur [http://localhost:8081](http://localhost:8081)
- **Mailpit** : Interface de capture d'emails (test) sur [http://localhost:8025](http://localhost:8025)

## 📂 Structure des Entités Principales

- **User** : Gestion des utilisateurs avec hiérarchie de rôles (Étudiant, Enseignant, Administrateur).
- **Classroom** : Organisation des étudiants par classes ou groupes.
- **Assignment** : Devoirs ou tâches créés par les enseignants pour une classe.
- **Submission** : Travaux déposés par les étudiants en réponse à un devoir.
- **Logs** : Suivi des activités sur la plateforme.

---
Développé avec ❤️ pour l'épreuve E6 du BTS SIO.
