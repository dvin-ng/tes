<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $document_id = $_GET['id'];
    
    // Get document info before deleting
    $stmt = $pdo->prepare("SELECT file_path, uploaded_by_id, project_id FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if ($document) {
        // Check if user has permission to delete this document
        if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $document['uploaded_by_id'] == $_SESSION['user_id']) {
            
            // Delete file from server
            $file_path = 'uploads/' . $document['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete record from database
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$document_id]);
            
            // Redirect back
            $redirect_url = "documents.php";
            if ($document['project_id']) {
                $redirect_url = "project_detail.php?id=" . $document['project_id'] . "#documents";
            }
            header("Location: " . $redirect_url);
            exit;
        }
    }
}

header("Location: documents.php");
exit;
?>