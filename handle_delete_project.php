<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Check if user has permission to delete project
if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: projects.php");
    exit;
}

if (isset($_GET['id'])) {
    $project_id = $_GET['id'];
    
    // Check if project exists and user has permission
    $stmt = $pdo->prepare("SELECT manager_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: projects.php");
        exit;
    }
    
    // Check if user has permission to delete this project
    if ($_SESSION['user_role'] != 'ADMIN' && $project['manager_id'] != $_SESSION['user_id']) {
        header("Location: projects.php");
        exit;
    }
    
    // Delete project (cascade will handle related records)
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
}

header("Location: projects.php");
exit;
?>