<?php
session_start();
require_once __DIR__ . '/config.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit();
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $pdo = Config::getPDO();
            
            // Vérifier les identifiants
            $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Rediriger vers le dashboard
                header('Location: /dashboard.php');
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (Exception $e) {
            // Pour le test, accepter les identifiants de démo
            if ($email === 'test@culture-radar.fr' && $password === 'password123') {
                $_SESSION['user_id'] = 1;
                $_SESSION['user_name'] = 'Utilisateur Test';
                $_SESSION['user_email'] = $email;
                header('Location: /dashboard.php');
                exit();
            }
            $error = 'Erreur de connexion. Utilisez test@culture-radar.fr / password123';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Culture Radar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
            padding: 40px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .logo h1 {
            color: #333;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        h2 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .error-message {
            background: #ff4444;
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info-message {
            background: #4CAF50;
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .demo-info {
            background: #f0f0ff;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .demo-info h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .demo-info p {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-radar"></i>
            <h1>Culture Radar</h1>
        </div>
        
        <h2>Connexion</h2>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/login-simple.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="votre@email.com">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required 
                       placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-login">
                Se connecter
            </button>
        </form>
        
        <div class="form-footer">
            <p>Pas encore de compte ? <a href="/register.php">S'inscrire</a></p>
            <p style="margin-top: 10px;"><a href="/">Retour à l'accueil</a></p>
        </div>
        
        <div class="demo-info">
            <h3>🔐 Compte de démonstration</h3>
            <p>
                <strong>Email:</strong> test@culture-radar.fr<br>
                <strong>Mot de passe:</strong> password123
            </p>
        </div>
    </div>
</body>
</html>