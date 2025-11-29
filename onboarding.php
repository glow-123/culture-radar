<?php
// Sécurité HTTP headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load configuration
require_once __DIR__ . '/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

$error = '';
$success = '';

// Handle onboarding form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        die('Erreur de vérification de sécurité (CSRF).');
    }
    
    $preferences = $_POST['preferences'] ?? [];
    $location = trim($_POST['location'] ?? '');
    $budget = $_POST['budget'] ?? 'high';
    $notifications = $_POST['notifications'] ?? [];
    
    if (empty($preferences) || count($preferences) < 3) {
        $error = 'Veuillez sélectionner au moins 3 préférences culturelles.';
    } elseif (empty($location)) {
        $error = 'Veuillez indiquer votre localisation.';
    } else {
        try {
            $dbConfig = Config::database();
        $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Update user profile with onboarding data
            $preferencesJson = json_encode($preferences);
            $notificationsJson = json_encode($notifications);
            $budgetMap = [
                'free' => 0,
                'low' => 15,
                'medium' => 30,
                'high' => 999
            ];
            
            $stmt = $pdo->prepare("
                UPDATE user_profiles 
                SET preferences = ?, location = ?, budget_max = ?, notification_settings = ?, onboarding_completed = 1, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $preferencesJson,
                $location,
                $budgetMap[$budget] ?? 999,
                $notificationsJson,
                $_SESSION['user_id']
            ]);
            
            // Mark onboarding as completed
            $stmt = $pdo->prepare("UPDATE users SET onboarding_completed = 1 WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Redirect to dashboard
            header('Location: /dashboard.php?welcome=1');
            exit();
            
        } catch (PDOException $e) {
            $error = 'Erreur lors de la sauvegarde. Veuillez réessayer.';
            error_log("Onboarding error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration - Culture Radar</title>
    
    <?php include 'includes/favicon.php'; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: #0f172a;
            overflow-x: hidden;
            background: #0a0a0f;
        }

        :root {
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --dark: #0f172a;
            --light: #f8fafc;
            --glass: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: radial-gradient(ellipse at center, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            overflow: hidden;
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .floating-element {
            position: absolute;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            animation: float-element 20s infinite ease-in-out;
        }

        @keyframes float-element {
            0%, 100% { transform: translate(0, 0) rotate(0deg); opacity: 0.3; }
            25% { transform: translate(100px, -100px) rotate(90deg); opacity: 0.6; }
            50% { transform: translate(-50px, -200px) rotate(180deg); opacity: 0.2; }
            75% { transform: translate(-100px, -50px) rotate(270deg); opacity: 0.8; }
        }

        /* Main Container */
        .onboarding-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .onboarding-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 0;
            max-width: 800px;
            width: 100%;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .onboarding-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--accent);
        }

        /* Header */
        .onboarding-header {
            padding: 2.5rem 2.5rem 1rem;
            text-align: center;
            position: relative;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            margin-bottom: 2rem;
        }

        .compass-icon {
            width: 50px;
            height: 50px;
            margin-right: 1rem;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            animation: compass-spin 20s linear infinite;
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.4);
        }

        @keyframes compass-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .step-dot.active {
            background: var(--accent);
            box-shadow: 0 0 15px rgba(79, 172, 254, 0.5);
        }

        .step-dot.completed {
            background: var(--success);
        }

        .step-title {
            font-size: 2.2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ffffff 0%, #a855f7 50%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .step-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Content */
        .onboarding-content {
            padding: 1rem 2.5rem 2.5rem;
        }

        .step-content {
            display: none;
            animation: fadeInUp 0.6s ease-out;
        }

        .step-content.active {
            display: block;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Welcome Step */
        .welcome-content {
            text-align: center;
            padding: 2rem 0;
        }

        .welcome-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: welcome-bounce 2s ease-in-out infinite;
        }

        @keyframes welcome-bounce {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .welcome-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .feature-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
        }

        .feature-title {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        /* Preferences Step */
        .preferences-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .preference-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid transparent;
            border-radius: 20px;
            padding: 1.5rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .preference-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: all 0.8s;
        }

        .preference-card:hover::before {
            left: 100%;
        }

        .preference-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .preference-card.selected {
            background: var(--accent);
            border-color: rgba(79, 172, 254, 0.5);
            box-shadow: 0 0 25px rgba(79, 172, 254, 0.4);
        }

        .preference-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            display: block;
        }

        .preference-title {
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
        }

        /* Location Step */
        .location-content {
            text-align: center;
        }

        .location-input-group {
            margin: 2rem 0;
        }

        .location-input {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1rem 1rem 1rem 3rem;
            color: white;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            margin: 0 auto;
        }

        .location-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .location-input:focus {
            outline: none;
            border-color: rgba(79, 172, 254, 0.5);
            box-shadow: 0 0 20px rgba(79, 172, 254, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .location-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.2rem;
        }

        .location-suggestion {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .location-suggestion:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .location-suggestion.selected {
            background: var(--accent);
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 120px;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid var(--glass-border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .btn-skip {
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            border: none;
            text-decoration: underline;
            font-size: 0.9rem;
        }

        .btn-skip:hover {
            color: white;
        }

        /* Progress Bar */
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: var(--accent);
            transition: width 0.5s ease;
            border-radius: 0 0 30px 30px;
        }

        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(245, 87, 108, 0.2);
            border: 1px solid rgba(245, 87, 108, 0.3);
            color: #f5576c;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .onboarding-container {
                padding: 1rem;
            }
            
            .onboarding-card {
                max-width: 100%;
            }
            
            .onboarding-header,
            .onboarding-content {
                padding: 2rem 1.5rem;
            }
            
            .step-title {
                font-size: 1.8rem;
            }
            
            .preferences-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="floating-elements"></div>
    </div>

    <!-- Main Container -->
    <div class="onboarding-container">
        <div class="onboarding-card">
            <div class="onboarding-header">
                <div class="logo">
                    <div class="compass-icon">🧭</div>
                    Culture Radar
                </div>
                
                <div class="step-indicator">
                    <div class="step-dot active"></div>
                    <div class="step-dot"></div>
                    <div class="step-dot"></div>
                    <div class="step-dot"></div>
                    <div class="step-dot"></div>
                </div>
                
                <h1 class="step-title" id="step-title">Bienvenue dans Culture Radar !</h1>
                <p class="step-subtitle" id="step-subtitle">
                    Découvrez une nouvelle façon d'explorer la culture autour de vous
                </p>
            </div>

            <div class="onboarding-content">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/onboarding.php" id="onboarding-form">
                    <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <!-- Step 1: Welcome -->
                    <div class="step-content active" id="step-1">
                        <div class="welcome-content">
                            <div class="welcome-icon">✨</div>
                            <p class="welcome-text">
                                Culture Radar est votre compagnon intelligent pour découvrir les événements 
                                culturels qui vous correspondent vraiment. Grâce à l'intelligence artificielle, 
                                nous analysons vos goûts pour vous proposer des expériences uniques.
                            </p>
                            
                            <div class="feature-grid">
                                <div class="feature-item">
                                    <div class="feature-icon">🎯</div>
                                    <div class="feature-title">Recommandations intelligentes</div>
                                    <div class="feature-description">IA personnalisée</div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">📍</div>
                                    <div class="feature-title">Culture de proximité</div>
                                    <div class="feature-description">Près de chez vous</div>
                                </div>
                                <div class="feature-item">
                                    <div class="feature-icon">💎</div>
                                    <div class="feature-title">Trésors cachés</div>
                                    <div class="feature-description">Lieux secrets</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Preferences -->
                    <div class="step-content" id="step-2">
                        <div class="preferences-grid">
                            <div class="preference-card" data-preference="art">
                                <div class="preference-icon">🎨</div>
                                <div class="preference-title">Art & Expo</div>
                                <input type="checkbox" name="preferences[]" value="art" style="display: none;">
                            </div>
                            <div class="preference-card" data-preference="music">
                                <div class="preference-icon">🎵</div>
                                <div class="preference-title">Musique</div>
                                <input type="checkbox" name="preferences[]" value="music" style="display: none;">
                            </div>
                            <div class="preference-card" data-preference="theater">
                                <div class="preference-icon">🎭</div>
                                <div class="preference-title">Théâtre</div>
                                <input type="checkbox" name="preferences[]" value="theater" style="display: none;">
                            </div>
                            <div class="preference-card" data-preference="cinema">
                                <div class="preference-icon">🎬</div>
                                <div class="preference-title">Cinéma</div>
                                <input type="checkbox" name="preferences[]" value="cinema" style="display: none;">
                            </div>
                            <div class="preference-card" data-preference="literature">
                                <div class="preference-icon">📚</div>
                                <div class="preference-title">Littérature</div>
                                <input type="checkbox" name="preferences[]" value="literature" style="display: none;">
                            </div>
                            <div class="preference-card" data-preference="heritage">
                                <div class="preference-icon">🏛️</div>
                                <div class="preference-title">Patrimoine</div>
                                <input type="checkbox" name="preferences[]" value="heritage" style="display: none;">
                            </div>
                            <div class="preference-card" data-preference="dance">
                                <div class="preference-icon">💃</div>
                                <div class="preference-title">Danse</div>
                                <input type="checkbox" name="preferences[]" value="dance" style="display: none;">
                            </div>
                            <div class="preference-card" data-preference="festival">
                                <div class="preference-icon">🎪</div>
                                <div class="preference-title">Festivals</div>
                                <input type="checkbox" name="preferences[]" value="festival" style="display: none;">
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Location -->
                    <div class="step-content" id="step-3">
                        <div class="location-content">
                            <div class="location-input-group" style="position: relative;">
                                <div class="location-icon">📍</div>
                                <input type="text" name="location" class="location-input" placeholder="Saisissez votre ville ou code postal" required>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Budget -->
                    <div class="step-content" id="step-4">
                        <div class="preferences-grid">
                            <div class="preference-card budget-card" data-budget="free">
                                <div class="preference-icon">🆓</div>
                                <div class="preference-title">Gratuit uniquement</div>
                                <input type="radio" name="budget" value="free" style="display: none;">
                            </div>
                            <div class="preference-card budget-card" data-budget="low">
                                <div class="preference-icon">💰</div>
                                <div class="preference-title">Jusqu'à 15€</div>
                                <input type="radio" name="budget" value="low" style="display: none;">
                            </div>
                            <div class="preference-card budget-card" data-budget="medium">
                                <div class="preference-icon">💳</div>
                                <div class="preference-title">Jusqu'à 30€</div>
                                <input type="radio" name="budget" value="medium" style="display: none;">
                            </div>
                            <div class="preference-card budget-card selected" data-budget="high">
                                <div class="preference-icon">💎</div>
                                <div class="preference-title">Pas de limite</div>
                                <input type="radio" name="budget" value="high" checked style="display: none;">
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Notifications -->
                    <div class="step-content" id="step-5">
                        <div style="display: flex; flex-direction: column; gap: 1.5rem; margin: 2rem 0;">
                            <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h3 style="color: white; font-weight: 600; margin-bottom: 0.5rem;">Recommandations personnalisées</h3>
                                    <p style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">Recevez des suggestions d'événements qui vous correspondent</p>
                                </div>
                                <label style="position: relative; width: 60px; height: 30px; background: var(--accent); border-radius: 15px; cursor: pointer; display: flex; align-items: center;">
                                    <input type="checkbox" name="notifications[]" value="recommendations" checked style="display: none;">
                                    <div style="position: absolute; top: 3px; right: 3px; width: 24px; height: 24px; background: white; border-radius: 50%; transition: all 0.3s ease;"></div>
                                </label>
                            </div>
                            
                            <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h3 style="color: white; font-weight: 600; margin-bottom: 0.5rem;">Newsletter hebdomadaire</h3>
                                    <p style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem;">Une sélection culturelle chaque semaine dans votre boîte mail</p>
                                </div>
                                <label style="position: relative; width: 60px; height: 30px; background: var(--accent); border-radius: 15px; cursor: pointer; display: flex; align-items: center;">
                                    <input type="checkbox" name="notifications[]" value="newsletter" checked style="display: none;">
                                    <div style="position: absolute; top: 3px; right: 3px; width: 24px; height: 24px; background: white; border-radius: 50%; transition: all 0.3s ease;"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" id="prev-btn" style="display: none;">← Précédent</button>
                        <button type="button" class="btn btn-primary" id="next-btn">Commencer →</button>
                        <button type="button" class="btn-skip" id="skip-btn" style="display: none;">Passer cette étape</button>
                    </div>
                </form>
            </div>

            <div class="progress-bar" id="progress-bar" style="width: 20%;"></div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 5;
        const selectedPreferences = new Set();
        let selectedLocation = '';
        let selectedBudget = 'high';

        const stepTitles = {
            1: "Bienvenue dans Culture Radar !",
            2: "Quels types d'événements vous intéressent ?",
            3: "Où souhaitez-vous découvrir la culture ?",
            4: "Quel est votre budget préféré ?",
            5: "Comment souhaitez-vous être informé ?"
        };

        const stepSubtitles = {
            1: "Découvrez une nouvelle façon d'explorer la culture autour de vous",
            2: "Sélectionnez vos domaines culturels favoris (minimum 3)",
            3: "Nous utiliserons cette information pour vous proposer des événements à proximité",
            4: "Cela nous aide à filtrer les recommandations selon vos moyens",
            5: "Personnalisez vos notifications pour ne rien manquer"
        };

        // Create floating elements
        function createFloatingElements() {
            const container = document.querySelector('.floating-elements');
            const elementCount = 15;
            
            for (let i = 0; i < elementCount; i++) {
                const element = document.createElement('div');
                element.className = 'floating-element';
                element.style.left = Math.random() * 100 + '%';
                element.style.top = Math.random() * 100 + '%';
                element.style.width = (Math.random() * 60 + 20) + 'px';
                element.style.height = (Math.random() * 60 + 20) + 'px';
                element.style.animationDelay = Math.random() * 20 + 's';
                element.style.animationDuration = (Math.random() * 10 + 15) + 's';
                container.appendChild(element);
            }
        }

        // Update step indicators
        function updateStepIndicators() {
            const dots = document.querySelectorAll('.step-dot');
            dots.forEach((dot, index) => {
                dot.classList.remove('active', 'completed');
                if (index + 1 === currentStep) {
                    dot.classList.add('active');
                } else if (index + 1 < currentStep) {
                    dot.classList.add('completed');
                }
            });
        }

        // Update progress bar
        function updateProgressBar() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';
        }

        // Update content
        function updateContent() {
            // Hide all steps
            document.querySelectorAll('.step-content').forEach(step => {
                step.classList.remove('active');
            });
            
            // Show current step
            document.getElementById(`step-${currentStep}`).classList.add('active');
            
            // Update titles
            document.getElementById('step-title').textContent = stepTitles[currentStep];
            document.getElementById('step-subtitle').textContent = stepSubtitles[currentStep];
            
            // Update buttons
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const skipBtn = document.getElementById('skip-btn');
            
            if (currentStep === 1) {
                prevBtn.style.display = 'none';
                nextBtn.textContent = 'Commencer →';
                skipBtn.style.display = 'none';
            } else if (currentStep === totalSteps) {
                prevBtn.style.display = 'inline-flex';
                nextBtn.textContent = '🚀 Découvrir Culture Radar';
                skipBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'inline-flex';
                nextBtn.textContent = 'Suivant →';
                skipBtn.style.display = 'block';
            }
            
            // Update next button state
            updateNextButtonState();
            
            updateStepIndicators();
            updateProgressBar();
        }

        // Update next button state
        function updateNextButtonState() {
            const nextBtn = document.getElementById('next-btn');
            
            if (currentStep === 2) {
                nextBtn.disabled = selectedPreferences.size < 3;
            } else if (currentStep === 3) {
                const locationInput = document.querySelector('input[name="location"]');
                nextBtn.disabled = !locationInput.value.trim();
            } else {
                nextBtn.disabled = false;
            }
        }

        // Preference selection
        document.querySelectorAll('[data-preference]').forEach(card => {
            card.addEventListener('click', () => {
                const preference = card.dataset.preference;
                const checkbox = card.querySelector('input[type="checkbox"]');
                
                if (card.classList.contains('selected')) {
                    card.classList.remove('selected');
                    selectedPreferences.delete(preference);
                    checkbox.checked = false;
                } else {
                    card.classList.add('selected');
                    selectedPreferences.add(preference);
                    checkbox.checked = true;
                }
                
                updateNextButtonState();
            });
        });

        // Budget selection
        document.querySelectorAll('[data-budget]').forEach(card => {
            card.addEventListener('click', () => {
                const budget = card.dataset.budget;
                const radio = card.querySelector('input[type="radio"]');
                
                document.querySelectorAll('[data-budget]').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                selectedBudget = budget;
                radio.checked = true;
            });
        });

        // Location input
        document.querySelector('input[name="location"]').addEventListener('input', (e) => {
            selectedLocation = e.target.value.trim();
            updateNextButtonState();
        });

        // Navigation
        document.getElementById('next-btn').addEventListener('click', () => {
            if (currentStep < totalSteps) {
                currentStep++;
                updateContent();
            } else {
                // Submit form
                document.getElementById('onboarding-form').submit();
            }
        });

        document.getElementById('prev-btn').addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateContent();
            }
        });

        document.getElementById('skip-btn').addEventListener('click', () => {
            if (currentStep < totalSteps) {
                currentStep++;
                updateContent();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            createFloatingElements();
            updateContent();
        });
    </script>
</body>
</html>