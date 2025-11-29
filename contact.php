<?php
session_start();
require_once __DIR__ . '/config.php';

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = false;
$error = false;
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = true;
        $message = 'Erreur de sécurité. Veuillez réessayer.';
    } else {
        // Sanitize inputs
        $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $subject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_STRING);
        $phone = filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING);
        $messageText = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_STRING);
        $category = filter_var($_POST['category'] ?? 'general', FILTER_SANITIZE_STRING);
        
        // Validate inputs
        $errors = [];
        
        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Le nom est requis (minimum 2 caractères)';
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide';
        }
        
        if (empty($subject) || strlen($subject) < 5) {
            $errors[] = 'Le sujet est requis (minimum 5 caractères)';
        }
        
        if (empty($messageText) || strlen($messageText) < 20) {
            $errors[] = 'Le message est requis (minimum 20 caractères)';
        }
        
        // Honeypot check (anti-spam)
        if (!empty($_POST['website'])) {
            $errors[] = 'Spam détecté';
        }
        
        if (empty($errors)) {
            // Save to database if available
            try {
                if (isset($pdo)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO contact_messages (name, email, phone, subject, category, message, ip_address, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $name, 
                        $email, 
                        $phone, 
                        $subject, 
                        $category, 
                        $messageText, 
                        $_SERVER['REMOTE_ADDR'] ?? ''
                    ]);
                }
                
                // For demo purposes, we'll just show success
                // In production, you would send an email here
                $success = true;
                $message = 'Merci pour votre message ! Nous vous répondrons dans les plus brefs délais.';
                
                // Clear form data
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
            } catch (Exception $e) {
                // Even if database fails, show success (for demo)
                $success = true;
                $message = 'Merci pour votre message ! Nous vous répondrons dans les plus brefs délais.';
            }
        } else {
            $error = true;
            $message = implode('<br>', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Culture Radar | Nous sommes à votre écoute</title>
    <meta name="description" content="Contactez l'équipe Culture Radar. Questions, suggestions, partenariats - nous sommes là pour vous aider à découvrir la culture.">
    
    <?php if (file_exists(__DIR__ . '/includes/favicon.php')): ?>
        <?php include 'includes/favicon.php'; ?>
    <?php endif; ?>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/accessibility.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        .university-notice {
            background: linear-gradient(90deg, #8B5CF6, #3B82F6);
            color: white;
            text-align: center;
            padding: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            margin-bottom: 2rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
        
        .contact-header {
            text-align: center;
            color: white;
            margin-bottom: 3rem;
        }
        
        .contact-header h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .contact-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
        }
        
        @media (max-width: 968px) {
            .contact-content {
                grid-template-columns: 1fr;
            }
        }
        
        .contact-form-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        .contact-info-section {
            color: white;
        }
        
        .form-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            color: #1F2937;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            color: #4B5563;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-group label .required {
            color: #EF4444;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .form-control.error {
            border-color: #EF4444;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .honeypot {
            position: absolute;
            left: -9999px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #8B5CF6, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
        }
        
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .info-card h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .info-card p {
            margin-bottom: 1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .contact-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .contact-item i {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .contact-item a {
            color: white;
            text-decoration: none;
        }
        
        .contact-item a:hover {
            text-decoration: underline;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .social-link {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .social-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
            border-color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        
        .faq-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .faq-item {
            margin-bottom: 1rem;
        }
        
        .faq-question {
            width: 100%;
            text-align: left;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-question:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .faq-answer {
            padding: 0 1rem;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            opacity: 0;
        }
        
        .faq-item.active .faq-answer {
            padding: 1rem;
            max-height: 500px;
            opacity: 0.9;
        }
        
        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }
        
        .map-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem;
            margin-top: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- University Notice -->
    <div class="university-notice">
        🎓 Projet Universitaire - Site de démonstration à des fins éducatives uniquement
    </div>
    
    <div class="contact-container">
        <!-- Back Button -->
        <a href="/" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Retour à l'accueil
        </a>
        
        <!-- Header -->
        <div class="contact-header">
            <h1>Contactez-nous</h1>
            <p>Une question ? Une suggestion ? Un partenariat ? Nous sommes à votre écoute !</p>
        </div>
        
        <!-- Main Content -->
        <div class="contact-content">
            <!-- Contact Form -->
            <div class="contact-form-section">
                <h2 class="form-title">Envoyez-nous un message</h2>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="contactForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Honeypot field (anti-spam) -->
                    <div class="honeypot">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">
                                Nom complet <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   required 
                                   minlength="2"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                Email <span class="required">*</span>
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">
                                Téléphone
                            </label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone"
                                   placeholder="+33 6 12 34 56 78"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category">
                                Catégorie
                            </label>
                            <select class="form-control" id="category" name="category">
                                <option value="general">Question générale</option>
                                <option value="bug">Signaler un bug</option>
                                <option value="feature">Suggestion de fonctionnalité</option>
                                <option value="partnership">Partenariat</option>
                                <option value="press">Presse</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">
                            Sujet <span class="required">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="subject" 
                               name="subject" 
                               required 
                               minlength="5"
                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">
                            Message <span class="required">*</span>
                        </label>
                        <textarea class="form-control" 
                                  id="message" 
                                  name="message" 
                                  required 
                                  minlength="20"
                                  rows="5"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Envoyer le message
                    </button>
                </form>
            </div>
            
            <!-- Contact Information -->
            <div class="contact-info-section">
                <!-- Contact Info Card -->
                <div class="info-card">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        Informations de contact
                    </h3>
                    
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong><br>
                            <a href="mailto:contact@culture-radar.fr">contact@culture-radar.fr</a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Téléphone</strong><br>
                            <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Adresse</strong><br>
                            Université Paris-Saclay<br>
                            91190 Gif-sur-Yvette, France
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Horaires</strong><br>
                            Lun-Ven: 9h00 - 18h00<br>
                            Weekend: Support par email
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="info-card">
                    <h3>
                        <i class="fas fa-share-alt"></i>
                        Suivez-nous
                    </h3>
                    <p>Restez connecté avec Culture Radar sur les réseaux sociaux pour découvrir les dernières actualités culturelles !</p>
                    
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Map placeholder -->
                <div class="map-container">
                    <i class="fas fa-map-marked-alt" style="font-size: 3rem; margin-right: 1rem;"></i>
                    <span>Carte interactive (Projet universitaire)</span>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="faq-section">
            <h3 style="color: white; font-family: 'Poppins', sans-serif; font-size: 1.8rem; margin-bottom: 2rem; text-align: center;">
                Questions fréquentes
            </h3>
            
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    <span>Comment fonctionne Culture Radar ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Culture Radar utilise l'intelligence artificielle pour analyser vos préférences culturelles et vous recommander des événements personnalisés dans votre ville. Notre algorithme apprend continuellement de vos interactions pour affiner ses suggestions.
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    <span>L'application est-elle gratuite ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Oui ! Culture Radar est un projet universitaire gratuit. Il s'agit d'un site de démonstration créé à des fins éducatives. Aucune transaction réelle n'est effectuée.
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    <span>Comment proposer un événement ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Pour proposer un événement, utilisez le formulaire de contact ci-dessus en sélectionnant "Partenariat" comme catégorie. Décrivez votre événement et nous reviendrons vers vous rapidement.
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    <span>Mes données sont-elles sécurisées ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Absolument ! Nous prenons la protection de vos données très au sérieux. Toutes les informations sont cryptées et nous ne partageons jamais vos données personnelles avec des tiers. Consultez notre politique de confidentialité pour plus de détails.
                </div>
            </div>
            
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFAQ(this)">
                    <span>Dans quelles villes Culture Radar est-il disponible ?</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer">
                    Actuellement, Culture Radar couvre Paris et sa région. Nous prévoyons d'étendre notre service à Lyon, Marseille, Bordeaux et d'autres grandes villes françaises prochainement.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="assets/js/accessibility.js"></script>
    <script>
        // FAQ Toggle
        function toggleFAQ(button) {
            const faqItem = button.parentElement;
            faqItem.classList.toggle('active');
        }
        
        // Form validation
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            
            let valid = true;
            let errors = [];
            
            if (name.length < 2) {
                errors.push('Le nom doit contenir au moins 2 caractères');
                valid = false;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Email invalide');
                valid = false;
            }
            
            if (subject.length < 5) {
                errors.push('Le sujet doit contenir au moins 5 caractères');
                valid = false;
            }
            
            if (message.length < 20) {
                errors.push('Le message doit contenir au moins 20 caractères');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
                alert('Erreurs dans le formulaire:\n' + errors.join('\n'));
            }
        });
        
        // Character counter for textarea
        const messageField = document.getElementById('message');
        const updateCounter = () => {
            const length = messageField.value.length;
            const label = messageField.previousElementSibling;
            const counter = label.querySelector('.char-counter');
            
            if (counter) {
                counter.textContent = `(${length}/500)`;
            } else {
                label.innerHTML += ` <span class="char-counter" style="color: #9CA3AF; font-weight: normal;">(${length}/500)</span>`;
            }
        };
        
        messageField.addEventListener('input', updateCounter);
        
        // Smooth animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.info-card, .faq-section').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>