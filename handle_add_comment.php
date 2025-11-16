<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST['content'];
    $task_id = $_POST['task_id'];
    $author_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO comments (content, task_id, author_id) VALUES (?, ?, ?)");
    $stmt->execute([$content, $task_id, $author_id]);
    
    header("Location: task_detail.php?id=$task_id");
    exit;
}

header("Location: tasks.php");
exit;
?>