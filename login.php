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

// Initialize login attempts counter
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// Reset attempts after 15 minutes
if (isset($_SESSION['last_attempt_time']) && (time() - $_SESSION['last_attempt_time']) > 900) {
    $_SESSION['login_attempts'] = 0;
}

// Load configuration
require_once __DIR__ . '/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limit
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();
    
    if ($_SESSION['login_attempts'] > 5) {
        die('Trop de tentatives, réessayez dans quelques minutes.');
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        die('Erreur de vérification de sécurité (CSRF).');
    }
    
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
            
            // Check user credentials
            $stmt = $pdo->prepare("SELECT id, name, email, password, created_at FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Reset login attempts on successful login
                $_SESSION['login_attempts'] = 0;
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Redirect to dashboard or intended page
                $redirect = $_GET['redirect'] ?? '/dashboard.php';
                header('Location: ' . $redirect);
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion. Veuillez réessayer.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO & Meta -->
    <title>Connexion - Culture Radar | Votre boussole culturelle intelligente</title>
    <meta name="description" content="Connectez-vous à Culture Radar pour accéder à vos recommandations culturelles personnalisées et découvrir les événements qui vous correspondent.">
    <meta name="robots" content="noindex, nofollow">
    
    <?php include 'includes/favicon.php'; ?>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Login specific styles */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
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
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent);
            border-radius: 30px 30px 0 0;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        
        .login-logo .compass-icon {
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1rem;
            color: white;
            font-size: 1rem;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-input:focus {
            outline: none;
            border-color: rgba(79, 172, 254, 0.5);
            box-shadow: 0 0 20px rgba(79, 172, 254, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            font-size: 1.1rem;
            transition: var(--transition);
        }
        
        .password-toggle:hover {
            color: white;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox {
            width: 18px;
            height: 18px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 4px;
            cursor: pointer;
        }
        
        .checkbox:checked {
            background: var(--accent);
            border-color: var(--accent);
        }
        
        .forgot-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .forgot-link:hover {
            color: white;
        }
        
        .login-button {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }
        
        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .login-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--glass-border);
        }
        
        .divider span {
            background: rgba(15, 23, 42, 0.9);
            padding: 0 1rem;
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .social-button {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.75rem;
            color: white;
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .social-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .signup-link {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .signup-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .signup-link a:hover {
            color: #4facfe;
        }
        
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
        
        .alert-success {
            background: rgba(56, 239, 125, 0.2);
            border: 1px solid rgba(56, 239, 125, 0.3);
            color: #38ef7d;
        }
        
        .back-link {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }
        
        .back-link:hover {
            color: white;
            transform: translateX(-5px);
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-card {
                padding: 2rem;
            }
            
            .back-link {
                position: static;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Skip to content for accessibility -->
    <a href="#main-content" class="skip-to-content">Aller au contenu principal</a>
    
    <!-- Animated Background -->
    <div class="animated-bg" aria-hidden="true">
        <div class="stars"></div>
        <div class="floating-shapes"></div>
    </div>
    
    <!-- Back to home link -->
    <a href="/" class="back-link">
        <i class="fas fa-arrow-left"></i>
        Retour à l'accueil
    </a>
    
    <!-- Main Content -->
    <main id="main-content" class="login-container">
        <div class="login-card">
            <div class="login-header">
                <a href="/" class="login-logo">
                    <div class="compass-icon">🧭</div>
                    Culture Radar
                </a>
                
                <h1 class="login-title">Bon retour !</h1>
                <p class="login-subtitle">Connectez-vous pour découvrir vos recommandations culturelles</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login.php" class="login-form">
                <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf_token']; ?>">
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
                        aria-describedby="email-error"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="password-input-group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Votre mot de passe"
                            required
                            aria-describedby="password-error"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" class="checkbox">
                        <span>Se souvenir de moi</span>
                    </label>
                    
                    <a href="/forgot-password.php" class="forgot-link">Mot de passe oublié ?</a>
                </div>
                
                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            
            <div class="divider">
                <span>ou</span>
            </div>
            
            <div class="social-login">
                <a href="/auth/google.php" class="social-button">
                    <i class="fab fa-google"></i>
                    Google
                </a>
                <a href="/auth/facebook.php" class="social-button">
                    <i class="fab fa-facebook"></i>
                    Facebook
                </a>
            </div>
            
            <div class="signup-link">
                Pas encore de compte ? 
                <a href="/register.php">Créer un compte</a>
            </div>
        </div>
    </main>
    
    <!-- Scripts -->
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Form validation
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                showError('Veuillez remplir tous les champs.');
                return;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Veuillez saisir une adresse email valide.');
                return;
            }
            
            // Show loading state
            const submitBtn = e.target.querySelector('.login-button');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';
            submitBtn.disabled = true;
        });
        
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        function showError(message) {
            // Remove existing alerts
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = 'alert alert-error';
            alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            
            // Insert before form
            const form = document.querySelector('.login-form');
            form.parentNode.insertBefore(alert, form);
            
            // Scroll to alert
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });
        
        // Handle enter key in password field
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.login-form').dispatchEvent(new Event('submit'));
            }
        });
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>