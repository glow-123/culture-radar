<?php
session_start();

// Load configuration
require_once __DIR__ . '/../config.php';

// Check if organizer is logged in
if (!isset($_SESSION['organizer_id'])) {
    header('Location: /organizer/login.php');
    exit();
}

$error = '';
$success = '';
$events = [];
$editEvent = null;

// Handle create/edit form
$isCreating = isset($_GET['create']) && $_GET['create'] == '1';
$isEditing = isset($_GET['edit']) && is_numeric($_GET['edit']);

try {
    $dbConfig = Config::database();
    $dsn = "mysql:host=" . $dbConfig['host'] . ";dbname=" . $dbConfig['name'] . ";charset=" . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create' || $action === 'edit') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = $_POST['category'] ?? '';
            $venue_name = trim($_POST['venue_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $price = (float)($_POST['price'] ?? 0);
            $is_free = isset($_POST['is_free']) ? 1 : 0;
            $external_url = trim($_POST['external_url'] ?? '');
            
            // Validation
            if (empty($title) || empty($category) || empty($start_date)) {
                $error = 'Veuillez remplir tous les champs obligatoires.';
            } else {
                if ($action === 'create') {
                    $stmt = $pdo->prepare("
                        INSERT INTO events (title, description, category, venue_name, address, city, postal_code, 
                                          start_date, end_date, price, is_free, external_url, organizer_id, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $title, $description, $category, $venue_name, $address, $city, $postal_code,
                        $start_date, $end_date ?: null, $price, $is_free, $external_url, $_SESSION['organizer_id']
                    ]);
                    $success = 'Événement créé avec succès !';
                } elseif ($action === 'edit' && isset($_POST['event_id'])) {
                    $eventId = (int)$_POST['event_id'];
                    
                    // Verify ownership
                    $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND organizer_id = ?");
                    $stmt->execute([$eventId, $_SESSION['organizer_id']]);
                    
                    if ($stmt->fetch()) {
                        $stmt = $pdo->prepare("
                            UPDATE events 
                            SET title = ?, description = ?, category = ?, venue_name = ?, address = ?, 
                                city = ?, postal_code = ?, start_date = ?, end_date = ?, price = ?, 
                                is_free = ?, external_url = ?, updated_at = NOW()
                            WHERE id = ? AND organizer_id = ?
                        ");
                        $stmt->execute([
                            $title, $description, $category, $venue_name, $address, $city, $postal_code,
                            $start_date, $end_date ?: null, $price, $is_free, $external_url, $eventId, $_SESSION['organizer_id']
                        ]);
                        $success = 'Événement mis à jour avec succès !';
                    } else {
                        $error = 'Événement non trouvé ou accès non autorisé.';
                    }
                }
            }
        } elseif ($action === 'delete' && isset($_POST['event_id'])) {
            $eventId = (int)$_POST['event_id'];
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND organizer_id = ?");
            $stmt->execute([$eventId, $_SESSION['organizer_id']]);
            
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE events SET is_active = 0 WHERE id = ? AND organizer_id = ?");
                $stmt->execute([$eventId, $_SESSION['organizer_id']]);
                $success = 'Événement supprimé avec succès !';
            } else {
                $error = 'Événement non trouvé ou accès non autorisé.';
            }
        }
    }
    
    // Get event for editing
    if ($isEditing) {
        $eventId = (int)$_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
        $stmt->execute([$eventId, $_SESSION['organizer_id']]);
        $editEvent = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$editEvent) {
            $error = 'Événement non trouvé.';
            $isEditing = false;
        }
    }
    
    // Get organizer's events
    $stmt = $pdo->prepare("
        SELECT e.*, 
               COUNT(ui.id) as total_interactions,
               COUNT(CASE WHEN ui.interaction_type = 'save' THEN 1 END) as saves,
               COUNT(CASE WHEN ui.interaction_type = 'click' THEN 1 END) as clicks
        FROM events e
        LEFT JOIN user_interactions ui ON e.id = ui.event_id
        WHERE e.organizer_id = ? AND e.is_active = 1
        GROUP BY e.id
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$_SESSION['organizer_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Erreur lors du chargement des données.';
    error_log("Organizer events error: " . $e->getMessage());
}

$categories = [
    'art' => '🎨 Art & Expo',
    'music' => '🎵 Musique',
    'theater' => '🎭 Théâtre',
    'cinema' => '🎬 Cinéma',
    'literature' => '📚 Littérature',
    'heritage' => '🏛️ Patrimoine',
    'dance' => '💃 Danse',
    'festival' => '🎪 Festival'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes événements - Culture Radar Pro</title>
    
    <?php include '../includes/favicon.php'; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .events-container {
            padding-top: 100px;
            min-height: 100vh;
        }
        
        .events-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .events-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
        }
        
        .create-btn {
            background: var(--success);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .create-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }
        
        .form-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 2rem;
        }
        
        .form-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.7;
            transition: var(--transition);
        }
        
        .close-btn:hover {
            opacity: 1;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            display: block;
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.75rem;
            color: white;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: rgba(79, 172, 254, 0.5);
            box-shadow: 0 0 15px rgba(79, 172, 254, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .event-card {
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1.5rem;
            transition: var(--transition);
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .event-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .event-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: var(--success);
            color: white;
        }
        
        .status-upcoming {
            background: var(--accent);
            color: white;
        }
        
        .status-past {
            background: rgba(255, 255, 255, 0.3);
            color: rgba(255, 255, 255, 0.8);
        }
        
        .event-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .event-category {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .event-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .event-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .event-stat {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            color: white;
            text-align: center;
        }
        
        .event-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: rgba(255, 255, 255, 0.7);
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .events-header {
                flex-direction: column;
                text-align: center;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="animated-bg" aria-hidden="true">
        <div class="stars"></div>
        <div class="floating-shapes"></div>
    </div>
    
    <!-- Header -->
    <header class="header" role="banner">
        <nav class="nav" role="navigation">
            <a href="/organizer/" class="logo">
                <div class="location-pin-icon"></div>
                Culture Radar <span style="font-size: 0.8rem; opacity: 0.8;">Pro</span>
            </a>
            
            <ul class="nav-links">
                <li><a href="/organizer/dashboard.php">Tableau de bord</a></li>
                <li><a href="/organizer/events.php" class="active">Mes événements</a></li>
                <li><a href="/organizer/analytics.php">Statistiques</a></li>
            </ul>
            
            <div class="nav-actions">
                <div class="user-menu">
                    <button class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['organizer_name'] ?? 'O', 0, 1)); ?>
                    </button>
                    <div class="user-dropdown">
                        <a href="/organizer/profile.php">Mon profil</a>
                        <a href="/organizer/logout.php">Déconnexion</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main class="events-container">
        <div class="container">
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
            
            <div class="events-header">
                <h1 class="events-title">Mes événements</h1>
                <a href="?create=1" class="create-btn">
                    <i class="fas fa-plus"></i>
                    Créer un événement
                </a>
            </div>
            
            <?php if (!empty($events)): ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <div class="event-card-header">
                                <div class="event-category">
                                    <?php echo $categories[$event['category']] ?? ucfirst($event['category']); ?>
                                </div>
                                <div class="event-status <?php 
                                    $now = time();
                                    $start = strtotime($event['start_date']);
                                    if ($start > $now) echo 'status-upcoming';
                                    elseif ($start < $now) echo 'status-past';
                                    else echo 'status-active';
                                ?>">
                                    <?php 
                                    if ($start > $now) echo 'À venir';
                                    elseif ($start < $now) echo 'Passé';
                                    else echo 'En cours';
                                    ?>
                                </div>
                            </div>
                            
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            
                            <div class="event-meta">
                                <div><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($event['start_date'])); ?></div>
                                <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['city']); ?></div>
                                <div><i class="fas fa-euro-sign"></i> <?php echo $event['is_free'] ? 'Gratuit' : number_format($event['price'], 2) . ' €'; ?></div>
                                <div><i class="fas fa-building"></i> <?php echo htmlspecialchars($event['venue_name'] ?: 'Non spécifié'); ?></div>
                            </div>
                            
                            <div class="event-stats">
                                <div class="event-stat">
                                    <div style="font-weight: 600;"><?php echo $event['total_interactions']; ?></div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">Vues</div>
                                </div>
                                <div class="event-stat">
                                    <div style="font-weight: 600;"><?php echo $event['saves']; ?></div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">Favoris</div>
                                </div>
                                <div class="event-stat">
                                    <div style="font-weight: 600;"><?php echo $event['clicks']; ?></div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">Clics</div>
                                </div>
                            </div>
                            
                            <div class="event-actions">
                                <a href="?edit=<?php echo $event['id']; ?>" class="btn-small btn-primary">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="/event.php?id=<?php echo $event['id']; ?>" class="btn-small btn-outline" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> Voir
                                </a>
                                <button class="btn-small btn-outline" style="background: rgba(245, 87, 108, 0.2); border-color: #f5576c; color: #f5576c;" onclick="deleteEvent(<?php echo $event['id']; ?>)">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;">📅</div>
                    <h3>Aucun événement créé</h3>
                    <p>Commencez par créer votre premier événement culturel.</p>
                    <a href="?create=1" class="btn-primary" style="margin-top: 1rem;">
                        Créer un événement
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Create/Edit Modal -->
    <?php if ($isCreating || $isEditing): ?>
        <div class="form-modal">
            <div class="form-card">
                <div class="form-header">
                    <h2 class="form-title">
                        <?php echo $isEditing ? 'Modifier l\'événement' : 'Créer un événement'; ?>
                    </h2>
                    <button class="close-btn" onclick="window.location.href='/organizer/events.php'">×</button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $isEditing ? 'edit' : 'create'; ?>">
                    <?php if ($isEditing): ?>
                        <input type="hidden" name="event_id" value="<?php echo $editEvent['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="title" class="form-label">Titre de l'événement *</label>
                            <input type="text" id="title" name="title" class="form-input" 
                                   value="<?php echo htmlspecialchars($editEvent['title'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category" class="form-label">Catégorie *</label>
                            <select id="category" name="category" class="form-select" required>
                                <option value="">Choisir une catégorie</option>
                                <?php foreach ($categories as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" 
                                            <?php echo ($editEvent['category'] ?? '') === $value ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="city" class="form-label">Ville *</label>
                            <input type="text" id="city" name="city" class="form-input" 
                                   value="<?php echo htmlspecialchars($editEvent['city'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="venue_name" class="form-label">Lieu</label>
                            <input type="text" id="venue_name" name="venue_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($editEvent['venue_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" id="address" name="address" class="form-input" 
                                   value="<?php echo htmlspecialchars($editEvent['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date" class="form-label">Date et heure de début *</label>
                            <input type="datetime-local" id="start_date" name="start_date" class="form-input" 
                                   value="<?php echo $editEvent ? date('Y-m-d\TH:i', strtotime($editEvent['start_date'])) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date" class="form-label">Date et heure de fin</label>
                            <input type="datetime-local" id="end_date" name="end_date" class="form-input" 
                                   value="<?php echo $editEvent && $editEvent['end_date'] ? date('Y-m-d\TH:i', strtotime($editEvent['end_date'])) : ''; ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-textarea"><?php echo htmlspecialchars($editEvent['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="price" class="form-label">Prix (€)</label>
                            <input type="number" id="price" name="price" class="form-input" step="0.01" min="0"
                                   value="<?php echo $editEvent['price'] ?? ''; ?>">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_free" name="is_free" 
                                       <?php echo ($editEvent['is_free'] ?? false) ? 'checked' : ''; ?>>
                                <label for="is_free" style="color: rgba(255, 255, 255, 0.8); font-weight: normal;">Événement gratuit</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="external_url" class="form-label">Site web / Billetterie</label>
                            <input type="url" id="external_url" name="external_url" class="form-input" 
                                   value="<?php echo htmlspecialchars($editEvent['external_url'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                        <button type="button" class="btn-outline" onclick="window.location.href='/organizer/events.php'">
                            Annuler
                        </button>
                        <button type="submit" class="btn-primary">
                            <?php echo $isEditing ? 'Mettre à jour' : 'Créer l\'événement'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        function deleteEvent(eventId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="event_id" value="${eventId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Free event checkbox handler
        document.getElementById('is_free')?.addEventListener('change', function() {
            const priceInput = document.getElementById('price');
            if (this.checked) {
                priceInput.value = '0';
                priceInput.disabled = true;
            } else {
                priceInput.disabled = false;
            }
        });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>