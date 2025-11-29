<?php
session_start();
require_once __DIR__ . '/config.php';

// Gestion des préférences cookies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferences = [
        'essential' => true, // Toujours activés
        'analytics' => isset($_POST['analytics']) ? true : false,
        'marketing' => isset($_POST['marketing']) ? true : false,
        'social' => isset($_POST['social']) ? true : false
    ];
    
    // Sauvegarder les préférences dans un cookie
    setcookie('cookie_preferences', json_encode($preferences), time() + (365 * 24 * 60 * 60), '/');
    $_SESSION['cookie_preferences_saved'] = true;
    header('Location: /cookies.php?saved=1');
    exit;
}

$saved = isset($_GET['saved']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Cookies - Culture Radar</title>
    <meta name="description" content="Politique de cookies et gestion des consentements - Culture Radar">
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
        .success-block {
            background: #f0fff4;
            border-left: 4px solid #4CAF50;
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
        .cookie-category {
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 2rem;
            margin: 2rem 0;
        }
        .cookie-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .cookie-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .cookie-header i {
            color: #667eea;
            font-size: 1.5rem;
        }
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #667eea;
        }
        input:disabled + .slider {
            background-color: #667eea;
            opacity: 0.6;
            cursor: not-allowed;
        }
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        .cookie-list {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }
        .cookie-item {
            display: grid;
            grid-template-columns: 150px 100px 1fr;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9rem;
        }
        .cookie-item:last-child {
            border-bottom: none;
        }
        .cookie-item strong {
            color: #333;
        }
        .cookie-duration {
            color: #999;
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 2rem;
            transition: transform 0.3s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-left: 1rem;
            transition: all 0.3s;
        }
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="nav-legal">
        <div class="nav-legal-container">
            <a href="/" style="color: #333;"><i class="fas fa-home"></i> Accueil</a>
            <a href="/legal.php">Mentions Légales</a>
            <a href="/terms.php">CGU</a>
            <a href="/privacy.php">Confidentialité</a>
            <a href="/cookies.php" class="active">Cookies</a>
        </div>
    </nav>

    <div class="legal-header">
        <h1><i class="fas fa-cookie-bite"></i> Gestion des Cookies</h1>
        <p>Personnalisez vos préférences de cookies</p>
    </div>

    <div class="legal-container">
        <?php if ($saved): ?>
        <div class="success-block">
            <p><strong>✓ Préférences enregistrées !</strong><br>
            Vos préférences de cookies ont été mises à jour avec succès.</p>
        </div>
        <?php endif; ?>

        <section>
            <h2>Qu'est-ce qu'un cookie ?</h2>
            <p>Un cookie est un petit fichier texte stocké sur votre appareil lorsque vous visitez notre site web. Les cookies nous permettent de :</p>
            <ul>
                <li>Mémoriser vos préférences et paramètres</li>
                <li>Vous maintenir connecté à votre compte</li>
                <li>Analyser l'utilisation du site pour l'améliorer</li>
                <li>Personnaliser votre expérience</li>
            </ul>
        </section>

        <section>
            <h2>Vos Préférences de Cookies</h2>
            
            <form method="POST" action="/cookies.php">
                <!-- Cookies Essentiels -->
                <div class="cookie-category">
                    <div class="cookie-header">
                        <h3><i class="fas fa-lock"></i> Cookies Essentiels</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" checked disabled>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <p>Ces cookies sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés. Ils ne stockent aucune information personnelle identifiable.</p>
                    
                    <div class="cookie-list">
                        <div class="cookie-item">
                            <strong>session_id</strong>
                            <span class="cookie-duration">Session</span>
                            <span>Maintient votre session active pendant la navigation</span>
                        </div>
                        <div class="cookie-item">
                            <strong>csrf_token</strong>
                            <span class="cookie-duration">Session</span>
                            <span>Protection contre les attaques CSRF</span>
                        </div>
                        <div class="cookie-item">
                            <strong>cookie_consent</strong>
                            <span class="cookie-duration">13 mois</span>
                            <span>Mémorise vos préférences de cookies</span>
                        </div>
                    </div>
                </div>

                <!-- Cookies Analytiques -->
                <div class="cookie-category">
                    <div class="cookie-header">
                        <h3><i class="fas fa-chart-bar"></i> Cookies Analytiques</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="analytics" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <p>Ces cookies nous aident à comprendre comment les visiteurs utilisent notre site, nous permettant d'améliorer votre expérience.</p>
                    
                    <div class="cookie-list">
                        <div class="cookie-item">
                            <strong>_ga</strong>
                            <span class="cookie-duration">2 ans</span>
                            <span>Google Analytics - Distingue les utilisateurs uniques</span>
                        </div>
                        <div class="cookie-item">
                            <strong>_gid</strong>
                            <span class="cookie-duration">24 heures</span>
                            <span>Google Analytics - Distingue les utilisateurs</span>
                        </div>
                        <div class="cookie-item">
                            <strong>_hjid</strong>
                            <span class="cookie-duration">1 an</span>
                            <span>Hotjar - Analyse du comportement utilisateur</span>
                        </div>
                    </div>
                </div>

                <!-- Cookies Marketing -->
                <div class="cookie-category">
                    <div class="cookie-header">
                        <h3><i class="fas fa-bullhorn"></i> Cookies Marketing</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="marketing">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <p>Ces cookies sont utilisés pour vous présenter des publicités pertinentes et mesurer l'efficacité de nos campagnes.</p>
                    
                    <div class="cookie-list">
                        <div class="cookie-item">
                            <strong>_fbp</strong>
                            <span class="cookie-duration">3 mois</span>
                            <span>Facebook Pixel - Publicité ciblée</span>
                        </div>
                        <div class="cookie-item">
                            <strong>ads/ga-audiences</strong>
                            <span class="cookie-duration">Session</span>
                            <span>Google Ads - Remarketing</span>
                        </div>
                        <div class="cookie-item">
                            <strong>mc_</strong>
                            <span class="cookie-duration">13 mois</span>
                            <span>Mailchimp - Personnalisation des emails</span>
                        </div>
                    </div>
                </div>

                <!-- Cookies Réseaux Sociaux -->
                <div class="cookie-category">
                    <div class="cookie-header">
                        <h3><i class="fas fa-share-alt"></i> Cookies Réseaux Sociaux</h3>
                        <label class="toggle-switch">
                            <input type="checkbox" name="social">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <p>Ces cookies permettent de partager du contenu sur les réseaux sociaux et d'intégrer des fonctionnalités sociales.</p>
                    
                    <div class="cookie-list">
                        <div class="cookie-item">
                            <strong>c_user</strong>
                            <span class="cookie-duration">90 jours</span>
                            <span>Facebook - Intégration sociale</span>
                        </div>
                        <div class="cookie-item">
                            <strong>guest_id</strong>
                            <span class="cookie-duration">2 ans</span>
                            <span>Twitter - Partage de contenu</span>
                        </div>
                        <div class="cookie-item">
                            <strong>li_at</strong>
                            <span class="cookie-duration">1 an</span>
                            <span>LinkedIn - Connexion sociale</span>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 3rem;">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Enregistrer mes préférences
                    </button>
                    <button type="button" class="btn-secondary" onclick="acceptAll()">
                        Tout accepter
                    </button>
                </div>
            </form>
        </section>

        <section>
            <h2>Gestion des Cookies par Navigateur</h2>
            
            <p>Vous pouvez également gérer les cookies directement depuis votre navigateur :</p>
            
            <div class="info-block">
                <h4>Chrome</h4>
                <p>Paramètres → Confidentialité et sécurité → Cookies et autres données des sites<br>
                <a href="https://support.google.com/chrome/answer/95647" target="_blank">Guide complet Chrome</a></p>
            </div>
            
            <div class="info-block">
                <h4>Firefox</h4>
                <p>Paramètres → Vie privée et sécurité → Cookies et données de sites<br>
                <a href="https://support.mozilla.org/fr/kb/cookies-informations-sites-enregistrent" target="_blank">Guide complet Firefox</a></p>
            </div>
            
            <div class="info-block">
                <h4>Safari</h4>
                <p>Préférences → Confidentialité → Gérer les données de sites web<br>
                <a href="https://support.apple.com/fr-fr/guide/safari/sfri11471/mac" target="_blank">Guide complet Safari</a></p>
            </div>
            
            <div class="info-block">
                <h4>Edge</h4>
                <p>Paramètres → Cookies et autorisations de site → Gérer et supprimer les cookies<br>
                <a href="https://support.microsoft.com/fr-fr/microsoft-edge/supprimer-les-cookies-dans-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank">Guide complet Edge</a></p>
            </div>
        </section>

        <section>
            <h2>Opt-out Publicitaire</h2>
            
            <p>Pour vous opposer au ciblage publicitaire, vous pouvez utiliser ces outils :</p>
            
            <ul>
                <li><strong>Google Ads :</strong> <a href="https://adssettings.google.com" target="_blank">Paramètres des annonces Google</a></li>
                <li><strong>Facebook :</strong> Accédez aux paramètres de confidentialité de votre compte Facebook</li>
                <li><strong>Programme EDAA :</strong> <a href="http://www.youronlinechoices.eu" target="_blank">Your Online Choices</a></li>
                <li><strong>Network Advertising Initiative :</strong> <a href="http://optout.networkadvertising.org" target="_blank">NAI Opt-out</a></li>
            </ul>
        </section>

        <section>
            <h2>Impact sur l'Expérience Utilisateur</h2>
            
            <div class="info-block">
                <p><strong>Note importante :</strong> La désactivation de certains cookies peut affecter votre expérience sur notre site :</p>
                <ul style="margin-top: 1rem;">
                    <li><strong>Sans cookies analytiques :</strong> Nous ne pourrons pas améliorer le site selon vos besoins</li>
                    <li><strong>Sans cookies marketing :</strong> Vous verrez toujours des publicités, mais elles seront moins pertinentes</li>
                    <li><strong>Sans cookies sociaux :</strong> Les fonctions de partage social seront limitées</li>
                </ul>
            </div>
        </section>

        <section>
            <h2>Questions Fréquentes</h2>
            
            <h3>Combien de temps conservez-vous les cookies ?</h3>
            <p>La durée varie selon le type de cookie : de la durée de session (supprimé à la fermeture du navigateur) jusqu'à 13 mois maximum pour certains cookies de préférences.</p>
            
            <h3>Les cookies contiennent-ils mes données personnelles ?</h3>
            <p>Les cookies essentiels ne contiennent pas de données personnelles identifiables. Les cookies analytiques et marketing peuvent contenir un identifiant unique mais pas vos informations personnelles directes.</p>
            
            <h3>Puis-je changer mes préférences plus tard ?</h3>
            <p>Oui, vous pouvez modifier vos préférences à tout moment en revenant sur cette page ou via le lien "Gestion des cookies" en bas de chaque page du site.</p>
        </section>

        <div style="margin-top: 3rem; padding: 2rem; background: #f0f0ff; border-radius: 10px;">
            <h3>Contact</h3>
            <p>Pour toute question concernant notre utilisation des cookies :</p>
            <p>Email : <a href="mailto:cookies@cultureradar.fr">cookies@cultureradar.fr</a><br>
            Téléphone : +33 (0)1 48 57 89 34</p>
        </div>

        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #e0e0e0; text-align: center;">
            <a href="/" style="color: #667eea; text-decoration: none; font-weight: 500;">← Retour à l'accueil</a>
        </div>
    </div>

    <script>
        function acceptAll() {
            document.querySelectorAll('input[type="checkbox"]:not(:disabled)').forEach(checkbox => {
                checkbox.checked = true;
            });
        }
    </script>
</body>
</html>