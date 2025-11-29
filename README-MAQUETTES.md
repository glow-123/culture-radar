# 🎨 Culture Radar - Maquettes & Code Source

## 📋 Vue d'ensemble

Culture Radar est une plateforme de découverte culturelle intelligente utilisant l'IA pour recommander des événements personnalisés. Ce projet universitaire comprend 4 maquettes HTML interactives et le code source complet.

## 🖼️ Maquettes disponibles

### 1. **Page d'Accueil** (`mockup-homepage.html`)
- Hero section avec message principal
- Barre de recherche intelligente
- Filtres rapides (Aujourd'hui, Weekend, Gratuit)
- Grille de catégories (6 catégories principales)
- Section événements recommandés
- Process en 3 étapes
- Footer complet

**Éléments clés:**
- Navigation sticky
- CTA primaires et secondaires
- Intégration widget accessibilité
- Animations au survol

### 2. **Page Catégorie** (`mockup-category.html`)
- Hero avec statistiques de catégorie
- Fil d'Ariane (breadcrumb)
- Sous-catégories en chips
- **Sidebar de filtres:**
  - Filtres par date
  - Filtres par prix
  - Filtres par lieu
  - Accessibilité PMR
- **Grille d'événements:**
  - Cards avec badges (Best-seller, Gratuit, Dernières places)
  - Informations essentielles (date, lieu, prix)
  - Actions rapides (favoris, détails)
- Pagination
- Toggle vue carte/liste
- Newsletter intégrée

### 3. **Page Produit/Événement** (`mockup-product.html`)
- **Galerie d'images** avec vignettes
- **Informations détaillées:**
  - Catégorie et tags
  - Note et avis (324 avis)
  - Durée et public cible
- **Système d'onglets:**
  - Description
  - Distribution
  - Infos pratiques
  - Avis clients
- **Sidebar de réservation:**
  - Sélection date/heure
  - Choix quantité
  - Prix dynamique
  - Bouton réservation
  - Actions (favoris, partage)
- **Section lieu** avec carte
- **Avis clients** détaillés
- **Événements similaires** en carousel

### 4. **Page Contact** (`mockup-contact.html`)
- Hero minimaliste
- **Formulaire complet:**
  - Champs standards (nom, email, téléphone)
  - Catégorie de demande
  - Message avec compteur de caractères
  - Checkbox newsletter et CGU
- **Sidebar informations:**
  - Coordonnées complètes
  - Réseaux sociaux
  - Adresse physique
- **Section carte** interactive
- **FAQ** en accordéon
- **Call-to-action** final

## 🛠️ Technologies utilisées

### Frontend
- **HTML5** - Structure sémantique
- **CSS3** - Styles avancés avec gradients et animations
- **JavaScript** - Interactivité et validation
- **PHP** - Rendu dynamique et logique serveur

### Backend
- **PHP 8.2** - Logique métier
- **MySQL** - Base de données
- **PDO** - Connexion sécurisée BDD

### APIs intégrées
- **OpenAgenda API** - Événements culturels
- **Paris Open Data** - Événements municipaux
- **SerpAPI** - Google Events
- **OpenWeatherMap** - Météo

### Fonctionnalités
- 🔐 **Authentification** complète (login/register)
- ♿ **Accessibilité** avancée (widget complet)
- 📱 **Responsive** design
- 🌍 **Multi-langue** ready
- 🔍 **SEO** optimisé
- 🚀 **Performance** optimisée

## 📁 Structure du projet

```
culture-radar/
├── mockups/                  # Maquettes HTML
│   ├── index.html           # Page d'accès aux maquettes
│   ├── mockup-homepage.html # Maquette accueil
│   ├── mockup-category.html # Maquette catégorie
│   ├── mockup-product.html  # Maquette produit/événement
│   └── mockup-contact.html  # Maquette contact
│
├── assets/                   # Ressources
│   ├── css/
│   │   ├── style.css        # Styles principaux
│   │   └── accessibility.css # Styles accessibilité
│   └── js/
│       ├── main.js          # JS principal
│       └── accessibility.js # Widget accessibilité
│
├── api/                      # APIs
│   ├── events-aggregator.php # Agrégateur principal
│   ├── real-events.php      # OpenAgenda
│   └── google-events.php    # Google Events
│
├── services/                 # Services
│   └── OpenAgendaService.php # Service OpenAgenda
│
├── sql/                      # Base de données
│   ├── database.sql         # Structure BDD
│   └── contact_messages.sql # Table messages
│
├── includes/                 # Composants réutilisables
│   └── favicon.php          # Favicons
│
├── index.php                # Page d'accueil
├── contact.php              # Page contact
├── config.php               # Configuration
├── .env                     # Variables d'environnement
└── README-MAQUETTES.md      # Cette documentation
```

## 🚀 Installation

### Prérequis
- PHP 8.2+
- MySQL 5.7+
- Apache/Nginx
- Composer (optionnel)

### Étapes d'installation

1. **Extraire l'archive**
```bash
tar -xzf culture-radar-source.tar.gz
cd culture-radar
```

2. **Configurer la base de données**
```bash
mysql -u root -p < sql/database.sql
```

3. **Configurer l'environnement**
```bash
cp .env.example .env
# Éditer .env avec vos paramètres
```

4. **Lancer le serveur**
```bash
php -S localhost:8000
```

5. **Accéder aux maquettes**
```
http://localhost:8000/mockups/
```

## 🎯 Fonctionnalités principales

### Widget Accessibilité
- Mode dyslexie avec police OpenDyslexic
- Contraste élevé
- Tailles de texte ajustables
- Guide de lecture
- Modes daltoniens (4 types)
- Réduction des animations
- Navigation clavier complète

### Système de filtres
- Filtres temporels (Maintenant, Aujourd'hui, Demain, Weekend)
- Filtres par prix
- Filtres géographiques
- Filtres d'accessibilité
- Tri multi-critères

### Réservation
- Sélection date/heure
- Calcul dynamique du prix
- Places multiples
- Annulation gratuite
- E-billets

## 🔐 Sécurité

- Protection CSRF
- Validation des entrées
- Requêtes préparées PDO
- Sanitization des données
- Headers de sécurité HTTP
- Honeypot anti-spam

## 📱 Responsive Design

- Mobile-first approach
- Breakpoints: 640px, 768px, 968px, 1200px
- Touch-friendly
- Progressive Web App ready

## 🌟 Points forts du design

1. **Gradients modernes** - Utilisation de dégradés violet/bleu
2. **Glassmorphism** - Effets de verre dépoli
3. **Micro-interactions** - Animations subtiles au survol
4. **Typographie** - Poppins pour les titres, Inter pour le texte
5. **Iconographie** - Emojis et Font Awesome
6. **Espacement** - Design aéré et moderne

## 📊 Métriques de performance

- Lighthouse Score: 95+
- First Contentful Paint: < 1.5s
- Time to Interactive: < 3.5s
- Accessibility Score: 100

## 🤝 Contribution

Projet universitaire - Démonstration uniquement
Contact: contact@culture-radar.fr

## 📄 Licence

Projet universitaire - Usage éducatif uniquement

## 🏆 Crédits

- **Développement**: Équipe Culture Radar
- **Design**: Maquettes originales
- **Université**: Paris-Saclay
- **Année**: 2024

---

**Note**: Ceci est un projet universitaire de démonstration. Aucune transaction réelle n'est effectuée.