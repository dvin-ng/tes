<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Only Admin can delete teams
if ($_SESSION['user_role'] != 'ADMIN') {
    header("Location: teams.php");
    exit;
}

if (isset($_GET['id'])) {
    $team_id = $_GET['id'];
    
    // Delete team (cascade will handle related records)
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
}

header("Location: teams.php");
exit;
?>