<?php 
session_start();

// Configuration de la base de données
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'jurishub',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
];

// Connexion sécurisée à la base de données
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
} catch (\PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$userId = 200;

// Récupération des conversations
$conversations = [];
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.nom, u.prenom, 
               (SELECT message FROM messages 
                WHERE (sender_id = u.id AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = u.id)
                ORDER BY created_at DESC LIMIT 1) as last_message
        FROM users u
        WHERE u.id IN (
            SELECT DISTINCT IF(sender_id = ?, receiver_id, sender_id) as contact_id
            FROM messages
            WHERE sender_id = ? OR receiver_id = ?
        )
        ORDER BY (SELECT created_at FROM messages 
                 WHERE (sender_id = u.id AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = u.id)
                 ORDER BY created_at DESC LIMIT 1) DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
    $conversations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur conversations: " . $e->getMessage());
}

// Récupération de l'interlocuteur sélectionné
$otherUserId = isset($_GET['contact']) ? intval($_GET['contact']) : null;
$otherUser = null;
$messages = [];

if ($otherUserId) {
    try {
        // Vérification que l'utilisateur existe
        $stmt = $pdo->prepare("SELECT id, nom, prenom FROM users WHERE id = ?");
        $stmt->execute([$otherUserId]);
        $otherUser = $stmt->fetch();

        if (!$otherUser) {
            die("Utilisateur introuvable");
        }

        // Récupération des messages
        $stmt = $pdo->prepare("
            SELECT m.*, u.nom, u.prenom 
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
        $messages = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log("Erreur récupération messages: " . $e->getMessage());
        die("Erreur lors de la récupération des messages");
    }
}

// Traitement de l'envoi de message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $otherUserId) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $otherUserId, $message]);
            
            // Redirection pour éviter le renvoi du formulaire
            header("Location: msg.php?contact=" . $otherUserId);
            exit();
        } catch (PDOException $e) {
            error_log("Erreur envoi message: " . $e->getMessage());
            die("Erreur lors de l'envoi du message");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie | JurisHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark:rgb(25, 131, 202);
            --primary: #3498db;
            --primary-light: #415a77;
            --secondary: #778da9;
            --secondary-light: #a8c0d6;
            --accent:rgb(104, 104, 104);
            --accent-light:rgb(168, 168, 168);
            --light: #f8f9fa;
            --light-gray: #e9ecef;
            --dark: #212529;
            --text: #2b2d42;
            --text-light: #6c757d;
            --white: #ffffff;
            --gold:rgb(15, 0, 61);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.16);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --sidebar-width: 300px;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f5f7fa;
            color: var(--text);
            line-height: 1.7;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar Luxe */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-dark), var(--primary));
            color: var(--white);
            position: fixed;
            height: 100vh;
            padding: 2rem 0;
            transition: var(--transition);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 3rem;
            padding: 0 2rem;
            position: relative;
        }

        .logo::after {
            content: '';
            display: block;
            width: 60%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            margin: 1.5rem auto 0;
        }

        .logo a {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .logo-icon {
            color: var(--gold);
            font-size: 1.8rem;
        }

        .nav-container {
            flex: 1;
            overflow-y: auto;
            padding: 0 1rem;
        }

        .nav-links {
            list-style: none;
        }

        .nav-item {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.9rem 1.5rem;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: var(--transition);
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .nav-link i {
            min-width: 36px;
            font-size: 1.1rem;
            text-align: center;
            transition: var(--transition);
            color: var(--secondary-light);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--white);
            transform: translateX(5px);
        }

        .nav-link:hover i {
            color: var(--gold);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(119, 141, 169, 0.2), transparent);
            color: var(--white);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--gold);
        }

        .nav-link.active i {
            color: var(--gold);
        }

        .sidebar-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: auto;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
            color: var(--white);
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--secondary-light);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-deconnexion {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            width: 100%;
            padding: 0.8rem;
            background: transparent;
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .btn-deconnexion:hover {
            background: rgba(230, 57, 70, 0.2);
            color: var(--white);
            border-color: var(--accent);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 3rem;
            transition: var(--transition);
            background-color: var(--light);
        }

        .page-header {
            margin-bottom: 3rem;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--accent));
            border-radius: 3px;
        }

        .page-description {
            color: var(--text-light);
            max-width: 700px;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        /* Floating Button */
        .floating-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 20px rgba(230, 57, 70, 0.3);
            cursor: pointer;
            transition: var(--transition);
            z-index: 100;
            border: none;
        }

        .floating-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(230, 57, 70, 0.4);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .main-content {
                padding: 2rem;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 1050;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.8rem;
            }
        }

        /* Styles pour la messagerie */
        .messaging-container {
            display: flex;
            height: calc(100vh - 180px);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            background: var(--white);
        }
        
        .contacts-list {
            width: 300px;
            border-right: 1px solid var(--light-gray);
            overflow-y: auto;
            background: var(--white);
        }
        
        .contact {
            padding: 15px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .contact:hover {
            background: var(--light);
        }
        
        .contact.active {
            background: var(--primary-light);
            color: var(--white);
        }
        
        .contact.active .contact-lastmsg {
            color: rgba(255,255,255,0.8);
        }
        
        .contact-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .contact-info {
            flex: 1;
            min-width: 0;
        }
        
        .contact-name {
            font-weight: 600;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-decoration: none;
        }
        
        .contact-lastmsg {
            font-size: 13px;
            color: var(--text-light);
            white-space: nowrap;
            overflow: hidden;
            text-decoration: none;
            text-overflow: ellipsis;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 15px 20px;
            background: var(--primary);
            color: var(--white);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f5f7fa;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .sent {
            background: var(--primary);
            color: var(--white);
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .received {
            background: var(--white);
            margin-right: auto;
            border-bottom-left-radius: 4px;
            box-shadow: var(--shadow-sm);
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 5px;
            text-align: right;
        }
        
        .message-form {
            padding: 15px;
            background: var(--white);
            border-top: 1px solid var(--light-gray);
            display: flex;
            gap: 10px;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 18px;
            border: 1px solid var(--light-gray);
            border-radius: 24px;
            outline: none;
            font-size: 15px;
            transition: var(--transition);
        }
        
        .message-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(48, 63, 159, 0.2);
        }
        
        .send-btn {
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .send-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }
        
        .no-conversation {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-light);
            text-align: center;
            padding: 30px;
        }
        
        .no-conversation i {
            font-size: 50px;
            margin-bottom: 20px;
            color: var(--light-gray);
        }
        
        .toggle-contacts {
            display: none;
            margin-bottom: 15px;
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .toggle-contacts:hover {
            background: var(--primary-dark);
        }
        
        .toggle-contacts i {
            margin-right: 8px;
        }

        @media (max-width: 992px) {
            .contacts-list {
                width: 280px;
            }
        }
        
        @media (max-width: 768px) {
            .contacts-list {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                width: 280px;
                z-index: 1000;
                transform: translateX(-100%);
                transition: var(--transition);
                box-shadow: var(--shadow-lg);
            }
            
            .contacts-list.active {
                transform: translateX(0);
            }
            
            .toggle-contacts {
                display: inline-flex;
                align-items: center;
            }
            
            .messaging-container {
                height: calc(100vh - 220px);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="logo">
            <a href="indexav.php">
                <i class="fas fa-balance-scale logo-icon"></i>
                <span>JurisHub</span>
            </a>
        </div>
        
        <div class="nav-container">
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="calendrier.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Agenda</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tdl.php" class="nav-link">
                        <i class="fas fa-tasks"></i>
                        <span>To-do list</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stat.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistiques</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="msg.php" class="nav-link active">
                        <i class="fas fa-envelope"></i>
                        <span>Messagerie</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="recherche.php" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Rechercher</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="user-info">
                    <div class="user-name">Maître Hajaij</div>
                    <div class="user-role">Avocate</div>
                </div>
            </div>
            <button class="btn-deconnexion">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Messagerie</h1>
            <p class="page-description">
                Communiquez avec vos collègues et clients
            </p>
        </div>
        
        <button class="toggle-contacts" id="toggleContacts">
            <i class="fas fa-users"></i> Liste des contacts
        </button>
        
        <div class="messaging-container">
            <div class="contacts-list" id="contactsList">
                <?php if (empty($conversations)): ?>
                    <div class="no-conversation">
                        <i class="fas fa-comment-slash"></i>
                        <p>Aucune conversation</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $contact): ?>
                        <a href="msg.php?contact=<?= htmlspecialchars($contact['id']) ?>" class="contact <?= ($otherUserId == $contact['id']) ? 'active' : '' ?>">
                            <div class="contact-avatar">
                                <?= strtoupper(substr($contact['prenom'], 0, 1)) . strtoupper(substr($contact['nom'], 0, 1)) ?>
                            </div>
                            <div class="contact-info">
                                <div class="contact-name"><?= htmlspecialchars($contact['prenom'] . ' ' . $contact['nom']) ?></div>
                                <div class="contact-lastmsg">
                                    <?= !empty($contact['last_message']) ? 
                                        htmlspecialchars($contact['last_message']) : 'Aucun message' ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="chat-area">
                <?php if ($otherUser): ?>
                    <div class="chat-header">
                        <div class="contact-avatar">
                            <?= strtoupper(substr($otherUser['prenom'], 0, 1)) . strtoupper(substr($otherUser['nom'], 0, 1)) ?>
                        </div>
                        <div><?= htmlspecialchars($otherUser['prenom'] . ' ' . $otherUser['nom']) ?></div>
                    </div>
                    
                    <div class="messages-container" id="messagesContainer">
                        <?php if (empty($messages)): ?>
                            <div class="no-conversation">
                                <i class="fas fa-comment-medical"></i>
                                <p>Envoyez votre premier message</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?= ($message['sender_id'] == $userId) ? 'sent' : 'received' ?>">
                                    <div><?= htmlspecialchars($message['message']) ?></div>
                                    <div class="message-time">
                                        <?= date('H:i', strtotime($message['created_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <form class="message-form" method="POST">
                        <input type="text" name="message" class="message-input" placeholder="Écrivez votre message..." required>
                        <button type="submit" class="send-btn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="no-conversation">
                        <i class="fas fa-user-friends"></i>
                        <p>Sélectionnez un contact pour commencer à discuter</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Floating Action Button -->
    <button class="floating-btn" id="menuToggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
    </button>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });

        // Toggle contacts list on mobile
        const toggleContacts = document.getElementById('toggleContacts');
        const contactsList = document.getElementById('contactsList');
        
        if (toggleContacts && contactsList) {
            toggleContacts.addEventListener('click', (e) => {
                e.stopPropagation();
                contactsList.classList.toggle('active');
            });
        }

        // Fermer la sidebar en cliquant à l'extérieur
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && e.target !== menuToggle) {
                sidebar.classList.remove('active');
            }
            
            if (contactsList && !contactsList.contains(e.target) && e.target !== toggleContacts) {
                contactsList.classList.remove('active');
            }
        });

        // Scroll to bottom of messages
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Auto-refresh messages
        function refreshMessages() {
            if (<?= $otherUserId ? 'true' : 'false' ?>) {
                fetch(`get_messages.php?currentUserId=<?= $userId ?>&otherUserId=<?= $otherUserId ?? 0 ?>`)
                    .then(res => res.json())
                    .then(messages => {
                        const container = document.getElementById('messagesContainer');
                        if (container) {
                            container.innerHTML = messages.map(msg => `
                                <div class="message ${msg.sender_id == <?= $userId ?> ? 'sent' : 'received'}">
                                    <div>${msg.message}</div>
                                    <div class="message-time">
                                        ${formatTime(msg.created_at)}
                                    </div>
                                </div>
                            `).join('');
                            container.scrollTop = container.scrollHeight;
                        }
                    });
            }
        }
        
        function formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
        
        // Actualiser toutes les 3 secondes
        setInterval(refreshMessages, 3000);
    </script>
</body>
</html>