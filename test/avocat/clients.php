<?php
// Connexion à la base de données
include 'db.php';

// Récupérer les clients depuis la base de données avec tri alphabétique
$sql = "SELECT * FROM users WHERE role='client' ORDER BY nom, prenom";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clients | JurisHub</title>
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

        /* Clients Grid */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            padding: 10px;
        }

        .client-card {
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
        }

        .client-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }

        .card-header {
            background: var(--primary);
            color: var(--white);
            padding: 15px;
            text-align: center;
            position: relative;
        }

        .card-header h3 {
            font-size: 1.3rem;
            margin: 0;
        }

        .status-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 10px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .card-body {
            padding: 20px;
        }

        .client-info {
            margin-bottom: 15px;
        }

        .client-info p {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }

        .client-info i {
            margin-right: 10px;
            color: var(--primary);
            width: 20px;
            text-align: center;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            font-weight: 600;
            transition: var(--transition);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary-light);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .no-results {
            text-align: center;
            grid-column: 1 / -1;
            padding: 40px;
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* Search and Filter */
        .search-filter-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
        }

        .search-bar {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            border: 2px solid var(--light-gray);
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            transition: var(--transition);
        }

        .search-bar input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(27, 38, 59, 0.2);
        }

        .search-bar i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .filter-select {
            min-width: 250px;
        }

        .filter-select select {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid var(--light-gray);
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            transition: var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 1em;
        }

        .filter-select select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(27, 38, 59, 0.2);
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
            
            .grid-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.8rem;
            }
            
            .search-filter-container {
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
                    <a href="clients.php" class="nav-link active">
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
                    <a href="staff.php" class="nav-link">
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
            <h1 class="page-title">Gestion des Clients</h1>
            <p class="page-description">
                Gérez votre portefeuille clients et leurs informations
            </p>
        </div>
        
        <div class="search-filter-container">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="search" placeholder="Rechercher par nom, prénom ou téléphone..." onkeyup="filterClients()">
            </div>
            
            <div class="filter-select">
                <select id="status-filter" onchange="filterClients()">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                    <option value="prospect">Prospect</option>
                </select>
            </div>
        </div>
        
        <div class="grid-container" id="client-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $full_name = htmlspecialchars($row['prenom'] . ' ' . htmlspecialchars($row['nom']));
                    $status = htmlspecialchars($row['status'] ?? 'Actif');
                    $email = htmlspecialchars($row['email']);
                    $phone = htmlspecialchars($row['tel']);
                    $client_id = htmlspecialchars($row['id']);
                ?>
                    <div class="client-card" 
                         data-name="<?= strtolower($full_name) ?>" 
                         data-phone="<?= strtolower($phone) ?>"
                         data-status="<?= strtolower($status) ?>">
                        <div class="card-header">
                            <h3><?= $full_name ?></h3>
                            <span class="status-badge"><?= $status ?></span>
                        </div>
                        <div class="card-body">
                            <div class="client-info">
                                <p><i class="fas fa-phone"></i> <?= $phone ?></p>
                                <p><i class="fas fa-envelope"></i> <?= $email ?></p>
                                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['adresse'] ?? 'Non renseignée') ?></p>
                            </div>
                            <div class="action-buttons">
                                <a href="clientinfo.php?id=<?= $client_id ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Détails
                                </a>
                                <a href="mailto:<?= $email ?>" class="btn btn-secondary">
                                    <i class="fas fa-envelope"></i> Email
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-users fa-3x" style="color: var(--primary); margin-bottom: 15px;"></i>
                    <p>Aucun client trouvé dans la base de données.</p>
                    <a href="add_client.php" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Ajouter un client
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Action Button -->
    <button class="floating-btn" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <script>
        function filterClients() {
            const searchInput = document.getElementById("search").value.toLowerCase();
            const statusFilter = document.getElementById("status-filter").value.toLowerCase();
            const cards = document.querySelectorAll(".client-card");
            let hasResults = false;
            
            cards.forEach(card => {
                const name = card.getAttribute("data-name");
                const phone = card.getAttribute("data-phone");
                const status = card.getAttribute("data-status");
                
                const matchesSearch = name.includes(searchInput) || phone.includes(searchInput);
                const matchesStatus = statusFilter === "" || status === statusFilter.toLowerCase();
                
                if (matchesSearch && matchesStatus) {
                    card.style.display = "block";
                    hasResults = true;
                } else {
                    card.style.display = "none";
                }
            });
            
            // Show no results message if needed
            const noResults = document.querySelector(".no-results");
            if (noResults) {
                noResults.style.display = hasResults ? "none" : "block";
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
    </script>
</body>
</html>

<?php
// Fermer la connexion à la base de données
$conn->close();
?>