# culture-radar

**Votre boussole culturelle intelligente**

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Railway](https://img.shields.io/badge/Deployed%20on-Railway-0B0D0E?style=flat-square&logo=railway&logoColor=white)](https://railway.app)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

> Plateforme de recommandations culturelles personnalisées par IA pour découvrir les événements culturels en Île-de-France.

**Site en production** : [https://ias-b3-g7-teampossible-paris.up.railway.app](https://ias-b3-g7-teampossible-paris.up.railway.app)

---

## Table des matières

- [À propos](#-à-propos)
- [Fonctionnalités](#-fonctionnalités)
- [Technologies](#-technologies)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Déploiement](#-déploiement)
- [Structure du projet](#-structure-du-projet)
- [API](#-api)
- [Équipe](#-équipe)
- [Licence](#-licence)

---

## À propos

**CultureRadar** est une plateforme web innovante qui utilise l'intelligence artificielle pour aider les utilisateurs à découvrir les événements culturels qui correspondent à leurs goûts en Île-de-France.

### Le problème résolu

-  **Saturation d'informations** : Trop de plateformes éparpillées (Facebook Events, Eventbrite, Instagram...)
-  **Manque de temps** : Difficulté à trouver des événements adaptés à ses goûts
-  **Culture invisible** : Les événements de proximité et lieux indépendants sont peu visibles

### Notre solution

Une plateforme unique avec recommandations personnalisées par IA, prenant en compte :
- Vos préférences culturelles (musique, théâtre, expos, patrimoine)
- Votre localisation et moyens de transport
- Vos contraintes pratiques (météo, horaires, budget)

---

##  Fonctionnalités

### 👤 Utilisateurs (B2C)

| Fonctionnalité | Description |
|----------------|-------------|
|  **Inscription/Connexion** | Création de compte sécurisé avec validation email |
|  **Onboarding personnalisé** | Configuration des préférences culturelles |
|  **Recommandations IA** | Score de compatibilité pour chaque événement |
|  **Recherche avancée** | Filtres par catégorie, date, prix, distance |
|  **Favoris** | Sauvegarde des événements intéressants |
|  **Tableau de bord** | Historique et statistiques personnelles |

###  Organisateurs (B2B)

| Fonctionnalité | Description |
|----------------|-------------|
|  **Gestion d'événements** | Création, modification, suppression |
|  **Statistiques** | Vues, clics, sauvegardes par événement |
|  **Audience insights** | Données sur les profils des visiteurs |
|  **Mise en avant** | Options de visibilité premium |

### 🔧 Administration

| Fonctionnalité | Description |
|----------------|-------------|
|  **Analytics globaux** | Métriques de la plateforme |
|  **Gestion utilisateurs** | Modération des comptes |
|  **Validation événements** | Workflow de modération |
|  **Configuration** | Paramètres système |

---

##  Technologies

### Backend
- **PHP 8.1+** - Langage serveur
- **MySQL 8.0+** - Base de données relationnelle
- **PDO** - Couche d'abstraction base de données

### Frontend
- **HTML5 / CSS3** - Structure et styles
- **JavaScript ES6+** - Interactivité
- **Tailwind CSS** - Framework CSS utilitaire
- **Font Awesome** - Icônes

### Infrastructure
- **Railway** - Hébergement cloud (PaaS)
- **Docker** - Conteneurisation
- **Apache** - Serveur web

### Outils
- **Google Fonts** - Typographies (Poppins, Inter)
- **Lighthouse** - Audit performance/accessibilité
- **WAVE** - Audit accessibilité

---

##  Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        FRONTEND                              │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐        │
│  │ Landing │  │ Discover│  │Dashboard│  │  Admin  │        │
│  │  Page   │  │  Page   │  │  User   │  │  Panel  │        │
│  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘        │
└───────┼────────────┼────────────┼────────────┼──────────────┘
        │            │            │            │
        ▼            ▼            ▼            ▼
┌─────────────────────────────────────────────────────────────┐
│                        BACKEND PHP                           │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │   Router    │  │  Services   │  │   Classes   │          │
│  │  (index)    │  │  (API/Auth) │  │ (Recommend) │          │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘          │
└─────────┼────────────────┼────────────────┼─────────────────┘
          │                │                │
          ▼                ▼                ▼
┌─────────────────────────────────────────────────────────────┐
│                     DATABASE MySQL                           │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐        │
│  │  users  │  │ events  │  │organizers│ │profiles │        │
│  └─────────┘  └─────────┘  └─────────┘  └─────────┘        │
└─────────────────────────────────────────────────────────────┘
```

---

##  Installation

### Prérequis

- PHP 8.1 ou supérieur
- MySQL 8.0 ou supérieur
- Composer (optionnel)
- Serveur Apache ou Nginx

### Installation locale

1. **Cloner le repository**
```bash
git clone https://github.com/glow-123/culture-radar.git
cd culture-radar
```

2. **Configurer la base de données**
```bash
# Créer la base de données
mysql -u root -p < sql/setup-database.sql
```

3. **Configurer les variables d'environnement**
```bash
# Copier le fichier de configuration
cp config.example.php config.php

# Éditer avec vos paramètres
nano config.php
```

4. **Lancer le serveur de développement**
```bash
php -S localhost:8000
```

5. **Accéder à l'application**
```
http://localhost:8000
```

### Installation avec MAMP (macOS)

Voir le guide détaillé : [MAMP_SETUP_GUIDE.md](MAMP_SETUP_GUIDE.md)

---

## ⚙️ Configuration

### Variables d'environnement

Créer un fichier `.env` ou configurer dans `config.php` :

```php
// Base de données
DB_HOST=localhost
DB_NAME=culture_radar
DB_USER=root
DB_PASS=your_password

// Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

// API Keys (optionnel)
OPENWEATHER_API_KEY=your_api_key
GOOGLE_MAPS_API_KEY=your_api_key
```

### Configuration Railway

Pour le déploiement sur Railway, configurer ces variables :

```
MYSQL_HOST=...
MYSQL_DATABASE=railway
MYSQL_USER=root
MYSQL_PASSWORD=...
MYSQL_PORT=3306
```

Voir : [RAILWAY_ENV_VARS.txt](RAILWAY_ENV_VARS.txt)

---

##  Déploiement

### Railway (Recommandé)

1. **Connecter le repository GitHub à Railway**
2. **Configurer les variables d'environnement**
3. **Railway détecte automatiquement le `Dockerfile.railway`**
4. **Le déploiement est automatique à chaque push**

### Docker

```bash
# Build l'image
docker build -t culture-radar .

# Lancer le conteneur
docker run -p 8080:80 culture-radar
```

### Docker Compose

```bash
docker-compose up -d
```

---

##  Structure du projet

```
culture-radar/
├── admin/                  # Panel d'administration
│   └── dashboard.php       # Tableau de bord admin
├── api/                    # Endpoints API
│   ├── events.php          # API événements
│   └── users.php           # API utilisateurs
├── assets/                 # Ressources statiques
│   ├── css/                # Feuilles de style
│   ├── js/                 # Scripts JavaScript
│   └── images/             # Images
├── cache/                  # Cache application
├── classes/                # Classes PHP
│   └── RecommendationEngine.php  # Moteur IA
├── includes/               # Fichiers inclus
│   ├── header.php          # En-tête commun
│   ├── footer.php          # Pied de page commun
│   └── favicon.php         # Favicons
├── maquettes/              # Maquettes UI/UX
├── organizer/              # Espace organisateurs B2B
│   ├── dashboard.php       # Tableau de bord organisateur
│   └── login.php           # Connexion organisateur
├── scripts/                # Scripts utilitaires
├── services/               # Services métier
├── sql/                    # Scripts SQL
│   └── setup-database.sql  # Initialisation BDD
├── config.php              # Configuration
├── index.php               # Page d'accueil
├── login.php               # Connexion utilisateur
├── register.php            # Inscription
├── dashboard.php           # Tableau de bord utilisateur
├── discover.php            # Page découverte
├── contact.php             # Page contact
├── legal.php               # Mentions légales
├── privacy.php             # Politique confidentialité
├── terms.php               # CGU
├── cookies.php             # Politique cookies
├── Dockerfile.railway      # Config Docker Railway
├── railway.json            # Config Railway
└── README.md               # Ce fichier
```

---

##  API

### Endpoints disponibles

#### Événements

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/events.php` | Liste des événements |
| `GET` | `/api/events.php?id={id}` | Détail d'un événement |
| `POST` | `/api/events.php` | Créer un événement (auth) |
| `PUT` | `/api/events.php?id={id}` | Modifier un événement (auth) |
| `DELETE` | `/api/events.php?id={id}` | Supprimer un événement (auth) |

#### Utilisateurs

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/auth/login.php` | Connexion |
| `POST` | `/api/auth/register.php` | Inscription |
| `GET` | `/api/users.php?id={id}` | Profil utilisateur (auth) |

---

##  Performances

Résultats audit Lighthouse (Janvier 2026) :

| Métrique | Mobile | Bureau |
|----------|--------|--------|
| **Performance** | 85/100 | 99/100 |
| **Accessibilité** | 97/100 | 97/100 |
| **Bonnes pratiques** | 100/100 | 100/100 |
| **SEO** | 100/100 | 100/100 |

---

##  Accessibilité

Le site est conforme aux normes **WCAG 2.1 niveau AA** :

- ✅ Navigation au clavier complète
- ✅ Contrastes de couleurs validés (WAVE)
- ✅ Attributs ARIA appropriés
- ✅ Widget d'accessibilité intégré (8 modes)
- ✅ Skip links pour navigation rapide

---

##  Équipe

**Projet Mission Possible - IA School B3 2024-2025**

| Membre | Rôle |
|--------|------|
| **Safiatou Baldé** | Chef de projet |
| **Manouk Glasius** | Développeur |
| **Gloria Amini** | UX/UI Designer |
| **Hidaya Msallem** | Webmarketer |


<p align="center">
  <strong>CultureRadar</strong> - Votre boussole culturelle intelligente 
  <br>
  <a href="https://ias-b3-g7-teampossible-paris.up.railway.app">Visiter le site</a>
</p>

