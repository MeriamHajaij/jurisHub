<?php
session_start();

// Configuration identique à msg.php
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

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
} catch (\PDOException $e) {
    die(json_encode(['error' => 'Database connection failed']));
}

$currentUserId = $_GET['currentUserId'] ?? null;
$otherUserId = $_GET['otherUserId'] ?? null;

if (!$currentUserId || !$otherUserId) {
    die(json_encode(['error' => 'Missing parameters']));
}

try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom, u.prenom 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$currentUserId, $otherUserId, $otherUserId, $currentUserId]);
    $messages = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    echo json_encode($messages);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database error']));
}
?>