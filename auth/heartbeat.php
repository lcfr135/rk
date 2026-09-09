<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION['id'])) {
    exit;
}

$user_id = $_SESSION['id'];

$stmt = $conn->prepare("
    UPDATE users
    SET last_seen = NOW()
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();