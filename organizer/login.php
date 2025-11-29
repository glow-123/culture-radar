<?php
session_start();

// Load configuration
require_once __DIR__ . '/../config.php';

// Redirect if already logged in
if (isset($_SESSION['organizer_id'])) {
    header('Location: /organizer/dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        try {
            $dbConfig = Config::database();
            $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check organizer credentials
            $stmt = $pdo->prepare("SELECT * FROM organizers WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $organizer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($organizer && password_verify($password, $organizer['password'])) {
                // Login successful
                $_SESSION['organizer_id'] = $organizer['id'];
                $_SESSION['organizer_name'] = $organizer['name'];
                $_SESSION['organizer_email'] = $organizer['email'];
                $_SESSION['subscription_type'] = $organizer['subscription_type'];
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE organizers SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$organizer['id']]);
                
                header('Location: /organizer/dashboard.php');
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion. Veuillez réessayer.';
            error_log("Organizer login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Organisateur - Culture Radar Pro</title>
    
    <?php include '../includes/favicon.php'; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .organizer-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 3rem;
            max-width: 450px;
            width: 100%;
            box-shadow: var(--shadow-xl);
            position: relative;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 30px 30px 0 0;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .pro-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
        }
        
        .pro-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .login-title {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ffffff 0%, #a855f7 50%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="animated-bg" aria-hidden="true">
        <div class="stars"></div>
        <div class="floating-shapes"></div>
    </div>
    
    <main class="organizer-login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="pro-logo">
                    <i class="fas fa-building"></i>
                    Culture Radar
                    <span class="pro-badge">Pro</span>
                </div>
                <h1 class="login-title">Connexion</h1>
                <p class="login-subtitle">Accédez à votre espace organisateur</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/organizer/login.php">
                <div class="form-group">
                    <label for="email" class="form-label">Adresse email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="votre@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Votre mot de passe"
                        required
                    >
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; margin-bottom: 1.5rem;">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            
            <div class="text-center" style="color: rgba(255, 255, 255, 0.8);">
                Pas encore inscrit ? 
                <a href="/organizer/register.php" style="color: #4facfe; text-decoration: none; font-weight: 600;">
                    Créer un compte organisateur
                </a>
            </div>
            
            <div class="text-center" style="margin-top: 1rem;">
                <a href="/" style="color: rgba(255, 255, 255, 0.6); text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Retour au site
                </a>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>