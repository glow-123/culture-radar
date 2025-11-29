<?php
session_start();
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions Générales d'Utilisation - Culture Radar</title>
    <meta name="description" content="Conditions Générales d'Utilisation (CGU) de Culture Radar - Règles d'utilisation du service de recommandations culturelles">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .legal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0 2rem;
            text-align: center;
        }
        .legal-container {
            max-width: 900px;
            margin: -2rem auto 4rem;
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        h1 {
            margin: 0 0 1rem;
            font-size: 2.5rem;
        }
        h2 {
            color: #667eea;
            margin-top: 2.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            border-bottom: 2px solid #f0f0ff;
            padding-bottom: 0.5rem;
        }
        h3 {
            color: #333;
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        h4 {
            color: #555;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        p, li {
            line-height: 1.8;
            color: #555;
            margin-bottom: 1rem;
        }
        .info-block {
            background: #f8f8ff;
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            margin: 2rem 0;
            border-radius: 8px;
        }
        .warning-block {
            background: #fff8f0;
            border-left: 4px solid #ff9800;
            padding: 1.5rem;
            margin: 2rem 0;
            border-radius: 8px;
        }
        .nav-legal {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-legal-container {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            gap: 2rem;
            padding: 0 2rem;
        }
        .nav-legal a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 0;
        }
        .nav-legal a:hover {
            text-decoration: underline;
        }
        .nav-legal a.active {
            border-bottom: 2px solid #667eea;
        }
        .article {
            margin: 2rem 0;
            padding: 2rem;
            background: #fafafa;
            border-radius: 10px;
        }
        .article-number {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }
        ol, ul {
            padding-left: 2rem;
        }
        .toc {
            background: #f0f0ff;
            padding: 2rem;
            border-radius: 10px;
            margin: 2rem 0;
        }
        .toc h3 {
            margin-top: 0;
            color: #667eea;
        }
        .toc ul {
            list-style: none;
            padding-left: 0;
        }
        .toc li {
            margin: 0.5rem 0;
        }
        .toc a {
            color: #555;
            text-decoration: none;
        }
        .toc a:hover {
            color: #667eea;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="nav-legal">
        <div class="nav-legal-container">
            <a href="/" style="color: #333;"><i class="fas fa-home"></i> Accueil</a>
            <a href="/legal.php">Mentions Légales</a>
            <a href="/terms.php" class="active">CGU</a>
            <a href="/privacy.php">Confidentialité</a>
            <a href="/cookies.php">Cookies</a>
        </div>
    </nav>

    <div class="legal-header">
        <h1>Conditions Générales d'Utilisation</h1>
        <p>En vigueur depuis le 1er août 2024</p>
    </div>

    <div class="legal-container">
        <div class="warning-block">
            <p><strong>⚠️ Important :</strong> L'utilisation du service Culture Radar implique l'acceptation pleine et entière des présentes Conditions Générales d'Utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser nos services.</p>
        </div>

        <div class="toc">
            <h3>Sommaire</h3>
            <ul>
                <li><a href="#article1">Article 1 - Objet et Champ d'Application</a></li>
                <li><a href="#article2">Article 2 - Description du Service</a></li>
                <li><a href="#article3">Article 3 - Inscription et Compte Utilisateur</a></li>
                <li><a href="#article4">Article 4 - Utilisation du Service</a></li>
                <li><a href="#article5">Article 5 - Abonnement Premium</a></li>
                <li><a href="#article6">Article 6 - Responsabilité et Garanties</a></li>
                <li><a href="#article7">Article 7 - Propriété Intellectuelle</a></li>
                <li><a href="#article8">Article 8 - Modification des CGU</a></li>
                <li><a href="#article9">Article 9 - Droit Applicable et Juridiction</a></li>
            </ul>
        </div>

        <div class="article" id="article1">
            <h2><span class="article-number">Article 1</span> Objet et Champ d'Application</h2>
            
            <p>Les présentes Conditions Générales d'Utilisation (ci-après "CGU") définissent les conditions dans lesquelles CultureRadar SAS (ci-après "CultureRadar" ou "nous") met à disposition des utilisateurs (ci-après "Utilisateur" ou "vous") son service de recommandations culturelles personnalisées accessible via le site web cultureradar.fr et ses sous-domaines.</p>
            
            <p>L'utilisation du service CultureRadar implique l'acceptation pleine et entière des présentes CGU. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser nos services.</p>
            
            <div class="info-block">
                <p><strong>Date d'entrée en vigueur :</strong> 1er août 2024<br>
                <strong>Dernière mise à jour :</strong> Août 2024<br>
                <strong>Version :</strong> 1.0</p>
            </div>
        </div>

        <div class="article" id="article2">
            <h2><span class="article-number">Article 2</span> Description du Service</h2>
            
            <p>CultureRadar propose un service de découverte et de recommandation d'événements culturels basé sur l'intelligence artificielle. Le service comprend :</p>
            
            <ul>
                <li><strong>Recommandations personnalisées</strong> d'événements culturels selon vos préférences et contraintes</li>
                <li><strong>Agenda culturel personnel</strong> pour organiser vos sorties</li>
                <li><strong>Système de favoris</strong> et d'historique de vos activités culturelles</li>
                <li><strong>Partage social</strong> de découvertes avec la communauté</li>
                <li><strong>Notifications personnalisées</strong> (sur accord explicite de l'utilisateur)</li>
            </ul>
            
            <h3>Services gratuits</h3>
            <p>L'accès de base à CultureRadar est gratuit et comprend :</p>
            <ul>
                <li>Consultation des événements publics</li>
                <li>3 recommandations personnalisées par jour</li>
                <li>Création d'un profil utilisateur</li>
                <li>Sauvegarde de 10 favoris</li>
            </ul>
        </div>

        <div class="article" id="article3">
            <h2><span class="article-number">Article 3</span> Inscription et Compte Utilisateur</h2>
            
            <h3>3.1 Conditions d'inscription</h3>
            <p>L'inscription sur CultureRadar est gratuite et optionnelle pour consulter les événements publics. La création d'un compte utilisateur est nécessaire pour accéder aux recommandations personnalisées et aux fonctionnalités avancées.</p>
            
            <div class="warning-block">
                <p><strong>Âge minimum requis :</strong> L'utilisateur doit être âgé d'au moins 16 ans. Les utilisateurs mineurs de 16 à 18 ans doivent obtenir le consentement de leurs parents ou tuteurs légaux.</p>
            </div>
            
            <h3>3.2 Informations de compte</h3>
            <p>L'utilisateur s'engage à :</p>
            <ul>
                <li>Fournir des informations exactes, complètes et à jour lors de son inscription</li>
                <li>Maintenir la confidentialité de ses identifiants de connexion</li>
                <li>Ne pas créer plusieurs comptes pour une même personne</li>
                <li>Informer immédiatement CultureRadar de toute utilisation non autorisée</li>
            </ul>
            
            <p>En cas d'utilisation non autorisée de votre compte, contactez immédiatement : <a href="mailto:securite@cultureradar.fr">securite@cultureradar.fr</a></p>
            
            <h3>3.3 Suppression de compte</h3>
            <p>L'utilisateur peut supprimer son compte à tout moment :</p>
            <ul>
                <li>Depuis son espace personnel (Paramètres > Supprimer mon compte)</li>
                <li>En contactant notre support : <a href="mailto:support@cultureradar.fr">support@cultureradar.fr</a></li>
            </ul>
            
            <div class="info-block">
                <p><strong>Note :</strong> La suppression entraîne l'effacement définitif de toutes les données personnelles sous 30 jours, conformément à notre politique de confidentialité.</p>
            </div>
        </div>

        <div class="article" id="article4">
            <h2><span class="article-number">Article 4</span> Utilisation du Service</h2>
            
            <h3>4.1 Usage personnel</h3>
            <p>Le service CultureRadar est destiné à un usage personnel et non commercial. Toute utilisation à des fins commerciales nécessite un accord préalable écrit de CultureRadar.</p>
            
            <h3>4.2 Comportement utilisateur</h3>
            <p>L'utilisateur s'engage à :</p>
            <ul>
                <li>Respecter les droits de propriété intellectuelle</li>
                <li>Ne pas publier de contenu illégal, diffamatoire ou portant atteinte aux droits d'autrui</li>
                <li>Ne pas utiliser de robots, scripts ou autres moyens automatisés pour accéder au service</li>
                <li>Ne pas tenter de contourner les mesures de sécurité mises en place</li>
                <li>Ne pas perturber ou surcharger les serveurs</li>
                <li>Respecter les autres utilisateurs et maintenir un comportement courtois</li>
            </ul>
            
            <h3>4.3 Contenu utilisateur</h3>
            <p>Les avis, commentaires et évaluations publiés par les utilisateurs :</p>
            <ul>
                <li>Relèvent de leur seule responsabilité</li>
                <li>Doivent être authentiques et basés sur une expérience réelle</li>
                <li>Ne doivent pas contenir de propos discriminatoires ou haineux</li>
                <li>Peuvent être modérés ou supprimés par CultureRadar</li>
            </ul>
            
            <div class="warning-block">
                <p><strong>Modération :</strong> CultureRadar se réserve le droit de supprimer tout contenu inapproprié et de suspendre ou bannir les comptes ne respectant pas ces règles.</p>
            </div>
        </div>

        <div class="article" id="article5">
            <h2><span class="article-number">Article 5</span> Abonnement Premium</h2>
            
            <h3>5.1 Services Premium</h3>
            <p>L'abonnement Premium (9,90€/mois) donne accès à :</p>
            
            <div class="info-block">
                <ul style="margin: 0;">
                    <li>Recommandations quotidiennes illimitées</li>
                    <li>Planificateur de sorties avancé avec itinéraires optimisés</li>
                    <li>Accès prioritaire aux réservations partenaires</li>
                    <li>Support client prioritaire 7j/7</li>
                    <li>Statistiques détaillées de vos activités culturelles</li>
                    <li>Mode hors-ligne pour consulter vos événements</li>
                    <li>Suppression de toute publicité</li>
                </ul>
            </div>
            
            <h3>5.2 Facturation et paiement</h3>
            <ul>
                <li>Paiement mensuel par carte bancaire ou PayPal</li>
                <li>Prélèvement automatique à la date anniversaire</li>
                <li>Facture disponible dans l'espace personnel</li>
                <li>Essai gratuit de 7 jours pour les nouveaux utilisateurs</li>
            </ul>
            
            <h3>5.3 Résiliation</h3>
            <p>L'abonnement Premium est <strong>sans engagement</strong> et peut être résilié à tout moment :</p>
            <ul>
                <li>Depuis l'espace personnel (Abonnement > Résilier)</li>
                <li>L'accès Premium reste actif jusqu'à la fin de la période payée</li>
                <li>Aucun remboursement pour la période en cours</li>
            </ul>
        </div>

        <div class="article" id="article6">
            <h2><span class="article-number">Article 6</span> Responsabilité et Garanties</h2>
            
            <h3>6.1 Limitation de responsabilité</h3>
            <p>CultureRadar s'efforce de fournir des informations exactes et à jour sur les événements culturels. Cependant, nous ne pouvons garantir :</p>
            <ul>
                <li>L'exactitude, la complétude ou la disponibilité des informations</li>
                <li>La réalisation effective des événements annoncés</li>
                <li>La qualité des événements recommandés</li>
                <li>L'absence d'erreurs ou d'interruptions du service</li>
            </ul>
            
            <div class="warning-block">
                <p><strong>Exclusion de responsabilité :</strong> CultureRadar ne saurait être tenu responsable des dommages directs ou indirects résultant de l'utilisation du service, incluant notamment les annulations d'événements, modifications d'horaires ou problèmes rencontrés lors de sorties culturelles.</p>
            </div>
            
            <h3>6.2 Disponibilité du service</h3>
            <p>Nous nous efforçons d'assurer une disponibilité du service 24h/24 et 7j/7, avec un objectif de disponibilité de 99,5%. Cependant :</p>
            <ul>
                <li>Des interruptions peuvent survenir pour maintenance</li>
                <li>Les mises à jour sont généralement effectuées en heures creuses</li>
                <li>Les utilisateurs sont prévenus à l'avance des maintenances planifiées</li>
                <li>Aucune compensation n'est due en cas d'indisponibilité</li>
            </ul>
        </div>

        <div class="article" id="article7">
            <h2><span class="article-number">Article 7</span> Propriété Intellectuelle</h2>
            
            <p>Le site CultureRadar, sa structure générale, ses textes, images, sons, savoir-faire et tous autres éléments le composant sont la propriété exclusive de CultureRadar SAS.</p>
            
            <h3>Protection des droits</h3>
            <p>Sont notamment protégés :</p>
            <ul>
                <li>Le logo et l'identité visuelle CultureRadar®</li>
                <li>Les algorithmes de recommandation</li>
                <li>L'interface utilisateur et l'expérience (UX/UI)</li>
                <li>Les contenus éditoriaux et descriptions</li>
                <li>La base de données d'événements</li>
            </ul>
            
            <div class="warning-block">
                <p><strong>Interdiction formelle :</strong> Toute reproduction, représentation, adaptation ou exploitation partielle ou totale est strictement interdite sans autorisation écrite préalable de CultureRadar SAS.</p>
            </div>
            
            <h3>Licence d'utilisation</h3>
            <p>En utilisant CultureRadar, nous vous accordons une licence limitée, non exclusive et révocable pour :</p>
            <ul>
                <li>Accéder et utiliser le service à des fins personnelles</li>
                <li>Partager des liens vers des événements</li>
                <li>Imprimer des informations pour usage personnel</li>
            </ul>
        </div>

        <div class="article" id="article8">
            <h2><span class="article-number">Article 8</span> Modification des CGU</h2>
            
            <p>CultureRadar se réserve le droit de modifier les présentes CGU à tout moment pour :</p>
            <ul>
                <li>S'adapter aux évolutions légales et réglementaires</li>
                <li>Améliorer le service et ajouter de nouvelles fonctionnalités</li>
                <li>Clarifier certaines dispositions</li>
            </ul>
            
            <h3>Notification des modifications</h3>
            <p>Les utilisateurs seront informés des modifications importantes par :</p>
            <ul>
                <li>Email à l'adresse enregistrée</li>
                <li>Notification dans l'application</li>
                <li>Bannière sur le site web</li>
            </ul>
            
            <div class="info-block">
                <p><strong>Acceptation :</strong> L'utilisation continue du service après modification vaut acceptation des nouvelles conditions. En cas de désaccord, l'utilisateur peut résilier son compte.</p>
            </div>
        </div>

        <div class="article" id="article9">
            <h2><span class="article-number">Article 9</span> Droit Applicable et Juridiction</h2>
            
            <p>Les présentes CGU sont soumises au droit français.</p>
            
            <h3>Résolution des litiges</h3>
            <ol>
                <li><strong>Règlement amiable :</strong> En cas de litige, les parties s'efforceront de trouver une solution amiable</li>
                <li><strong>Médiation :</strong> Recours possible à un médiateur de la consommation</li>
                <li><strong>Juridiction :</strong> À défaut d'accord, les tribunaux de Paris seront seuls compétents</li>
            </ol>
            
            <div class="info-block">
                <p><strong>Plateforme de règlement en ligne des litiges :</strong><br>
                <a href="https://ec.europa.eu/consumers/odr/" target="_blank">https://ec.europa.eu/consumers/odr/</a></p>
            </div>
        </div>

        <div style="margin-top: 3rem; padding: 2rem; background: #f0f0ff; border-radius: 10px;">
            <h3>Contact et réclamations</h3>
            <p>Pour toute question concernant ces CGU ou pour signaler un problème :</p>
            <p><strong>Service Client CultureRadar</strong><br>
            Email : <a href="mailto:support@cultureradar.fr">support@cultureradar.fr</a><br>
            Téléphone : +33 (0)1 48 57 89 34<br>
            Courrier : CultureRadar SAS - Service Client<br>
            42 rue de la Culture, 93100 Montreuil</p>
        </div>

        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #e0e0e0; text-align: center;">
            <a href="/" style="color: #667eea; text-decoration: none; font-weight: 500;">← Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>