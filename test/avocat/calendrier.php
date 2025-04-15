<?php 
// Connexion à la base de données
$host = 'localhost';
$db = 'jurishub';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

function getEvents($pdo, $date, $filter = 'all') {
    $events = [];
    
    // Rendez-vous
    if ($filter === 'all' || $filter === 'appointment') {
        $stmt = $pdo->prepare("SELECT a.date_time, u.prenom AS client_first_name, u.nom AS client_last_name, 
                                      a.reason AS event_type, 'appointment' AS event_category
                               FROM appointments a
                               JOIN users u ON a.client_id = u.id
                               WHERE DATE(a.date_time) = :date
                               AND a.status != 'cancelled'");
        $stmt->execute(['date' => $date]);
        $appointments = $stmt->fetchAll();
        $events = array_merge($events, $appointments);
    }

    // Procès
    if ($filter === 'all' || $filter === 'hearing') {
        $stmt = $pdo->prepare("SELECT h.hearing_date AS date_time, u.prenom AS client_first_name, u.nom AS client_last_name,
                                      'Audience' AS event_type, 'hearing' AS event_category
                               FROM proces h
                               JOIN users u ON h.client_id = u.id
                               JOIN legal_case lc ON h.legal_case_id = lc.id
                               WHERE DATE(h.hearing_date) = :date");
        $stmt->execute(['date' => $date]);
        $proces = $stmt->fetchAll();
        $events = array_merge($events, $proces);
    }
    
    // Trier par heure
    usort($events, function($a, $b) {
        return strtotime($a['date_time']) <=> strtotime($b['date_time']);
    });
    
    return $events;
}

// Validation de la date et du filtre
$selected_date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'appointment', 'hearing'])) {
    $filter = 'all';
}

// Récupérer les événements pour la date sélectionnée
$events = getEvents($pdo, $selected_date, $filter);

// Obtenir le mois et l'année de la date sélectionnée
$month = date('m', strtotime($selected_date));
$year = date('Y', strtotime($selected_date));

// Calculer le premier jour du mois et le nombre de jours dans le mois
$first_day_of_month = strtotime("$year-$month-01");
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day_of_week = date('w', $first_day_of_month);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier & Agenda | JurisHub</title>
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

        /* Sidebar Navigation */
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

        /* ... (conserver le reste du CSS existant) ... */

        /* Filtres */
        .filter-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            justify-content: center;
        }

        .filter-btn {
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-weight: 500;
            background: var(--light);
            border: 1px solid var(--light-gray);
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .filter-btn:hover {
            background: var(--primary-light);
            color: var(--white);
        }

        /* Styles pour les types d'événements */
        .event-item-type.appointment {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .event-item-type.hearing {
            background: rgba(156, 39, 176, 0.1);
            color: #9C27B0;
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

        /* Calendar Container */
        .calendar-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .calendar-header h2 {
            font-size: 1.5rem;
            color: var(--primary);
            font-weight: 600;
        }

        .month-selector {
            padding: 0.8rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light);
            transition: var(--transition);
        }

        .month-selector:focus {
            outline: none;
            border-color: var(--secondary-light);
            box-shadow: 0 0 0 3px rgba(119, 141, 169, 0.2);
        }

        .calendar-days, .calendar-dates {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .calendar-days div {
            text-align: center;
            font-weight: 600;
            color: var(--primary-light);
            padding: 0.8rem;
            font-size: 0.9rem;
        }

        .day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            background: var(--light);
            position: relative;
        }

        .day:hover {
            background: var(--secondary-light);
            color: var(--white);
            transform: translateY(-3px);
        }

        .day.has-event::after {
            content: '';
            position: absolute;
            bottom: 6px;
            width: 6px;
            height: 6px;
            background: var(--accent);
            border-radius: 50%;
        }

        .day.today {
            background: var(--gold);
            color: var(--white);
            font-weight: 600;
        }

        .day.empty {
            visibility: hidden;
        }

        /* Events Container */
        .events-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .events-container h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            text-align: center;
        }

        .event-list {
            list-style: none;
        }

        .event-item {
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: var(--light);
            border-radius: 8px;
            border-left: 4px solid var(--accent);
            transition: var(--transition);
        }

        .event-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-sm);
        }

        .event-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            font-weight: 600;
        }

        .event-item-time {
            color: var(--accent);
        }

        .event-item-type {
            color: var(--primary-light);
            font-size: 0.9rem;
            background: rgba(119, 141, 169, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
        }

        .event-item-client {
            font-style: italic;
            color: var(--text);
        }

        .no-events {
            text-align: center;
            color: var(--text-light);
            padding: 2rem;
        }

        /* Event Form */
        .event-form-container {
            display: none;
            margin-top: 2rem;
            padding: 2rem;
            background: var(--light);
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .event-form-container h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-family: 'Montserrat', sans-serif;
            transition: var(--transition);
            background-color: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary-light);
            box-shadow: 0 0 0 3px rgba(119, 141, 169, 0.2);
        }

        .btn {
            padding: 0.9rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: var(--white);
            box-shadow: 0 2px 8px rgba(27, 38, 59, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(27, 38, 59, 0.3);
        }

        .btn-accent {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: var(--white);
        }

        .btn-accent:hover {
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
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
            
            .calendar-days, .calendar-dates {
                gap: 0.3rem;
            }
            
            .day {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.8rem;
            }
            
            .calendar-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .event-item {
                padding: 1rem;
            }
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
                    <a href="calendrier.php" class="nav-link active">
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
            <button class="btn-deconnexion">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Agenda Professionnel</h1>
            <p class="page-description">
                Gérez votre emploi du temps et vos rendez-vous avec efficacité
            </p>
        </div>
        
        <div class="calendar-container">
            <div class="calendar-header">
                <h2>Calendrier de <?php echo date('F Y', strtotime($selected_date)); ?></h2>
                <form method="get">
                    <input type="month" name="date" class="month-selector" 
                           value="<?php echo date('Y-m', strtotime($selected_date)); ?>" 
                           onchange="this.form.submit()">
                </form>
            </div>

            <div class="calendar-days">
                <div>Dim</div>
                <div>Lun</div>
                <div>Mar</div>
                <div>Mer</div>
                <div>Jeu</div>
                <div>Ven</div>
                <div>Sam</div>
            </div>

            <div class="calendar-dates">
                <?php
                // Ajouter les jours vides avant le début du mois
                for ($i = 0; $i < $first_day_of_week; $i++) {
                    echo "<div class='day empty'></div>";
                }

                // Afficher les jours du mois
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $current_date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                    $is_today = ($current_date == date('Y-m-d')) ? 'today' : '';
                    $has_event = false;

                    // Vérifier si un événement est associé à ce jour
                    foreach ($events as $event) {
                        $event_date = date('Y-m-d', strtotime($event['date_time']));
                        if ($event_date === $current_date) {
                            $has_event = true;
                            break;
                        }
                    }

                    $event_class = $has_event ? 'has-event' : '';
                    
                    echo "<div class='day $is_today $event_class' 
                          onclick='showEventsForDay(\"$current_date\")'>$day</div>";
                }
                ?>
            </div>
        </div>

        <div class="events-container" id="eventsContainer">
            <div class="filter-container">
                <button class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" 
                        onclick="setFilter('all')">Tous</button>
                <button class="filter-btn <?= $filter === 'appointment' ? 'active' : '' ?>" 
                        onclick="setFilter('appointment')">Rendez-vous</button>
                <button class="filter-btn <?= $filter === 'hearing' ? 'active' : '' ?>" 
                        onclick="setFilter('hearing')">Procès</button>
            </div>
            
            <h3>Événements du <?php echo date('d/m/Y', strtotime($selected_date)); ?></h3>
            
            <?php if (empty($events)): ?>
                <div class="no-events">
                    <i class="fas fa-calendar-times" style="font-size: 2rem; color: var(--secondary); margin-bottom: 1rem;"></i>
                    <p>Aucun événement prévu pour cette date</p>
                </div>
            <?php else: ?>
                <ul class="event-list">
                    <?php foreach ($events as $event): 
                        $time = date('H:i', strtotime($event['date_time']));
                        $client_name = htmlspecialchars($event['client_first_name'] . ' ' . $event['client_last_name']);
                        $event_type = htmlspecialchars($event['event_type']);
                        $event_category = $event['event_category'];
                    ?>
                        <li class="event-item">
                            <div class="event-item-header">
                                <span class="event-item-time"><?php echo $time; ?></span>
                                <span class="event-item-type <?= $event_category ?>"><?php echo $event_type; ?></span>
                            </div>
                            <div class="event-item-client"><?php echo $client_name; ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Afficher les événements pour un jour spécifique
        function showEventsForDay(date) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date', date);
            window.location.href = currentUrl.toString();
        }

        // Appliquer un filtre
        function setFilter(filter) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('filter', filter);
            window.location.href = currentUrl.toString();
        }

        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && e.target !== menuToggle) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>

<?php
// Fermeture de la connexion PDO
$pdo = null;
?>