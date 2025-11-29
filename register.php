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

// Initialize register attempts counter
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 0;
}

// Reset attempts after 15 minutes
if (isset($_SESSION['last_register_attempt']) && (time() - $_SESSION['last_register_attempt']) > 900) {
    $_SESSION['register_attempts'] = 0;
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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limit
    $_SESSION['register_attempts']++;
    $_SESSION['last_register_attempt'] = time();
    
    if ($_SESSION['register_attempts'] > 5) {
        die('Trop de tentatives, réessayez dans quelques minutes.');
    }
    
    // Verify CSRF token
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        die('Erreur de vérification de sécurité (CSRF).');
    }
    
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $acceptTerms = isset($_POST['accept_terms']);
    $acceptNewsletter = isset($_POST['accept_newsletter']);
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $error = 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (!$acceptTerms) {
        $error = 'Vous devez accepter les conditions d\'utilisation.';
    } else {
        try {
            $dbConfig = Config::database();
            $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                // Create user account
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, accepts_newsletter, is_active, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$name, $email, $hashedPassword, $acceptNewsletter ? 1 : 0]);
                
                $userId = $pdo->lastInsertId();
                
                // Create user profile with default preferences
                $stmt = $pdo->prepare("
                    INSERT INTO user_profiles (user_id, preferences, location, budget_max, created_at) 
                    VALUES (?, '{}', '', 0, NOW())
                ");
                $stmt->execute([$userId]);
                
                // Log the user in
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Reset register attempts on successful registration
                $_SESSION['register_attempts'] = 0;
                
                // Redirect to onboarding
                header('Location: /onboarding.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la création du compte. Veuillez réessayer.';
            error_log("Registration error: " . $e->getMessage());
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
    <title>Créer un compte - Culture Radar | Votre boussole culturelle intelligente</title>
    <meta name="description" content="Créez votre compte Culture Radar et découvrez les événements culturels personnalisés qui vous correspondent. Inscription gratuite et sécurisée.">
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
        /* Registration specific styles */
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }
        
        .register-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 3rem;
            max-width: 500px;
            width: 100%;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent);
            border-radius: 30px 30px 0 0;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        
        .register-logo .compass-icon {
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
        
        .register-title {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ffffff 0%, #a855f7 50%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.half {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
        }
        
        .strength-bar {
            display: flex;
            gap: 2px;
            margin-bottom: 0.5rem;
        }
        
        .strength-segment {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            transition: var(--transition);
        }
        
        .strength-segment.active {
            background: var(--success);
        }
        
        .strength-segment.medium {
            background: var(--warning);
        }
        
        .strength-segment.weak {
            background: var(--secondary);
        }
        
        .strength-text {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .checkbox {
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 4px;
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .checkbox:checked {
            background: var(--accent);
            border-color: var(--accent);
        }
        
        .checkbox-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
            line-height: 1.4;
            cursor: pointer;
        }
        
        .checkbox-label a {
            color: #4facfe;
            text-decoration: none;
        }
        
        .checkbox-label a:hover {
            text-decoration: underline;
        }
        
        .register-button {
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
        
        .register-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .register-button:disabled {
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
        
        .login-link {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .login-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .login-link a:hover {
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
        
        @media (max-width: 600px) {
            .register-container {
                padding: 1rem;
            }
            
            .register-card {
                padding: 2rem;
            }
            
            .form-group.half {
                grid-template-columns: 1fr;
                gap: 0;
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
    <main id="main-content" class="register-container">
        <div class="register-card">
            <div class="register-header">
                <a href="/" class="register-logo">
                    <div class="compass-icon">🧭</div>
                    Culture Radar
                </a>
                
                <h1 class="register-title">Bienvenue !</h1>
                <p class="register-subtitle">Créez votre compte pour découvrir la culture autour de vous</p>
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
            
            <form method="POST" action="/register.php" class="register-form">
                <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="name" class="form-label">Nom complet *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-input" 
                        placeholder="Votre nom et prénom"
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        required
                        aria-describedby="name-error"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Adresse email *</label>
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
                
                <div class="form-group half">
                    <div>
                        <label for="password" class="form-label">Mot de passe *</label>
                        <div class="password-input-group">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Mot de passe"
                                required
                                aria-describedby="password-error"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="password-strength">
                            <div class="strength-bar">
                                <div class="strength-segment"></div>
                                <div class="strength-segment"></div>
                                <div class="strength-segment"></div>
                                <div class="strength-segment"></div>
                            </div>
                            <div class="strength-text">Saisissez votre mot de passe</div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="form-label">Confirmer *</label>
                        <div class="password-input-group">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-input" 
                                placeholder="Confirmer"
                                required
                                aria-describedby="confirm-password-error"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="confirm-password-toggle-icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="accept_terms" name="accept_terms" class="checkbox" required>
                    <label for="accept_terms" class="checkbox-label">
                        J'accepte les <a href="/terms.php" target="_blank">conditions d'utilisation</a> 
                        et la <a href="/privacy.php" target="_blank">politique de confidentialité</a> *
                    </label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="accept_newsletter" name="accept_newsletter" class="checkbox">
                    <label for="accept_newsletter" class="checkbox-label">
                        Je souhaite recevoir la newsletter hebdomadaire avec les meilleures recommandations culturelles
                    </label>
                </div>
                
                <button type="submit" class="register-button">
                    <i class="fas fa-user-plus"></i>
                    Créer mon compte
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
            
            <div class="login-link">
                Déjà un compte ? 
                <a href="/login.php">Se connecter</a>
            </div>
        </div>
    </main>
    
    <!-- Scripts -->
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId + '-toggle-icon');
            
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
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const checks = {
                length: password.length >= 8,
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };
            
            Object.values(checks).forEach(check => {
                if (check) strength++;
            });
            
            return { strength, checks };
        }
        
        function updatePasswordStrength(password) {
            const { strength, checks } = checkPasswordStrength(password);
            const strengthBar = document.querySelectorAll('.strength-segment');
            const strengthText = document.querySelector('.strength-text');
            
            // Reset segments
            strengthBar.forEach(segment => {
                segment.classList.remove('active', 'medium', 'weak');
            });
            
            // Update segments based on strength
            for (let i = 0; i < strength; i++) {
                if (strength <= 2) {
                    strengthBar[i].classList.add('weak');
                } else if (strength <= 3) {
                    strengthBar[i].classList.add('medium');
                } else {
                    strengthBar[i].classList.add('active');
                }
            }
            
            // Update text
            if (password.length === 0) {
                strengthText.textContent = 'Saisissez votre mot de passe';
            } else if (strength <= 2) {
                strengthText.textContent = 'Mot de passe faible';
            } else if (strength <= 3) {
                strengthText.textContent = 'Mot de passe moyen';
            } else {
                strengthText.textContent = 'Mot de passe fort';
            }
        }
        
        // Password input event listener
        document.getElementById('password').addEventListener('input', function(e) {
            updatePasswordStrength(e.target.value);
            checkPasswordMatch();
        });
        
        // Confirm password checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const confirmInput = document.getElementById('confirm_password');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    confirmInput.style.borderColor = 'rgba(56, 239, 125, 0.5)';
                } else {
                    confirmInput.style.borderColor = 'rgba(245, 87, 108, 0.5)';
                }
            } else {
                confirmInput.style.borderColor = 'var(--glass-border)';
            }
        }
        
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        // Form validation
        document.querySelector('.register-form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const acceptTerms = document.getElementById('accept_terms').checked;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                showError('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showError('Veuillez saisir une adresse email valide.');
                return;
            }
            
            const { strength } = checkPasswordStrength(password);
            if (strength < 3) {
                e.preventDefault();
                showError('Le mot de passe doit être plus fort (au moins 8 caractères avec minuscule, majuscule et chiffre).');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showError('Les mots de passe ne correspondent pas.');
                return;
            }
            
            if (!acceptTerms) {
                e.preventDefault();
                showError('Vous devez accepter les conditions d\'utilisation.');
                return;
            }
            
            // Show loading state
            const submitBtn = e.target.querySelector('.register-button');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création du compte...';
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
            const form = document.querySelector('.register-form');
            form.parentNode.insertBefore(alert, form);
            
            // Scroll to alert
            alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            if (nameInput) {
                nameInput.focus();
            }
        });
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>