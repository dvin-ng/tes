<?php
// auth_check.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user data
require_once 'db_connect.php';
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Update session data
 $_SESSION['user_name'] = $user['name'];
 $_SESSION['user_role'] = $user['role'];
?>