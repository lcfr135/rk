<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = (int) $_SESSION['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$game_id = isset($_POST['game_id']) ? (int) $_POST['game_id'] : (isset($_GET['game_id']) ? (int) $_GET['game_id'] : 0);

if ($game_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid game ID']);
    exit;
}

if ($action === 'toggle') {
    // Cek apakah sudah ada
    $stmt = $conn->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND game_id = ?");
    $stmt->bind_param("ii", $user_id, $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Hapus favorite
        $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND game_id = ?");
        $stmt->bind_param("ii", $user_id, $game_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Tambah favorite
        $stmt = $conn->prepare("INSERT INTO user_favorites (user_id, game_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $game_id);
        $stmt->execute();
        echo json_encode(['success' => true, 'action' => 'added']);
    }
    exit;
}

if ($action === 'list') {
    $stmt = $conn->prepare("SELECT game_id FROM user_favorites WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = (int) $row['game_id'];
    }
    echo json_encode(['success' => true, 'favorites' => $favorites]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>