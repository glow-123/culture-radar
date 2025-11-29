<?php
session_start();
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité - Culture Radar</title>
    <meta name="description" content="Politique de Confidentialité et Protection des Données de Culture Radar - RGPD, données personnelles, droits des utilisateurs">
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
        .warning-block {
            background: #fff8f0;
            border-left: 4px solid #ff9800;
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
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }
        .data-table th {
            background: #f0f0ff;
            color: #667eea;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }
        .rights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .right-card {
            background: #fafafa;
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        .right-card h4 {
            color: #667eea;
            margin-top: 0;
            margin-bottom: 1rem;
        }
        .right-card p {
            margin: 0;
            font-size: 0.95rem;
        }
        .icon-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .icon-title i {
            color: #667eea;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="nav-legal">
        <div class="nav-legal-container">
            <a href="/" style="color: #333;"><i class="fas fa-home"></i> Accueil</a>
            <a href="/legal.php">Mentions Légales</a>
            <a href="/terms.php">CGU</a>
            <a href="/privacy.php" class="active">Confidentialité</a>
            <a href="/cookies.php">Cookies</a>
        </div>
    </nav>

    <div class="legal-header">
        <h1>Politique de Confidentialité</h1>
        <p>Protection de vos données personnelles - RGPD</p>
        <p style="font-size: 0.9rem; opacity: 0.9;">Dernière mise à jour : Août 2024</p>
    </div>

    <div class="legal-container">
        <div class="success-block">
            <div class="icon-title">
                <i class="fas fa-shield-alt"></i>
                <h3 style="margin: 0;">Notre Engagement</h3>
            </div>
            <p style="margin: 0;">CultureRadar s'engage à protéger la vie privée de ses utilisateurs et à assurer la sécurité de leurs données personnelles. Cette politique de confidentialité explique comment nous collectons, utilisons, stockons et protégeons vos informations personnelles conformément au Règlement Général sur la Protection des Données (RGPD).</p>
        </div>

        <section>
            <h2>1. Types de Données Collectées</h2>
            
            <h3>1.1 Données d'identification</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type de donnée</th>
                        <th>Finalité</th>
                        <th>Base légale</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nom, prénom</td>
                        <td>Création et gestion du compte</td>
                        <td>Exécution du contrat</td>
                    </tr>
                    <tr>
                        <td>Adresse email</td>
                        <td>Connexion, communications</td>
                        <td>Exécution du contrat</td>
                    </tr>
                    <tr>
                        <td>Date de naissance</td>
                        <td>Vérification de l'âge, personnalisation</td>
                        <td>Obligation légale</td>
                    </tr>
                    <tr>
                        <td>Préférences culturelles</td>
                        <td>Recommandations personnalisées</td>
                        <td>Intérêt légitime</td>
                    </tr>
                </tbody>
            </table>
            
            <h3>1.2 Données de navigation</h3>
            <ul>
                <li><strong>Données techniques :</strong> adresse IP, type de navigateur, système d'exploitation</li>
                <li><strong>Cookies et traceurs :</strong> préférences de navigation, sessions utilisateur</li>
                <li><strong>Données d'usage :</strong> pages consultées, événements favoris, historique de navigation</li>
            </ul>
            
            <h3>1.3 Données comportementales</h3>
            <ul>
                <li><strong>Interactions utilisateur :</strong> clics, durée de consultation, événements sauvegardés</li>
                <li><strong>Évaluations et avis :</strong> commentaires sur événements, notes attribuées</li>
                <li><strong>Historique culturel :</strong> événements consultés, réservations effectuées</li>
            </ul>
        </section>

        <section>
            <h2>2. Finalités du Traitement</h2>
            
            <div class="info-block">
                <h3><i class="fas fa-magic"></i> Personnalisation du service</h3>
                <p>Vos données permettent de générer des recommandations culturelles adaptées à vos goûts, contraintes géographiques et disponibilités temporelles via notre algorithme d'intelligence artificielle.</p>
            </div>
            
            <div class="info-block">
                <h3><i class="fas fa-chart-line"></i> Amélioration du service</h3>
                <p>L'analyse anonymisée des comportements utilisateurs nous aide à optimiser notre algorithme de recommandation et à développer de nouvelles fonctionnalités.</p>
            </div>
            
            <div class="info-block">
                <h3><i class="fas fa-envelope"></i> Communication</h3>
                <p>Avec votre consentement explicite, nous utilisons vos coordonnées pour vous envoyer nos newsletters, recommandations personnalisées et informations sur notre service.</p>
            </div>
        </section>

        <section>
            <h2>3. Base Légale des Traitements</h2>
            
            <p>Nous traitons vos données personnelles uniquement sur les bases légales suivantes :</p>
            
            <ul>
                <li><strong>Exécution du contrat :</strong> traitement nécessaire à la fourniture du service de recommandations</li>
                <li><strong>Consentement :</strong> pour les newsletters, notifications push et analyses comportementales avancées</li>
                <li><strong>Intérêt légitime :</strong> pour l'amélioration du service et la sécurité de la plateforme</li>
                <li><strong>Obligation légale :</strong> pour la conservation de certaines données comptables et fiscales</li>
            </ul>
        </section>

        <section>
            <h2>4. Partage des Données</h2>
            
            <div class="success-block">
                <h3><i class="fas fa-lock"></i> Non-commercialisation</h3>
                <p>CultureRadar ne vend, ne loue ni ne commercialise vos données personnelles à des tiers à des fins marketing.</p>
            </div>
            
            <h3>4.1 Partenaires techniques</h3>
            <p>Nous partageons certaines données avec nos prestataires techniques dans le strict cadre de la fourniture du service :</p>
            <ul>
                <li><strong>Hébergement :</strong> OVHcloud / Railway (infrastructure)</li>
                <li><strong>Analytics :</strong> Google Analytics (statistiques anonymisées)</li>
                <li><strong>Paiement :</strong> Stripe (transactions sécurisées)</li>
                <li><strong>Email :</strong> SendGrid (envoi de notifications)</li>
            </ul>
            
            <p>Tous nos prestataires sont soumis à des contrats de confidentialité stricts.</p>
            
            <h3>4.2 Partenaires culturels</h3>
            <p>Avec votre consentement, nous pouvons partager des statistiques anonymisées avec nos partenaires culturels pour les aider à mieux connaître leur audience.</p>
        </section>

        <section>
            <h2>5. Conservation des Données</h2>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Type de données</th>
                        <th>Durée de conservation</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Données de compte actif</td>
                        <td>Durée de vie du compte</td>
                    </tr>
                    <tr>
                        <td>Données de compte supprimé</td>
                        <td>Effacement sous 30 jours maximum</td>
                    </tr>
                    <tr>
                        <td>Données analytiques</td>
                        <td>Anonymisation après 12 mois</td>
                    </tr>
                    <tr>
                        <td>Logs de sécurité</td>
                        <td>12 mois</td>
                    </tr>
                    <tr>
                        <td>Données comptables</td>
                        <td>10 ans (obligation légale)</td>
                    </tr>
                    <tr>
                        <td>Cookies</td>
                        <td>13 mois maximum</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2>6. Vos Droits RGPD</h2>
            
            <p>Conformément au RGPD, vous disposez de droits sur vos données personnelles :</p>
            
            <div class="rights-grid">
                <div class="right-card">
                    <h4><i class="fas fa-eye"></i> Droit d'accès</h4>
                    <p>Demander l'accès à toutes les données personnelles que nous détenons vous concernant.</p>
                </div>
                
                <div class="right-card">
                    <h4><i class="fas fa-edit"></i> Droit de rectification</h4>
                    <p>Corriger ou mettre à jour vos informations personnelles à tout moment.</p>
                </div>
                
                <div class="right-card">
                    <h4><i class="fas fa-trash"></i> Droit à l'effacement</h4>
                    <p>Demander la suppression de vos données personnelles (droit à l'oubli).</p>
                </div>
                
                <div class="right-card">
                    <h4><i class="fas fa-download"></i> Droit à la portabilité</h4>
                    <p>Récupérer vos données dans un format structuré et lisible par machine.</p>
                </div>
                
                <div class="right-card">
                    <h4><i class="fas fa-hand-paper"></i> Droit d'opposition</h4>
                    <p>Vous opposer au traitement de vos données pour des raisons particulières.</p>
                </div>
                
                <div class="right-card">
                    <h4><i class="fas fa-pause"></i> Droit de limitation</h4>
                    <p>Demander la limitation du traitement dans certains cas.</p>
                </div>
            </div>
            
            <h3>Exercice de vos droits</h3>
            <p>Pour exercer vos droits, contactez notre Délégué à la Protection des Données :</p>
            
            <div class="info-block">
                <p><strong>DPO - CultureRadar</strong><br>
                <i class="fas fa-envelope"></i> Email : <a href="mailto:dpo@cultureradar.fr">dpo@cultureradar.fr</a><br>
                <i class="fas fa-map-marker-alt"></i> Courrier : CultureRadar SAS - DPO<br>
                42 rue de la Culture, 93100 Montreuil</p>
                
                <p style="margin-top: 1rem;"><strong>Délai de réponse :</strong> 30 jours maximum</p>
            </div>
            
            <div class="warning-block">
                <p><strong>Réclamation auprès de la CNIL :</strong><br>
                En cas de réponse insatisfaisante, vous pouvez saisir la Commission Nationale de l'Informatique et des Libertés :<br>
                <a href="https://www.cnil.fr" target="_blank">www.cnil.fr</a></p>
            </div>
        </section>

        <section>
            <h2>7. Sécurité des Données</h2>
            
            <h3>7.1 Mesures techniques</h3>
            <ul>
                <li><strong>Chiffrement :</strong> SSL/TLS pour les transmissions, AES-256 pour le stockage</li>
                <li><strong>Accès restreint :</strong> authentification forte, gestion des habilitations</li>
                <li><strong>Surveillance :</strong> monitoring sécuritaire 24/7, détection d'intrusion</li>
                <li><strong>Sauvegardes :</strong> backups réguliers et chiffrés</li>
                <li><strong>Tests de sécurité :</strong> audits et tests de pénétration réguliers</li>
            </ul>
            
            <h3>7.2 Mesures organisationnelles</h3>
            <ul>
                <li><strong>Formation équipe :</strong> sensibilisation RGPD de tous les collaborateurs</li>
                <li><strong>Audit régulier :</strong> contrôles de sécurité trimestriels</li>
                <li><strong>Plan de continuité :</strong> procédures de sauvegarde et de récupération</li>
                <li><strong>Gestion des incidents :</strong> procédure de notification sous 72h</li>
                <li><strong>Privacy by Design :</strong> protection des données dès la conception</li>
            </ul>
        </section>

        <section>
            <h2>8. Transferts Internationaux</h2>
            
            <p>Vos données sont stockées principalement dans l'Union Européenne. En cas de transfert hors UE :</p>
            <ul>
                <li>Nous utilisons des clauses contractuelles types approuvées par la Commission Européenne</li>
                <li>Nous vérifions l'existence de décisions d'adéquation</li>
                <li>Nous appliquons des mesures de sécurité supplémentaires</li>
            </ul>
        </section>

        <section>
            <h2>9. Données des Mineurs</h2>
            
            <div class="warning-block">
                <p><strong>Protection des mineurs :</strong><br>
                CultureRadar n'est pas destiné aux enfants de moins de 16 ans. Nous ne collectons pas sciemment de données personnelles d'enfants de moins de 16 ans. Si vous êtes parent et pensez que votre enfant nous a fourni des données, contactez-nous immédiatement.</p>
            </div>
        </section>

        <section>
            <h2>10. Modifications de la Politique</h2>
            
            <p>Cette politique de confidentialité peut être mise à jour pour refléter :</p>
            <ul>
                <li>Les changements dans nos pratiques de traitement des données</li>
                <li>Les évolutions réglementaires</li>
                <li>Les nouvelles fonctionnalités du service</li>
            </ul>
            
            <p>Vous serez informé de toute modification substantielle par email et/ou notification sur la plateforme.</p>
        </section>

        <div style="margin-top: 3rem; padding: 2rem; background: #f0f0ff; border-radius: 10px;">
            <h3>Contact Protection des Données</h3>
            
            <p>Pour toute question concernant cette politique ou vos données personnelles :</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
                <div>
                    <h4>Délégué à la Protection des Données</h4>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:dpo@cultureradar.fr">dpo@cultureradar.fr</a><br>
                    <i class="fas fa-phone"></i> +33 (0)1 48 57 89 34</p>
                </div>
                
                <div>
                    <h4>Service Client</h4>
                    <p><i class="fas fa-envelope"></i> <a href="mailto:support@cultureradar.fr">support@cultureradar.fr</a><br>
                    <i class="fas fa-map-marker-alt"></i> 42 rue de la Culture, 93100 Montreuil</p>
                </div>
            </div>
        </div>

        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #e0e0e0; text-align: center;">
            <a href="/" style="color: #667eea; text-decoration: none; font-weight: 500;">← Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>