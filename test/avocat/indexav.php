<?php 
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

// Connexion sécurisée à la base de données avec gestion des erreurs
try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
} catch (\PDOException $e) {
    error_log("Erreur de connexion à la base de données: " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données. Veuillez réessayer plus tard.");
}

// Date du jour formatée une seule fois
$today = new DateTime();
$todayDate = $today->format('Y-m-d');

// Fonction pour exécuter des requêtes préparées sécurisées
function fetchData(PDO $pdo, string $query, array $params = []) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

// Récupération des données avec requêtes préparées
try {
    $nbr_rdv = fetchData($pdo, "SELECT COUNT(*) FROM appointments WHERE DATE(date_time) = ?", [$todayDate])->fetchColumn();
    $nbr_proces = fetchData($pdo, "SELECT COUNT(*) FROM proces WHERE DATE(hearing_date) = ?", [$todayDate])->fetchColumn();
    $tasks = fetchData($pdo, "SELECT * FROM tasks WHERE DATE(due_date) = ?", [$todayDate])->fetchAll();
    
    // Récupération des rendez-vous et procès pour la timeline
    $rdvs = fetchData($pdo,"SELECT TIME(a.date_time) AS heure,'RDV' AS type,CONCAT(u.nom, ' ', u.prenom) AS titre
            FROM 
                appointments a
            JOIN 
                users u ON a.client_id = u.id
            WHERE 
                DATE(a.date_time) = ?
                AND u.role = 'client'
            ORDER BY 
                a.date_time", [$todayDate])->fetchAll();
    
    $proces = fetchData($pdo, 
        "SELECT TIME(hearing_date) as heure, 'Procès' as type, CONCAT('Tribunal ', location, ' - Affaire ', legal_case_id) as titre 
         FROM proces 
         WHERE DATE(hearing_date) = ? 
         ORDER BY hearing_date", [$todayDate])->fetchAll();
    
} catch (\PDOException $e) {
    error_log("Erreur lors de la récupération des données: " . $e->getMessage());
    // Valeurs par défaut en cas d'erreur
    $nbr_rdv = 0;
    $nbr_proces = 0;
    $tasks = [];
    $rdvs = [];
    $proces = [];
}

// Fonction pour vérifier si une audience est dans moins de 2 heures
function audienceDansMoinsDe2h(array $proces): array|false {
    $now = new DateTime();
    $now->modify('+2 hours');
    
    foreach ($proces as $p) {
        try {
            $heureAudience = DateTime::createFromFormat('H:i:s', $p['heure']);
            if ($heureAudience && $heureAudience <= $now) {
                return $p;
            }
        } catch (Exception $e) {
            error_log("Erreur de format d'heure: " . $e->getMessage());
        }
    }
    return false;
}

// Fonction pour obtenir une citation aléatoire
function randomCitation(): string {
    $citations = [
        "Nul n'est censé ignorer la loi.",
        "La liberté des uns s'arrête là où commence celle des autres.",
        "La justice élève une nation.",
        "Un mauvais compromis vaut mieux qu'un bon procès.",
        "Le droit est l'expression de la volonté générale.",
        "À droit égal, force égale.",
        "La vérité finit toujours par triompher."
    ];
    return $citations[array_rand($citations)];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil | JurisHub</title>
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

        /* Sidebar Navigation Luxe */
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

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .dashboard-card {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            text-align: center;
            border-top: 4px solid var(--gold);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .dashboard-card h3 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .dashboard-card p {
            color: var(--text-light);
            font-size: 1rem;
        }

        .dashboard-card i {
            font-size: 2.5rem;
            color: var(--gold);
            margin-bottom: 1rem;
        }

        /* Tasks Section */
        .tasks-container {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .tasks-container h2 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .task-list {
            list-style: none;
        }

        .task-item {
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: var(--light);
            border-radius: 8px;
            border-left: 4px solid var(--accent);
            transition: var(--transition);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .task-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-sm);
        }

        .task-info h4 {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .task-info p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .task-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(119, 141, 169, 0.1);
            color: var(--primary-light);
        }

        .no-tasks {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
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
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.8rem;
            }
            
            .task-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        /* Ajout pour l'alerte audience */
        .audience-alert {
            background-color: #fff8e1;
            padding: 1.2rem;
            margin: 2rem 0;
            border-left: 5px solid #ffc107;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }
        .audience-alert i {
            color: #ff9800;
            font-size: 1.5rem;
        }
        .audience-alert strong {
            color: var(--primary);
        }
        .dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.645, 0.045, 0.355, 1);
            background: rgba(0, 0, 0, 0.1);
            border-radius: 0 0 6px 6px;
            margin-left: 3.5rem;
            width: calc(100% - 3.5rem);
        }

        .dropdown-content a {
            padding: 0.7rem 1rem;
            font-size: 0.9rem;
            display: block;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }

        .dropdown-content a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 4px;
            width: 4px;
            background: var(--secondary-light);
            border-radius: 50%;
            opacity: 0;
            transition: var(--transition);
        }

        .dropdown-content a:hover {
            color: var(--white);
            padding-left: 1.5rem;
        }

        .dropdown-content a:hover::before {
            opacity: 1;
            left: 0.5rem;
        }

        .dropdown-content a i {
            font-size: 0.8rem;
            margin-right: 0.75rem;
        }

        .nav-item:hover .dropdown-content {
            max-height: 500px;
        }

        .nav-item.has-dropdown .nav-link::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-left: auto;
            font-size: 0.7rem;
            transition: transform 0.3s ease;
            color: rgba(255,255,255,0.5);
        }

        .nav-item.has-dropdown:hover .nav-link::after {
            transform: rotate(180deg);
            color: var(--gold);
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

        /* Amélioration de la timeline */
        .timeline-item {
            display: flex;
            gap: 1rem;
            padding: 0.8rem;
            border-radius: 8px;
            transition: var(--transition);
        }
        .timeline-item:hover {
            background-color: var(--light-gray);
        }
        .timeline-time {
            font-weight: 600;
            color: var(--primary);
            min-width: 60px;
        }
        .timeline-type {
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .timeline-type.rdv {
            background-color: rgba(65, 90, 119, 0.1);
            color: var(--primary-light);
        }
        .timeline-type.proces {
            background-color: rgba(230, 57, 70, 0.1);
            color: var(--accent);
        }
    </style>
</head>
<body>
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
                        <span>To-Do List</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stat.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Statistiques</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="msg.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Messagerie</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="recherche.php" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Rechercher</span>
                    </a>
                    <div class="dropdown-content">
                        <a href="clients.php"><i class="fas fa-user-friends"></i> Clients</a>
                        <a href="membre.php"><i class="fas fa-user-tie"></i> Membre du barreau</a>
                        <a href="staff.php"><i class="fas fa-users"></i> Employé du cabinet</a>
                        <a href="affjud.php"><i class="fas fa-gavel"></i> Affaire judiciaire</a>
                    </div>
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
            <button href="../acceuil/login.php" class="btn-deconnexion">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </button>
        </div>
    </aside>


    <!-- CONTENU PRINCIPAL -->
    <div class="main-content">
        <!-- En-tête -->
        <div class="page-header">
            <h1 class="page-title">Bienvenue Maître</h1>
            <p class="page-description">
                Nous sommes le <strong><?= htmlspecialchars($today->format('d/m/Y')) ?></strong>.
                <br><br>
                <em>« <?= htmlspecialchars(randomCitation()) ?> »</em>
            </p>
        </div>

        <!-- Cartes résumé -->
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <i class="fas fa-calendar-check"></i>
                <h3><?= htmlspecialchars($nbr_rdv) ?></h3>
                <p>Rendez-vous prévus aujourd'hui</p>
            </div>
            <div class="dashboard-card">
                <i class="fas fa-gavel"></i>
                <h3><?= htmlspecialchars($nbr_proces) ?></h3>
                <p>Audiences à plaider</p>
            </div>
            <div class="dashboard-card">
                <i class="fas fa-tasks"></i>
                <h3><?= htmlspecialchars(count($tasks)) ?></h3>
                <p>Tâches à finaliser</p>
            </div>
        </div>

        <!-- 🔔 Alerte audience dans moins de 2h -->
        <?php if ($alerte = audienceDansMoinsDe2h($proces)): ?>
            <div class="audience-alert">
                <i class="fas fa-bell"></i>
                <div>
                    <strong>Alerte : Audience imminente</strong><br>
                    À <strong><?= htmlspecialchars($alerte['heure']) ?></strong> - <?= htmlspecialchars($alerte['titre']) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📅 Timeline RDV/Procès -->
        <section class="tasks-container">
            <h2><i class="fas fa-calendar-day"></i> Planning du jour</h2>
            <?php if (!empty($rdvs) || !empty($proces)): ?>
                <ul style="list-style: none; padding-left: 0;">
                    <?php
                        $timeline = array_merge($rdvs, $proces);
                        usort($timeline, fn($a, $b) => strcmp($a['heure'], $b['heure']));
                        
                        foreach ($timeline as $event):
                            $typeClass = strtolower($event['type']) === 'rdv' ? 'rdv' : 'proces';
                    ?>
                        <li class="timeline-item">
                            <span class="timeline-time"><?= htmlspecialchars(substr($event['heure'], 0, 5)) ?></span>
                            <span class="timeline-type <?= $typeClass ?>"><?= htmlspecialchars($event['type']) ?></span>
                            <span><?= htmlspecialchars($event['titre']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-tasks">Aucun rendez-vous ou audience prévu aujourd'hui</p>
            <?php endif; ?>
        </section>

        <!-- ✅ Liste des tâches -->
        <section class="tasks-container">
            <h2><i class="fas fa-tasks"></i> Tâches du jour</h2>
            <?php if (!empty($tasks)): ?>
                <ul class="task-list">
                    <?php foreach ($tasks as $task): ?>
                        <li class="task-item">
                            <div class="task-info">
                                <h4><?= htmlspecialchars($task['title']) ?></h4>
                                <p><?= htmlspecialchars($task['task_description']) ?></p>
                            </div>
                            <span class="task-status">À faire</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-tasks">Aucune tâche prévue pour aujourd'hui</p>
            <?php endif; ?>
        </section>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-btn" id="menuToggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
    </button>

    <script>
        // Mobile menu toggle amélioré
        document.addEventListener('DOMContentLoaded', () => {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.sidebar');
            
            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('active');
            });

            // Fermer la sidebar en cliquant à l'extérieur
            document.addEventListener('click', (e) => {
                if (!sidebar.contains(e.target) {
                    sidebar.classList.remove('active');
                }
            });

            // Empêcher la propagation du clic dans la sidebar
            sidebar.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>

<?php
// Fermeture propre de la connexion
$pdo = null;
?>