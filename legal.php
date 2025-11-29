<?php
session_start();
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions Légales - Culture Radar</title>
    <meta name="description" content="Mentions légales de Culture Radar - Informations légales, éditeur, hébergeur et propriété intellectuelle">
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
        .contact-info {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }
        .contact-info strong {
            color: #333;
            font-weight: 600;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        ul {
            padding-left: 2rem;
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
    </style>
</head>
<body>
    <nav class="nav-legal">
        <div class="nav-legal-container">
            <a href="/" style="color: #333;"><i class="fas fa-home"></i> Accueil</a>
            <a href="/legal.php" class="active">Mentions Légales</a>
            <a href="/terms.php">CGU</a>
            <a href="/privacy.php">Confidentialité</a>
            <a href="/cookies.php">Cookies</a>
        </div>
    </nav>

    <div class="legal-header">
        <h1>Mentions Légales</h1>
        <p>Dernière mise à jour : Août 2024</p>
    </div>

    <div class="legal-container">
        <section>
            <h2>1. Informations Éditeur</h2>
            
            <div class="info-block">
                <h3>CultureRadar SAS</h3>
                <div class="contact-info">
                    <strong>Forme juridique :</strong> <span>Société par Actions Simplifiée</span>
                    <strong>Capital social :</strong> <span>50 000 euros</span>
                    <strong>Siège social :</strong> <span>42 rue de la Culture, 93100 Montreuil</span>
                    <strong>RCS :</strong> <span>Bobigny 924 856 317</span>
                    <strong>SIREN :</strong> <span>924 856 317</span>
                    <strong>SIRET :</strong> <span>924 856 317 00012</span>
                    <strong>Code APE :</strong> <span>6201Z - Programmation informatique</span>
                    <strong>TVA Intracommunautaire :</strong> <span>FR 35 924856317</span>
                </div>
            </div>

            <h3>Direction et Contact</h3>
            <div class="contact-info">
                <strong>Directrice de publication :</strong> <span>Isabelle LEMOINE</span>
                <strong>Responsable rédactionnel :</strong> <span>Équipe éditoriale CultureRadar</span>
                <strong>Email :</strong> <span><a href="mailto:contact@cultureradar.fr">contact@cultureradar.fr</a></span>
                <strong>Téléphone :</strong> <span>+33 (0)1 48 57 89 34</span>
            </div>
        </section>

        <section>
            <h2>2. Hébergement</h2>
            
            <div class="info-block">
                <h3>OVHcloud</h3>
                <div class="contact-info">
                    <strong>Forme juridique :</strong> <span>Société par Actions Simplifiée</span>
                    <strong>Capital social :</strong> <span>10 069 020 euros</span>
                    <strong>Siège social :</strong> <span>2 rue Kellermann, 59100 Roubaix, France</span>
                    <strong>RCS :</strong> <span>Lille Métropole 424 761 419 00045</span>
                </div>
            </div>

            <p><em>Note : Pour le déploiement actuel, le site est hébergé sur Railway.app, une plateforme cloud internationale.</em></p>
        </section>

        <section>
            <h2>3. Propriété Intellectuelle</h2>
            
            <p>L'ensemble de ce site relève de la législation française et internationale sur le droit d'auteur et la propriété intellectuelle. Tous les droits de reproduction sont réservés, y compris pour les documents téléchargeables et les représentations iconographiques et photographiques.</p>
            
            <p>Les marques citées sur ce site sont déposées par les sociétés qui en sont propriétaires.</p>
            
            <p>La reproduction de tout ou partie de ce site sur un support électronique quel qu'il soit est formellement interdite sauf autorisation expresse du directeur de publication.</p>

            <h3>Protection des créations</h3>
            <ul>
                <li>Logo et identité visuelle Culture Radar</li>
                <li>Algorithmes de recommandation</li>
                <li>Interface utilisateur et expérience</li>
                <li>Contenus éditoriaux originaux</li>
                <li>Base de données d'événements culturels</li>
            </ul>
        </section>

        <section>
            <h2>4. Crédits</h2>
            
            <div class="info-block">
                <h3>Conception et Réalisation</h3>
                <p><strong>Agence :</strong> InnovaDigital Agency</p>
                
                <div class="contact-info" style="margin-top: 1.5rem;">
                    <strong>Design graphique :</strong> <span>Gloria Henri (InnovaDigital Agency)</span>
                    <strong>Développement technique :</strong> <span>Safiatou Diallo (InnovaDigital Agency)</span>
                    <strong>Stratégie marketing :</strong> <span>Hidaya Djaba (InnovaDigital Agency)</span>
                    <strong>Gestion de projet :</strong> <span>Manouk Mansouri (InnovaDigital Agency)</span>
                </div>
            </div>

            <h3>Technologies utilisées</h3>
            <ul>
                <li>Framework PHP pour le backend</li>
                <li>HTML5, CSS3, JavaScript pour le frontend</li>
                <li>Intelligence artificielle pour les recommandations</li>
                <li>APIs tierces pour les données culturelles</li>
            </ul>

            <h3>Sources de données</h3>
            <ul>
                <li>OpenAgenda - Agenda culturel collaboratif</li>
                <li>Paris Open Data - Données publiques de la Ville de Paris</li>
                <li>Google Events - Événements via SerpAPI</li>
                <li>Données météo - OpenWeatherMap</li>
            </ul>
        </section>

        <section>
            <h2>5. Responsabilité</h2>
            
            <p>CultureRadar s'efforce de fournir des informations exactes et mises à jour. Cependant, nous ne pouvons être tenus responsables :</p>
            <ul>
                <li>Des erreurs ou omissions dans les informations publiées</li>
                <li>Des modifications ou annulations d'événements par les organisateurs</li>
                <li>Des dommages directs ou indirects résultant de l'utilisation du site</li>
                <li>De l'indisponibilité temporaire du service</li>
            </ul>
        </section>

        <section>
            <h2>6. Protection des Données</h2>
            
            <p>Conformément au Règlement Général sur la Protection des Données (RGPD), vous disposez de droits sur vos données personnelles :</p>
            <ul>
                <li>Droit d'accès et de rectification</li>
                <li>Droit à l'effacement</li>
                <li>Droit à la portabilité</li>
                <li>Droit d'opposition</li>
            </ul>
            
            <div class="info-block">
                <p><strong>Délégué à la Protection des Données (DPO) :</strong></p>
                <p>Email : <a href="mailto:dpo@cultureradar.fr">dpo@cultureradar.fr</a><br>
                Courrier : CultureRadar SAS - DPO<br>
                42 rue de la Culture, 93100 Montreuil</p>
            </div>
            
            <p>Pour plus d'informations, consultez notre <a href="/privacy.php">Politique de Confidentialité</a>.</p>
        </section>

        <section>
            <h2>7. Contact</h2>
            
            <p>Pour toute question concernant ces mentions légales ou notre service :</p>
            
            <div class="info-block">
                <p><strong>CultureRadar SAS</strong><br>
                42 rue de la Culture<br>
                93100 Montreuil<br>
                France</p>
                
                <p>Email : <a href="mailto:contact@cultureradar.fr">contact@cultureradar.fr</a><br>
                Téléphone : +33 (0)1 48 57 89 34</p>
            </div>
        </section>

        <section>
            <h2>8. Médiation</h2>
            
            <p>En cas de litige, vous pouvez recourir gratuitement au service de médiation :</p>
            
            <div class="info-block">
                <p><strong>Plateforme européenne de règlement en ligne des litiges :</strong><br>
                <a href="https://ec.europa.eu/consumers/odr/" target="_blank">https://ec.europa.eu/consumers/odr/</a></p>
                
                <p><strong>Commission de la médiation de la consommation :</strong><br>
                <a href="https://www.economie.gouv.fr/mediation-conso" target="_blank">https://www.economie.gouv.fr/mediation-conso</a></p>
            </div>
        </section>

        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #e0e0e0; text-align: center;">
            <a href="/" class="back-link">← Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>