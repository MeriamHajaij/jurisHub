<?php
// Définition des métadonnées dynamiques
$page_title = "Cabinet d'Avocate Ghofrane Hajaïj | Expertise Juridique à Tunis";
$page_description = "Cabinet d'avocate Ghofrane Hajaïj - Expertise juridique en droit de la famille, droit pénal, droit du travail et droit des affaires. Gestion optimisée avec JurisHub.";
$page_keywords = "avocat, droit, juridique, cabinet, Ghofrane Hajaïj, conseil juridique, Tunisie";
$author = "Cabinet Ghofrane Hajaïj";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($author); ?>">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../acceuil/styles.css">
</head>
<body>
    <!-- Navigation -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo" aria-label="JurisHub - Retour à l'accueil">JurisHub</a>
                
                <button class="menu-toggle" aria-expanded="false" aria-label="Menu mobile">
                    <span class="hamburger"></span>
                </button>
                
                <ul class="nav-links">
                    <li><a href="index.php" class="nav-link active">Accueil</a></li>
                    <li><a href="index.php#services" class="nav-link">Services</a></li>
                    <li><a href="index.php#about" class="nav-link">À propos</a></li>
                    <li><a href="index.php#features" class="nav-link">Fonctionnalités</a></li>
                    <li><a href="contact.php" class="nav-link">Contact</a></li>
                </ul>
                
                <a href="../acceuil/login/login.php" class="btn-connexion">
                    <i class="fas fa-user"></i> Connexion
                </a>
            </nav>
        </div>
    </header>

    <!-- Section Hero -->
    <section class="hero">
        <div class="hero-overlay">
            <div class="container">
                <div class="hero-content">
                    <h1>Cabinet d'Avocat <span>Ghofrane Hajaïj</span></h1>
                    <p class="hero-subtitle">Expertise juridique alliée à une gestion moderne pour une défense optimale de vos droits</p>
                    <div class="hero-buttons">
                        <a href="#contact" class="cta-button primary">
                            <i class="fas fa-calendar-alt"></i> Prendre rendez-vous
                        </a>
                        <a href="#services" class="cta-button secondary">
                            <i class="fas fa-gavel"></i> Nos services
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Services -->
    <section id="services" class="section services">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">Domaines d'expertise</span>
                <h2 class="section-title">Nos services juridiques</h2>
                <p class="section-description">Un accompagnement personnalisé dans tous les domaines du droit avec professionnalisme et confidentialité.</p>
            </div>
            
            <div class="services-grid">
                <?php
                // Tableau des services (pourrait venir d'une base de données)
                $services = [
                    [
                        'icon' => 'fas fa-balance-scale',
                        'title' => 'Consultation juridique',
                        'description' => 'Analyse de votre situation et orientation dans vos démarches légales pour une stratégie optimale.'
                    ],
                    [
                        'icon' => 'fas fa-home',
                        'title' => 'Droit de la famille',
                        'description' => 'Divorce, garde d\'enfants, pension alimentaire, adoption avec une approche humaine et respectueuse.'
                    ],
                    [
                        'icon' => 'fas fa-handcuffs',
                        'title' => 'Droit pénal',
                        'description' => 'Défense des droits des victimes et des accusés devant toutes les juridictions pénales.'
                    ],
                    [
                        'icon' => 'fas fa-briefcase',
                        'title' => 'Droit du travail',
                        'description' => 'Rédaction de contrats, résolution des litiges employeur-employé et conseil en RH.'
                    ],
                    [
                        'icon' => 'fas fa-building',
                        'title' => 'Droit des affaires',
                        'description' => 'Création d\'entreprise, contrats commerciaux, contentieux et conseil aux entreprises.'
                    ],
                    [
                        'icon' => 'fas fa-handshake',
                        'title' => 'Médiation et arbitrage',
                        'description' => 'Résolution amiable des conflits pour éviter les procès et préserver les relations.'
                    ]
                ];

                // Affichage dynamique des services
                foreach ($services as $service) {
                    echo '
                    <article class="service-card">
                        <div class="service-icon">
                            <i class="' . htmlspecialchars($service['icon']) . '"></i>
                        </div>
                        <h3>' . htmlspecialchars($service['title']) . '</h3>
                        <p>' . htmlspecialchars($service['description']) . '</p>
                    </article>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Section À propos -->
    <section id="about" class="section about">
        <div class="container">
            <div class="about-grid">
                <div class="about-content">
                    <span class="section-subtitle">Notre cabinet</span>
                    <h2 class="section-title">Maître Ghofrane Hajaïj</h2>
                    <p>Avocate à la cour de cassation inscrite au barreau de Tunis, je mets mon expertise et mon engagement à votre service pour défendre vos droits et intérêts avec rigueur et détermination.</p>
                    
                    <ul class="about-list">
                        <?php
                        $about_points = [
                            "26 ans d'expérience",
                            "Approche personnalisée pour chaque client",
                            "Suivi rigoureux de vos dossiers",
                            "Honoraires transparents et adaptés"
                        ];

                        foreach ($about_points as $point) {
                            echo '
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>' . htmlspecialchars($point) . '</span>
                            </li>';
                        }
                        ?>
                    </ul>
                    
                    <a href="#contact" class="cta-button">
                        <i class="fas fa-envelope"></i> Nous contacter
                    </a>
                </div>
                
                <div class="about-image">
                    <img src="avocate.jpg" alt="Maître Ghofrane Hajaïj, avocate à Tunis" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <!-- Section Fonctionnalités -->
    <section id="features" class="section features">
        <div class="container">
            <div class="section-header">
                <span class="section-subtitle">Notre plateforme</span>
                <h2 class="section-title">JurisHub</h2>
                <p class="section-description">Une gestion optimisée de votre cabinet juridique pour plus d'efficacité et de transparence.</p>
            </div>
            
            <div class="features-grid">
                <?php
                $features = [
                    [
                        'icon' => 'fas fa-calendar-check',
                        'title' => 'Prise de rendez-vous en ligne',
                        'description' => 'Réservez vos consultations en quelques clics, 24h/24, avec confirmation immédiate.'
                    ],
                    [
                        'icon' => 'fas fa-clock',
                        'title' => 'Gestion d\'agenda intelligente',
                        'description' => 'Organisation optimale des consultations avec rappels automatiques.'
                    ],
                    [
                        'icon' => 'fas fa-folder-open',
                        'title' => 'Suivi des dossiers',
                        'description' => 'Accès sécurisé à l\'évolution de vos affaires avec notifications en temps réel.'
                    ],
                    [
                        'icon' => 'fas fa-file-contract',
                        'title' => 'Documents sécurisés',
                        'description' => 'Stockage et partage de documents juridiques avec cryptage avancé.'
                    ],
                    [
                        'icon' => 'fas fa-comments',
                        'title' => 'Messagerie professionnelle',
                        'description' => 'Échange sécurisé avec votre avocate via notre plateforme.'
                    ],
                    [
                        'icon' => 'fas fa-chart-line',
                        'title' => 'Tableau de bord',
                        'description' => 'Visualisation claire de l\'avancement de vos dossiers et rendez-vous.'
                    ]
                ];

                foreach ($features as $feature) {
                    echo '
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="' . htmlspecialchars($feature['icon']) . '"></i>
                        </div>
                        <h3>' . htmlspecialchars($feature['title']) . '</h3>
                        <p>' . htmlspecialchars($feature['description']) . '</p>
                    </div>';
                }
                ?>
            </div>
            
            <div class="features-cta">
                <a href="../acceuil/login/signup.php" class="cta-button primary">
                    <i class="fas fa-user-plus"></i> Créer un compte
                </a>
                <a href="#demo" class="cta-button secondary">
                    <i class="fas fa-play-circle"></i> Voir la démo
                </a>
            </div>
        </div>
    </section>

    <!-- Section Contact -->
    <section id="contact" class="section contact">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info">
                    <span class="section-subtitle">Nous contacter</span>
                    <h2 class="section-title">Prenez rendez-vous</h2>
                    
                    <?php
                    $contact_info = [
                        [
                            'icon' => 'fas fa-map-marker-alt',
                            'title' => 'Adresse',
                            'content' => 'Av. Kowaeit Immeuble Tour Bleue 2 eme etage , Hammamet'
                        ],
                        [
                            'icon' => 'fas fa-phone-alt',
                            'title' => 'Téléphone',
                            'content' => '+216 72 282 755'
                        ],
                        [
                            'icon' => 'fas fa-envelope',
                            'title' => 'Email',
                            'content' => 'maitre.ghofrane.hajaij@gmail.com'
                        ]
                    ];

                    foreach ($contact_info as $info) {
                        echo '
                        <div class="contact-method">
                            <i class="' . htmlspecialchars($info['icon']) . '"></i>
                            <div>
                                <h4>' . htmlspecialchars($info['title']) . '</h4>
                                <p>' . htmlspecialchars($info['content']) . '</p>
                            </div>
                        </div>';
                    }
                    ?>
                    
                    <div class="contact-social">
                        <a href="https://www.facebook.com/ghofrane.hajaij/" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="https://www.instagram.com/ghofrane_hjaiej" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="contact-form">
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3211.0812938861886!2d10.610104299999998!3d36.4072284!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12fd611038b1e4f9%3A0xf2d50d6e19c30cca!2sTours%20bleus!5e0!3m2!1sfr!2stn!4v1744136031282!5m2!1sfr!2stn" 
                            width="100%" 
                            height="300" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy"
                            aria-label="Carte du cabinet"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>JurisHub</h3>
                    <p>La solution de gestion moderne pour les cabinets d'avocats, alliant efficacité et simplicité.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="../acceuil/index.php">Accueil</a></li>
                        <li><a href="../acceuil/index.php#services">Services</a></li>
                        <li><a href="../acceuil/index.php#about">À propos</a></li>
                        <li><a href="../acceuil/index.php#features">Fonctionnalités</a></li>
                        <li><a href="../contact/contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Services</h4>
                    <ul>
                        <?php
                        // Affichage des services dans le footer
                        foreach (array_slice($services, 0, 5) as $service) {
                            echo '<li><a href="#">' . htmlspecialchars($service['title']) . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <?php
                        // Affichage des informations de contact dans le footer
                        foreach ($contact_info as $info) {
                            echo '<li><i class="' . htmlspecialchars($info['icon']) . '"></i> ' . htmlspecialchars($info['content']) . '</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Cabinet d'Avocate Ghofrane Hajaïj. Tous droits réservés.</p>
                <div class="legal-links">
                    <a href="#">Mentions légales</a>
                    <a href="#">Politique de confidentialité</a>
                    <a href="#">CGU</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>