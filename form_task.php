<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

 $task = null;
 $task_assignees = [];
 $is_edit = false;

// Check if editing existing task
if (isset($_GET['id'])) {
    $is_edit = true;
    $task_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        header("Location: tasks.php");
        exit;
    }
    
    // Check if user has permission to edit this task
    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $task['created_by_id'] != $_SESSION['user_id']) {
        header("Location: tasks.php");
        exit;
    }

    // Ambil data assignee dari tabel pivot task_assignees
    $stmt = $pdo->prepare("SELECT user_id FROM task_assignees WHERE task_id = ?");
    $stmt->execute([$task_id]);
    $task_assignees = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $due_date = $_POST['due_date'];
    $estimated_hours = $_POST['estimated_hours'];
    $project_id = $_POST['project_id'];
    // Data dari multi-select dropdown akan diterima sebagai array
    $assignees = $_POST['assignees'] ?? [];
    
    if ($is_edit) {
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, status = ?, due_date = ?, estimated_hours = ?, project_id = ? WHERE id = ?");
        $stmt->execute([$title, $description, $priority, $status, $due_date, $estimated_hours, $project_id, $task_id]);

        // Hapus assignee lama dan tambahkan yang baru
        $stmt = $pdo->prepare("DELETE FROM task_assignees WHERE task_id = ?");
        $stmt->execute([$task_id]);
        
        foreach ($assignees as $assignee_id) {
            $stmt = $pdo->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
            $stmt->execute([$task_id, $assignee_id]);
        }

        header("Location: task_detail.php?id=$task_id");
    } else {
        $created_by_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, priority, status, due_date, estimated_hours, project_id, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $priority, $status, $due_date, $estimated_hours, $project_id, $created_by_id]);
        $new_task_id = $pdo->lastInsertId();

        // Tambahkan assignee ke tabel pivot
        foreach ($assignees as $assignee_id) {
            $stmt = $pdo->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
            $stmt->execute([$new_task_id, $assignee_id]);
        }

        header("Location: task_detail.php?id=$new_task_id");
    }
    exit;
}

// Get projects for dropdown
 $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY name");
 $projects = $stmt->fetchAll();

// Get users for assignee dropdown
 $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
 $users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Tugas' : 'Tugas Baru'; ?> - Sistem Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- PERUBAHAN: Tambahkan CSS Tom Select -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit Tugas' : 'Tugas Baru'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="tasks.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="form_task.php<?php echo $is_edit ? '?id=' . $task['id'] : ''; ?>" method="post">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Judul Tugas</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $task['title'] ?? ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Deskripsi Tugas</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $task['description'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="project_id" class="form-label">Proyek Terkait</label>
                                            <select class="form-select" id="project_id" name="project_id" required>
                                                <option value="">Pilih Proyek</option>
                                                <?php foreach ($projects as $project): ?>
                                                    <option value="<?php echo $project['id']; ?>" <?php echo ($task['project_id'] ?? '') == $project['id'] ? 'selected' : ''; ?>>
                                                        <?php echo $project['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="assignees-select" class="form-label">Penanggung Jawab</label>
                                            <!-- PERUBAHAN: Ganti dropdown lama dengan select multiple untuk Tom Select -->
                                            <select id="assignees-select" name="assignees[]" multiple placeholder="Pilih Penanggung Jawab...">
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $task_assignees) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="priority" class="form-label">Prioritas</label>
                                            <select class="form-select" id="priority" name="priority" required>
                                                <option value="LOW" <?php echo ($task['priority'] ?? '') == 'LOW' ? 'selected' : ''; ?>>Rendah</option>
                                                <option value="MEDIUM" <?php echo ($task['priority'] ?? '') == 'MEDIUM' ? 'selected' : ''; ?>>Sedang</option>
                                                <option value="HIGH" <?php echo ($task['priority'] ?? '') == 'HIGH' ? 'selected' : ''; ?>>Tinggi</option>
                                                <option value="CRITICAL" <?php echo ($task['priority'] ?? '') == 'CRITICAL' ? 'selected' : ''; ?>>Kritis</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="TO_DO" <?php echo ($task['status'] ?? '') == 'TO_DO' ? 'selected' : ''; ?>>To-Do</option>
                                                <option value="IN_PROGRESS" <?php echo ($task['status'] ?? '') == 'IN_PROGRESS' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="REVIEW" <?php echo ($task['status'] ?? '') == 'REVIEW' ? 'selected' : ''; ?>>Review</option>
                                                <option value="DONE" <?php echo ($task['status'] ?? '') == 'DONE' ? 'selected' : ''; ?>>Done</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="due_date" class="form-label">Tenggat Waktu</label>
                                            <input type="datetime-local" class="form-control" id="due_date" name="due_date" value="<?php echo $task['due_date'] ? date('Y-m-d\TH:i', strtotime($task['due_date'])) : ''; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="estimated_hours" class="form-label">Perkiraan Waktu (Jam)</label>
                                            <input type="number" step="0.5" class="form-control" id="estimated_hours" name="estimated_hours" value="<?php echo $task['estimated_hours'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="tasks.php" class="btn btn-secondary me-2">Batal</a>
                                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Simpan Perubahan' : 'Buat Tugas'; ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- PERUBAHAN: Tambahkan JS Tom Select -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Tom Select untuk dropdown penanggung jawab
            new TomSelect('#assignees-select', {
                plugins: {
                    'checkbox_options': {},
                    'remove_button':{
                        'title':'Hapus item ini',
                    }
                },
                create: false,
                maxItems: null
            });
        });
    </script>
</body>
</html>