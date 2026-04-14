# Culture Radar

Plateforme de decouverte et de recommandation d'evenements culturels en France.

Site en ligne : https://cultureradar.netlify.app

---

## Presentation du projet

Culture Radar est une application web statique developpee dans le cadre du projet de certification RNCP 38018 — Chef de Projet Digital (IA School, promotion B3 2024-2025). Le projet simule le travail d'une agence digitale fictive, InnovaDigital Agency, mandatee par la cliente Mme Isabelle Lemoine (CultureRadar SAS).

La plateforme permet aux utilisateurs de decouvrir des evenements culturels personnalises selon leurs preferences et leur localisation, et offre aux organisateurs un espace dedie pour publier et gerer leurs evenements.

---

## Equipe projet

| Membre | Role |
|--------|------|
| Safiatou | Chef de projet |
| Manouk | Developpement |
| Gloria AMINI | UX/UI Design |
| Hidaya | Web Marketing |

---

## Structure du site

Le site est compose de 21 fichiers :

```
culture-radar/
├── index.html              Page d'accueil
├── evenements.html         Liste et filtrage des evenements (12 categories)
├── evenement-detail.html   Fiche dynamique de chaque evenement avec reservation
├── search.html             Recherche en texte libre sur tous les evenements
├── organisateurs.html      Espace B2B pour les organisateurs
├── pourquoi.html           Fonctionnalites de la plateforme
├── connexion.html          Page de connexion
├── inscription.html        Page d'inscription
├── onboarding.html         Questionnaire de profil culturel (post-inscription)
├── contact.html            Formulaire de contact
├── 404.html                Page d'erreur personnalisee
├── mentions-legales.html   Mentions legales
├── confidentialite.html    Politique de confidentialite
├── cgu.html                Conditions generales d'utilisation
├── cookies.html            Politique de gestion des cookies
├── style.css               Feuille de style principale (design system)
├── accessibility.css       Styles du widget d'accessibilite
├── accessibility.js        Widget d'accessibilite (WCAG 2.1 AA)
├── script.js               Interactions globales
├── sitemap.xml             Plan du site pour les moteurs de recherche
└── robots.txt              Instructions pour les crawlers
```

---

## Fonctionnalites

### Cote utilisateur
- Decouverte de 18 evenements repartis en 12 categories culturelles
- Filtrage en temps reel par categorie, date, prix, public et lieu
- Recherche en texte libre sur tous les evenements
- Fiche evenement dynamique avec formulaire de reservation
- Questionnaire d'onboarding pour creer un profil culturel personnalise
- Compte de demonstration : test@culture-radar.fr / password123

### Cote organisateur
- Presentation des avantages et tarifs B2B
- Formulaire d'inscription partenaire avec validation

### Accessibilite (WCAG 2.1 AA)
- Widget d'accessibilite avec panneau de preferences :
  - Taille de texte ajustable (normal, grand, tres grand)
  - Mode contraste eleve
  - Police dyslexie (OpenDyslexic)
  - Guide de lecture
  - Simulation daltonisme (protanopie, deuteranopie, tritanopie, monochrome)
  - Reduction des animations
  - Mode TDAH
- Skip-link sur toutes les pages
- Attributs ARIA sur tous les elements interactifs
- Focus visible conforme WCAG 2.1

### SEO et conformite technique
- Balises meta description sur toutes les pages
- Twitter Cards sur toutes les pages
- Balise Open Graph sur la page d'accueil
- Sitemap XML avec 13 URLs
- Robots.txt configure
- Structure de titres H1-H2-H3 hierarchisee
- Fil d'Ariane sur les pages secondaires

### RGPD et legal
- Bandeau cookies avec choix Accepter / Refuser / Personnaliser
- Page mentions legales complete
- Politique de confidentialite
- CGU
- Politique de gestion des cookies

---

## Technologies utilisees

- HTML5 semantique
- CSS3 avec variables CSS (design system)
- JavaScript vanilla (sans framework)
- Hebergement : Netlify (deploiement continu depuis GitHub)
- Polices : DM Serif Display + DM Sans (Google Fonts)

---

## Design system

| Element | Valeur |
|---------|--------|
| Couleur principale | #4A2C8A (violet) |
| Couleur secondaire | #D4A017 (or) |
| Fond sombre | #0D0720 |
| Police titres | DM Serif Display |
| Police corps | DM Sans |

---

## Deploiement

Le site est deploye automatiquement sur Netlify a chaque push sur la branche `main` du repo GitHub.

URL de production : https://cultureradar.netlify.app

---

## Contexte academique

Projet realise dans le cadre de la certification RNCP38018 — Chef de Projet Digital, niveau 6.
Etablissement : IA School, Paris.
Annee academique : 2024-2025.

Ce projet est fictif. Aucune reservation reelle ne peut etre effectuee. Les donnees presentees sont simulees a des fins pedagogiques.
