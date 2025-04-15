<?php
// Connexion à la base
$pdo = new PDO('mysql:host=localhost;dbname=jurishub', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Récupérer tous les utilisateurs
$stmt = $pdo->query("SELECT id, password FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Pour chaque utilisateur
foreach ($users as $user) {
    $plainPassword = $user['password']; // Supposons que les mots de passe sont en clair
    
    // 3. Hacher le mot de passe
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    // 4. Mettre à jour la base
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->execute([$hashedPassword, $user['id']]);
    
    echo "Mis à jour l'utilisateur ID {$user['id']}<br>";
}

echo "Migration terminée !";