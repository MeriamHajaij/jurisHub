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

$mois = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$incomes = [];
$expenses = [];

foreach ($mois as $index => $m) {
    // Revenus
    $stmt_income = $pdo->prepare("
        SELECT SUM(amount) 
        FROM payments 
        WHERE status = 'payé' AND MONTH(payment_date) = ? AND YEAR(payment_date) = YEAR(CURDATE())
    ");
    $stmt_income->execute([$index + 1]);
    $incomes[] = $stmt_income->fetchColumn() ?? 0;

    // Dépenses
    $stmt_salary = $pdo->query("SELECT SUM(salary) FROM users");
    $total_salary = $stmt_salary->fetchColumn() ?? 0;

    $stmt_legal = $pdo->prepare("
        SELECT SUM(montant) 
        FROM depense
        WHERE MONTH(date_depense) = ? AND YEAR(date_depense) = YEAR(CURDATE())
    ");
    $stmt_legal->execute([$index + 1]);
    $legal_exp = $stmt_legal->fetchColumn() ?? 0;
    $expenses[] = $total_salary + $legal_exp;
}

// Revenus du mois en cours
$current_month = date('n');
$stmt_month = $pdo->prepare("
    SELECT SUM(amount)
    FROM payments
    WHERE status = 'payé' AND MONTH(payment_date) = ? AND YEAR(payment_date) = YEAR(CURDATE())
");
$stmt_month->execute([$current_month]);
$monthly_revenue = $stmt_month->fetchColumn() ?? 0;

// Revenus de la semaine en cours
$stmt_week = $pdo->query("
    SELECT SUM(amount)
    FROM payments
    WHERE status = 'payé' AND WEEK(payment_date, 1) = WEEK(CURDATE(), 1) AND YEAR(payment_date) = YEAR(CURDATE())
");
$weekly_revenue = $stmt_week->fetchColumn() ?? 0;

// Affaires gagnées ce mois
$stmt_aff_g = $pdo->query("
    SELECT COUNT(*) 
    FROM legal_case l
    WHERE result = 'Gagnée' 
    AND EXISTS (
        SELECT legal_case_id 
        FROM proces p 
        WHERE p.legal_case_id = l.id 
        AND MONTH(p.hearing_date) = MONTH(CURDATE()) 
        AND YEAR(p.hearing_date) = YEAR(CURDATE())
    )
");
$affaires_gagnees = $stmt_aff_g->fetchColumn() ?? 0;

// Total des affaires ce mois
$stmt_tot_aff = $pdo->query(" 
    SELECT COUNT(*) 
    FROM legal_case l
    WHERE EXISTS (
        SELECT legal_case_id 
        FROM proces p 
        WHERE p.legal_case_id = l.id 
        AND MONTH(p.hearing_date) = MONTH(CURDATE()) 
        AND YEAR(p.hearing_date) = YEAR(CURDATE())
    )
");
$total_affaires = $stmt_tot_aff->fetchColumn() ?? 0;
$affaires_perdues = $total_affaires - $affaires_gagnees;

// Statistiques clients
$stmt_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'");
$total_clients = $stmt_clients->fetchColumn() ?? 0;

$stmt_new_clients = $pdo->query("
    SELECT COUNT(*) 
    FROM users 
    WHERE role = 'client' 
    AND MONTH(created_at) = MONTH(CURDATE()) 
    AND YEAR(created_at) = YEAR(CURDATE())
");
$new_clients = $stmt_new_clients->fetchColumn() ?? 0;

// Statistiques employés
$stmt_employees = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'secretaire'");
$total_employees = $stmt_employees->fetchColumn() ?? 0;

$stmt_avocats = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'interne'");
$total_avocats = $stmt_avocats->fetchColumn() ?? 0;

// Statistiques détaillées employés
$stmt_employee_stats = $pdo->query("
    SELECT 
        u.id, 
        CONCAT(u.prenom, ' ', u.nom) as name,
        COUNT(t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'terminee' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN t.status = 'en_cours' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN t.status = 'en_attente' THEN 1 ELSE 0 END) as pending_tasks
    FROM 
        users u
    LEFT JOIN 
        tasks t ON u.id = t.assigned_to
    WHERE 
        u.role IN ('secretaire', 'avocat')
    GROUP BY 
        u.id
");
$employee_stats = $stmt_employee_stats->fetchAll();

// Statistiques des motifs des affaires juridiques
$stmt_case_reasons = $pdo->query("
    SELECT 
        case_type as reason, 
        COUNT(*) as count
    FROM 
        legal_case
    GROUP BY 
        case_type
    ORDER BY 
        count DESC
");
$case_reasons = $stmt_case_reasons->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques | JurisHub</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Stats Container */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stats-card {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border-left: 4px solid var(--gold);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .stats-card h3 {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .stats-card .label {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .chart-card {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .chart-card h3 {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
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
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.8rem;
            }
        }

        /* Additional Styles */
        .stats-card small {
            display: block;
            color: var(--text-light);
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        .chart-card {
            margin-bottom: 3rem;
        }

        .section-title {
            margin-top: 3rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--light-gray);
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
                    <a href="stat.php" class="nav-link active">
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
            <h1 class="page-title">Statistiques du Cabinet</h1>
            <p class="page-description">
                Analyse des performances et indicateurs clés de votre activité
            </p>
        </div>
        
        <!-- Section Finances -->
        <section>
            <h2 class="section-title" style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--primary);">
                <i class="fas fa-coins" style="margin-right: 10px;"></i>Statistiques Financières
            </h2>
            
            <div class="stats-container">
                <div class="stats-card">
                    <h3><i class="fas fa-calendar-week"></i> Revenus Hebdomadaires</h3>
                    <div class="value"><?= number_format($weekly_revenue, 2) ?> TND</div>
                    <div class="label">Semaine en cours</div>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-calendar-alt"></i> Revenus Mensuels</h3>
                    <div class="value"><?= number_format($monthly_revenue, 2) ?> TND</div>
                    <div class="label">Mois de <?= $mois[$current_month - 1] ?></div>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-chart-line"></i> Bénéfice Net</h3>
                    <div class="value"><?= number_format($monthly_revenue - $expenses[$current_month - 1], 2) ?> TND</div>
                    <div class="label">Mois de <?= $mois[$current_month - 1] ?></div>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>Évolution des Revenus et Dépenses</h3>
                <div class="chart-wrapper">
                    <canvas id="financeChart"></canvas>
                </div>
            </div>
        </section>
        
        <!-- Section Affaires -->
        <section>
            <h2 class="section-title" style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--primary);">
                <i class="fas fa-gavel" style="margin-right: 10px;"></i>Statistiques des Affaires
            </h2>
            
            <div class="stats-container">
                <div class="stats-card">
                    <h3><i class="fas fa-trophy"></i> Affaires Gagnées</h3>
                    <div class="value"><?= $affaires_gagnees ?></div>
                    <div class="label">Ce mois-ci</div>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-balance-scale"></i> Total Affaires</h3>
                    <div class="value"><?= $total_affaires ?></div>
                    <div class="label">Ce mois-ci</div>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-percentage"></i> Taux de Réussite</h3>
                    <div class="value"><?= $total_affaires > 0 ? round(($affaires_gagnees / $total_affaires) * 100) : 0 ?>%</div>
                    <div class="label">Ce mois-ci</div>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>Répartition des Affaires</h3>
                <div class="chart-wrapper">
                    <canvas id="affairesChart"></canvas>
                </div>
            </div>
        </section>
        
        <!-- Section Clients -->
        <section>
            <h2 class="section-title" style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--primary);">
                <i class="fas fa-users" style="margin-right: 10px;"></i>Statistiques Clients
            </h2>
            
            <div class="stats-container">
                <div class="stats-card">
                    <h3><i class="fas fa-user-friends"></i> Clients Totaux</h3>
                    <div class="value"><?= $total_clients ?></div>
                    <div class="label">Portefeuille client</div>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-user-plus"></i> Nouveaux Clients</h3>
                    <div class="value"><?= $new_clients ?></div>
                    <div class="label">Ce mois-ci</div>
                </div>
            </div>
        </section>
        
        <!-- Section Employés -->
        <section>
            <h2 class="section-title" style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--primary);">
                <i class="fas fa-user-tie" style="margin-right: 10px;"></i>Statistiques Employés
            </h2>
            
            <div class="stats-container">
                <div class="stats-card">
                    <h3><i class="fas fa-briefcase"></i> Employés Totaux</h3>
                    <div class="value"><?= $total_employees ?></div>
                    <div class="label">Personnel du cabinet</div>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-gavel"></i> Avocats</h3>
                    <div class="value"><?= $total_avocats ?></div>
                    <div class="label">Effectif juridique</div>
                </div>
            </div>
        </section>

        <!-- Section Détails Employés -->
        <section>
            <h2 class="section-title" style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--primary);">
                <i class="fas fa-chart-line" style="margin-right: 10px;"></i>Performance des Employés
            </h2>
            
            <div class="chart-card">
                <h3>Progression Mensuelle des Employés</h3>
                <div class="chart-wrapper">
                    <canvas id="employeeProgressChart"></canvas>
                </div>
            </div>
            
            <div class="stats-container">
                <?php foreach ($employee_stats as $employee): 
                    $completion_rate = $employee['total_tasks'] > 0 ? 
                        round(($employee['completed_tasks'] / $employee['total_tasks']) * 100) : 0;
                    $progress_color = $completion_rate >= 80 ? '4CAF50' : 
                                     ($completion_rate >= 50 ? 'FFC107' : 'F44336');
                ?>
                <div class="stats-card">
                    <h3><i class="fas fa-user"></i> <?= htmlspecialchars($employee['name']) ?></h3>
                    <div class="value" style="color: #<?= $progress_color ?>"><?= $completion_rate ?>%</div>
                    <div class="label">Taux de complétion</div>
                    <div style="margin-top: 1rem;">
                        <small>Tâches complétées: <?= $employee['completed_tasks'] ?></small><br>
                        <small>En cours: <?= $employee['in_progress_tasks'] ?></small><br>
                        <small>En attente: <?= $employee['pending_tasks'] ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section Motifs des Affaires -->
        <section>
            <h2 class="section-title" style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--primary);">
                <i class="fas fa-chart-pie" style="margin-right: 10px;"></i>Répartition des Affaires par Motif
            </h2>
            
            <div class="chart-card">
                <h3>Motifs des Dernières Affaires</h3>
                <div class="chart-wrapper">
                    <canvas id="caseReasonsChart"></canvas>
                </div>
            </div>
        </section>
    </main>

    <!-- Floating Action Button -->
    <button class="floating-btn" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <script>
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

        // Finance Chart
        const mois = <?= json_encode($mois); ?>;
        const incomeData = <?= json_encode($incomes); ?>;
        const expenseData = <?= json_encode($expenses); ?>;

        const financeCtx = document.getElementById('financeChart').getContext('2d');
        new Chart(financeCtx, {
            type: 'line',
            data: {
                labels: mois,
                datasets: [
                    {
                        label: 'Revenus',
                        data: incomeData,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.3,
                        borderWidth: 2,
                        fill: true
                    },
                    {
                        label: 'Dépenses',
                        data: expenseData,
                        borderColor: '#F44336',
                        backgroundColor: 'rgba(244, 67, 54, 0.1)',
                        tension: 0.3,
                        borderWidth: 2,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' TND';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Montant (TND)',
                            font: {
                                size: 14
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                return value + ' TND';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Mois',
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });

        // Affaires Chart
        const affairesCtx = document.getElementById('affairesChart').getContext('2d');
        new Chart(affairesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Affaires Gagnées', 'Autres Affaires'],
                datasets: [{
                    data: [<?= $affaires_gagnees ?>, <?= $affaires_perdues ?>],
                    backgroundColor: ['#4CAF50', '#FFC107'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Employee Progress Chart
        const employeeProgressCtx = document.getElementById('employeeProgressChart').getContext('2d');
        new Chart(employeeProgressCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($employee_stats, 'name')) ?>,
                datasets: [
                    {
                        label: 'Tâches Complétées (%)',
                        data: <?= json_encode(array_map(function($e) { 
                            return $e['total_tasks'] > 0 ? 
                                round(($e['completed_tasks'] / $e['total_tasks']) * 100) : 0; 
                        }, $employee_stats)) ?>,
                        backgroundColor: '#415a77',
                        borderColor: '#1b263b',
                        borderWidth: 1
                    },
                    {
                        label: 'Tâches en Cours',
                        data: <?= json_encode(array_column($employee_stats, 'in_progress_tasks')) ?>,
                        backgroundColor: '#FFC107',
                        borderColor: '#FFA000',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Pourcentage'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Employés',
                            font: {
                                size: 14
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.datasetIndex === 0) {
                                    label += context.parsed.y + '%';
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            },
                            afterLabel: function(context) {
                                if (context.datasetIndex === 0) {
                                    const employee = <?= json_encode($employee_stats) ?>[context.dataIndex];
                                    return `Total tâches: ${employee.total_tasks}\nComplétées: ${employee.completed_tasks}`;
                                }
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });

        // Case Reasons Chart
        const caseReasonsCtx = document.getElementById('caseReasonsChart').getContext('2d');
        new Chart(caseReasonsCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($case_reasons, 'reason')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($case_reasons, 'count')) ?>,
                    backgroundColor: [
                        '#4CAF50', '#2196F3', '#FFC107', '#F44336', 
                        '#9C27B0', '#607D8B', '#00BCD4', '#8BC34A',
                        '#FF5722', '#795548', '#9E9E9E', '#CDDC39'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Répartition par type d\'affaire',
                        font: {
                            size: 16
                        },
                        padding: {
                            top: 10,
                            bottom: 30
                        }
                    }
                },
                layout: {
                    padding: {
                        left: 20,
                        right: 20,
                        top: 20,
                        bottom: 20
                    }
                },
                cutout: '60%',
                radius: '80%'
            }
        });

        // Animation for stats cards when they come into view
        const statsCards = document.querySelectorAll('.stats-card');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        statsCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease-out';
            observer.observe(card);
        });
    </script>
</body>
</html>

<?php
// Fermeture de la connexion PDO
$pdo = null;
?>