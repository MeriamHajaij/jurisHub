<?php
include 'db.php';
$sql = "SELECT * FROM users WHERE role IN ('avocat', 'secretaire', 'interne', 'ex-secretaire') ORDER BY role, nom";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Employés | JurisHub</title>
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
            position: relative;
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

        /* Search and Filter */
        .search-filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 3rem;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .search-bar {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 1rem 1.5rem 1rem 3rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            transition: var(--transition);
            font-size: 0.95rem;
            background-color: var(--light);
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--secondary-light);
            box-shadow: 0 0 0 3px rgba(119, 141, 169, 0.2);
            background-color: var(--white);
        }

        .search-bar i {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1rem;
        }

        /* Employees Grid */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 2rem;
        }

        .employee-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.03);
            position: relative;
        }

        .employee-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--gold), var(--accent));
            z-index: 2;
        }

        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            padding: 1.8rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }

        .card-header h3 {
            font-size: 1.4rem;
            margin-bottom: 0.8rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .card-body {
            padding: 2rem;
        }

        .employee-info {
            margin-bottom: 1.5rem;
        }

        .employee-info p {
            margin-bottom: 1.2rem;
            display: flex;
            align-items: flex-start;
            font-size: 0.95rem;
            color: var(--text);
        }

        .employee-info i {
            margin-right: 1.2rem;
            color: var(--primary-light);
            min-width: 20px;
            text-align: center;
            font-size: 1rem;
            margin-top: 3px;
        }

        .role-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .avocat {
            background-color: rgba(212, 237, 218, 0.3);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .secretaire {
            background-color: rgba(204, 229, 255, 0.3);
            color: #004085;
            border: 1px solid #b8daff;
        }

        .interne {
            background-color: rgba(255, 243, 205, 0.3);
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .ex-secretaire {
            background-color: rgba(248, 215, 218, 0.3);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            flex: 1;
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

        .btn-secondary {
            background: var(--light-gray);
            color: var(--text);
        }

        .btn-secondary:hover {
            background: #e0e3e7;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(108, 117, 125, 0.1);
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px dashed rgba(0, 0, 0, 0.08);
        }

        .no-results i {
            font-size: 3.5rem;
            color: var(--secondary);
            margin-bottom: 1.5rem;
            opacity: 0.6;
        }

        .no-results p {
            font-size: 1.1rem;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { 
                opacity: 0; 
                transform: translateY(20px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }

        .employee-card {
            animation: fadeInUp 0.6s cubic-bezier(0.23, 1, 0.32, 1) forwards;
            opacity: 0;
        }

        .employee-card:nth-child(1) { animation-delay: 0.1s; }
        .employee-card:nth-child(2) { animation-delay: 0.2s; }
        .employee-card:nth-child(3) { animation-delay: 0.3s; }
        .employee-card:nth-child(4) { animation-delay: 0.4s; }
        .employee-card:nth-child(5) { animation-delay: 0.5s; }
        .employee-card:nth-child(6) { animation-delay: 0.6s; }
        .employee-card:nth-child(7) { animation-delay: 0.7s; }
        .employee-card:nth-child(8) { animation-delay: 0.8s; }

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
            
            .grid-container {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
            
            .search-filter-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-bar {
                min-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .grid-container {
                grid-template-columns: 1fr;
            }
            
            .employee-card {
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                margin-bottom: 2rem;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
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
                    <a href="clients.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Clients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="membre.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Membre du barreau</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="staff.php" class="nav-link active">
                        <i class="fas fa-users"></i>
                        <span>Employé du cabinet</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="affjud.php" class="nav-link">
                        <i class="fa-solid fa-file"></i>
                        <span>Affaire judiciaire</span>
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
            <h1 class="page-title">Liste des Employés</h1>
            <p class="page-description">
                Consultez la liste complète des membres de votre cabinet. Trouvez facilement vos collaborateurs par rôle ou spécialité.
            </p>
        </div>
        
        <div class="search-filter-container">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="search" placeholder="Rechercher par nom, prénom, rôle ou téléphone..." onkeyup="filterEmployees()">
            </div>
        </div>
        
        <div class="grid-container" id="employee-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $full_name = htmlspecialchars($row['prenom'] . ' ' . $row['nom']);
                    $role_class = str_replace('-', '', $row['role']);
                ?>
                    <div class="employee-card" 
                         data-name="<?= strtolower($full_name) ?>" 
                         data-phone="<?= htmlspecialchars($row['tel']) ?>" 
                         data-role="<?= htmlspecialchars($row['role']) ?>">
                        <div class="card-header">
                            <h3><?= $full_name ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="employee-info">
                                <p><i class="fas fa-briefcase"></i> <span class="role-badge <?= $role_class ?>"><?= ucfirst(htmlspecialchars($row['role'])) ?></span></p>
                                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($row['tel']) ?></p>
                                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($row['email']) ?></p>
                                <?php if (!empty($row['specialite'])): ?>
                                <p><i class="fas fa-star"></i> <?= htmlspecialchars($row['specialite']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="action-buttons">
                                <a href="employeeinfo.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Détails
                                </a>
                                <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="btn btn-secondary">
                                    <i class="fas fa-envelope"></i> Email
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-user-slash"></i>
                    <p>Aucun employé trouvé dans la base de données.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Action Button -->
    <button class="floating-btn" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <script>
        function filterEmployees() {
            const input = document.getElementById("search").value.toLowerCase();
            const cards = document.querySelectorAll(".employee-card");
            let hasResults = false;
            
            cards.forEach(card => {
                const name = card.getAttribute("data-name");
                const phone = card.getAttribute("data-phone").toLowerCase();
                const role = card.getAttribute("data-role").toLowerCase();
                
                if (name.includes(input) || phone.includes(input) || role.includes(input)) {
                    card.style.display = "block";
                    hasResults = true;
                } else {
                    card.style.display = "none";
                }
            });
            
            const noResults = document.querySelector(".no-results");
            if (noResults) {
                noResults.style.display = hasResults ? "none" : "flex";
            }
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

        // Animation for cards
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.employee-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${0.1 * (index % 8)}s`;
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>