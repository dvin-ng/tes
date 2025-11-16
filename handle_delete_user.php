<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Only Admin can delete users
if ($_SESSION['user_role'] != 'ADMIN') {
    header("Location: dashboard.php");
    exit;
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        header("Location: users.php");
        exit;
    }
    
    // In a real application, you would also need to handle reassigning projects/tasks owned by this user
    // For simplicity, we'll just delete the user. Foreign key constraints might prevent this.
    // You might want to set foreign keys to ON DELETE SET NULL for manager_id, assignee_id, etc.
    
    // For now, let's just prevent deletion if the user is a manager of a project or creator of a task
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM projects WHERE manager_id = ?");
    $stmt->execute([$user_id]);
    $manages_projects = $stmt->fetch()['count'] > 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE created_by_id = ?");
    $stmt->execute([$user_id]);
    $created_tasks = $stmt->fetch()['count'] > 0;

    if ($manages_projects || $created_tasks) {
        // You could show a more specific error message here
        // For now, we'll just redirect back
        header("Location: users.php?error=cannot_delete");
        exit;
    }

    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
}

header("Location: users.php");
exit;
?>