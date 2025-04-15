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

// Gestion des actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    if ($_POST["action"] === "add" && !empty($_POST["task"])) {
        $stmt = $pdo->prepare("INSERT INTO tasks (task_description, due_date) VALUES (?, ?)");
        $stmt->execute([$_POST["task"], $_POST["due_date"] ?: null]);
    } elseif ($_POST["action"] === "delete") {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    } elseif ($_POST["action"] === "toggle") {
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'en_attente' WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    }
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// Récupération des tâches
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY due_date ASC");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List | JurisHub</title>
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

        /* Todo List Styles */
        .todo-container {
            background: var(--white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.03);
            max-width: 800px;
            margin: 0 auto;
        }

        .todo-form {
            display: flex;
            gap: 10px;
            margin-bottom: 2rem;
        }

        .todo-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .todo-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(27, 38, 59, 0.2);
        }

        .todo-date {
            padding: 12px 15px;
            border: 2px solid var(--light-gray);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
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

        .task-list {
            list-style: none;
            padding: 0;
        }

        .task-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .task-item:hover {
            background-color: rgba(119, 141, 169, 0.05);
        }

        .task-content {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .task-checkbox {
            margin-right: 15px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .task-text {
            flex: 1;
            font-size: 1rem;
        }

        .task-text.completed {
            text-decoration: line-through;
            color: var(--text-light);
        }

        .task-deadline {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-left: 15px;
            font-style: italic;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .task-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            transition: var(--transition);
            padding: 5px;
            border-radius: 4px;
        }

        .task-btn.check {
            color: #28a745;
        }

        .task-btn.check:hover {
            background: rgba(40, 167, 69, 0.1);
        }

        .task-btn.delete {
            color: var(--accent);
        }

        .task-btn.delete:hover {
            background: rgba(230, 57, 70, 0.1);
        }

        .no-tasks {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
            font-size: 1.1rem;
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
            
            .todo-form {
                flex-direction: column;
            }
            
            .task-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .task-actions {
                align-self: flex-end;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation Luxe -->
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
                    <a href="tdl.php" class="nav-link active">
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
            <button class="btn-deconnexion">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">To-Do List</h1>
            <p class="page-description">
                Gérez vos tâches quotidiennes et vos échéances professionnelles
            </p>
        </div>
        
        <div class="todo-container">
            <form method="POST" class="todo-form">
                <input type="text" name="task" class="todo-input" placeholder="Ajouter une nouvelle tâche..." required>
                <input type="date" name="deadline" class="todo-date">
                <input type="hidden" name="action" value="add">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </form>
            
            <ul class="task-list">
                <?php if ($tasks): ?>
                    <?php foreach ($tasks as $task): ?>
                        <li class="task-item">
                            <div class="task-content">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="task-btn check" title="Marquer comme complétée">
                                        <i class="fas <?= $task['status'] ? 'fa-check-circle' : 'fa-circle' ?>"></i>
                                    </button>
                                </form>
                                <span class="task-text <?= $task['status'] ? 'completed' : '' ?>">
                                    <?= htmlspecialchars($task['task_description']) ?>
                                </span>
                                <?php if ($task['due_date']): ?>
                                    <span class="task-deadline">
                                        <i class="far fa-calendar-alt"></i> <?= $task['due_date'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="task-btn delete" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-tasks">
                        <i class="far fa-check-circle fa-3x" style="color: var(--primary); margin-bottom: 15px;"></i>
                        <p>Aucune tâche à afficher. Ajoutez votre première tâche !</p>
                    </div>
                <?php endif; ?>
            </ul>
        </div>
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
    </script>
</body>
</html>

<?php
// Fermeture de la connexion PDO
$pdo = null;
?>