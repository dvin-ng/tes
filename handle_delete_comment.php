<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (isset($_GET['id']) && isset($_GET['task_id'])) {
    $comment_id = $_GET['id'];
    $task_id = $_GET['task_id'];
    
    // Check if comment exists and user has permission
    $stmt = $pdo->prepare("SELECT author_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    if ($comment) {
        // Check if user has permission to delete this comment
        if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $comment['author_id'] == $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
        }
    }
    
    header("Location: task_detail.php?id=$task_id");
    exit;
}

header("Location: tasks.php");
exit;
?>