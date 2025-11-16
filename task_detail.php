<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: tasks.php");
    exit;
}

 $task_id = $_GET['id'];

// MODIFIKASI: Get task data tanpa join ke assignee, karena sekarang bisa banyak
 $stmt = $pdo->prepare("SELECT t.*, p.name as project_name, creator.name as creator_name FROM tasks t LEFT JOIN projects p ON t.project_id = p.id LEFT JOIN users creator ON t.created_by_id = creator.id WHERE t.id = ?");
 $stmt->execute([$task_id]);
 $task = $stmt->fetch();

if (!$task) {
    header("Location: tasks.php");
    exit;
}

// MODIFIKASI: Ambil semua nama assignee dari tabel pivot task_assignees
 $stmt = $pdo->prepare("SELECT u.name FROM users u JOIN task_assignees ta ON u.id = ta.user_id WHERE ta.task_id = ?");
 $stmt->execute([$task_id]);
 $assignees = $stmt->fetchAll(PDO::FETCH_COLUMN);
 $task['assignee_names'] = implode(', ', $assignees);

// Get comments for this task
 $stmt = $pdo->prepare("SELECT c.*, u.name as author_name FROM comments c LEFT JOIN users u ON c.author_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
 $stmt->execute([$task_id]);
 $comments = $stmt->fetchAll();

// Get documents for this task
 $stmt = $pdo->prepare("SELECT d.*, u.name as uploader_name FROM documents d LEFT JOIN users u ON d.uploaded_by_id = u.id WHERE d.task_id = ? ORDER BY d.uploaded_at DESC");
 $stmt->execute([$task_id]);
 $documents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $task['title']; ?> - Sistem Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .comment {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-kanban fs-4 me-2"></i>
                        <h5 class="mb-0">ProyekKu</h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-house-door me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="projects.php">
                                <i class="bi bi-folder me-2"></i> Proyek
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="tasks.php">
                                <i class="bi bi-check2-square me-2"></i> Tugas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="documents.php">
                                <i class="bi bi-file-earmark me-2"></i> Dokumen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="teams.php">
                                <i class="bi bi-people me-2"></i> Tim
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] == 'ADMIN'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="bi bi-person-gear me-2"></i> Pengguna
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <hr>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-2"></i>
                            <strong><?php echo $_SESSION['user_name']; ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $task['title']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="tasks.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $task['created_by_id'] == $_SESSION['user_id']): ?>
                            <a href="form_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Task Info -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Detail Tugas</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <strong>Status:</strong>
                                        <span class="badge bg-<?php 
                                            echo match($task['status']) {
                                                'TO_DO' => 'secondary',
                                                'IN_PROGRESS' => 'primary',
                                                'REVIEW' => 'warning',
                                                'DONE' => 'success',
                                                default => 'secondary'
                                            };
                                        ?>"><?php echo $task['status']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Prioritas:</strong>
                                        <span class="badge bg-<?php 
                                            echo match($task['priority']) {
                                                'LOW' => 'success',
                                                'MEDIUM' => 'info',
                                                'HIGH' => 'warning',
                                                'CRITICAL' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>"><?php echo $task['priority']; ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Proyek:</strong> <a href="project_detail.php?id=<?php echo $task['project_id']; ?>"><?php echo $task['project_name']; ?></a>
                                    </div>
                                </div>
                                <p><strong>Deskripsi:</strong> <?php echo nl2br($task['description'] ?? 'Tidak ada deskripsi'); ?></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Dibuat oleh:</strong> <?php echo $task['creator_name']; ?></p>
                                        <!-- MODIFIKASI: Tampilkan nama assignee yang sudah digabung -->
                                        <p><strong>Penanggung Jawab:</strong> <?php echo $task['assignee_names'] ?: 'Tidak ada'; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Tenggat Waktu:</strong> <?php echo $task['due_date'] ? date('d M Y H:i', strtotime($task['due_date'])) : '-'; ?></p>
                                        <p><strong>Perkiraan Waktu:</strong> <?php echo $task['estimated_hours'] ?? '-'; ?> jam</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Dokumen Terkait</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($documents)): ?>
                                    <p>Belum ada dokumen.</p>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($documents as $doc): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="uploads/<?php echo $doc['file_path']; ?>" target="_blank"><?php echo $doc['title']; ?></a>
                                            <a href="uploads/<?php echo $doc['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <a href="form_document.php?task_id=<?php echo $task_id; ?>" class="btn btn-sm btn-primary mt-2">
                                    <i class="bi bi-upload me-1"></i> Unggah Dokumen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Komentar</h5>
                            </div>
                            <div class="card-body">
                                <!-- Add Comment Form -->
                                <form action="handle_add_comment.php" method="post" class="mb-4">
                                    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
                                    <div class="mb-3">
                                        <label for="content" class="form-label">Tambah Komentar</label>
                                        <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Komentar</button>
                                </form>

                                <!-- Display Comments -->
                                <?php if (empty($comments)): ?>
                                    <p>Belum ada komentar.</p>
                                <?php else: ?>
                                    <?php foreach ($comments as $comment): ?>
                                    <div class="comment">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1"><?php echo $comment['author_name']; ?></h6>
                                            <small><?php echo date('d M Y H:i', strtotime($comment['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br($comment['content']); ?></p>
                                        <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $comment['author_id'] == $_SESSION['user_id']): ?>
                                        <div>
                                            <a href="form_comment.php?id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <a href="handle_delete_comment.php?id=<?php echo $comment['id']; ?>&task_id=<?php echo $task_id; ?>" class="btn btn-sm btn-outline-danger">Hapus</a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>