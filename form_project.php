<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Check if user has permission to create/edit project
if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: dashboard.php");
    exit;
}

 $project = null;
 $project_members = [];
 $is_edit = false;

// Check if editing existing project
if (isset($_GET['id'])) {
    $is_edit = true;
    $project_id = $_GET['id'];
    
    // Get project data
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: projects.php");
        exit;
    }
    
    // Check if user has permission to edit this project
    if ($_SESSION['user_role'] != 'ADMIN' && $project['manager_id'] != $_SESSION['user_id']) {
        header("Location: projects.php");
        exit;
    }
    
    // Get project members
    $stmt = $pdo->prepare("SELECT user_id FROM project_members WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project_members = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $manager_id = $_POST['manager_id'];
    // Data dari multi-select dropdown akan diterima sebagai array
    $members = $_POST['members'] ?? [];
    
    if ($is_edit) {
        // Update project
        $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, start_date = ?, end_date = ?, status = ?, priority = ?, manager_id = ? WHERE id = ?");
        $stmt->execute([$name, $description, $start_date, $end_date, $status, $priority, $manager_id, $project_id]);
        
        // Update project members (remove all and add new ones)
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        foreach ($members as $member_id) {
            $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id) VALUES (?, ?)");
            $stmt->execute([$project_id, $member_id]);
        }
        
        header("Location: project_detail.php?id=$project_id");
    } else {
        // Create new project
        $stmt = $pdo->prepare("INSERT INTO projects (name, description, start_date, end_date, status, priority, manager_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $start_date, $end_date, $status, $priority, $manager_id]);
        
        $project_id = $pdo->lastInsertId();
        
        // Add project members
        foreach ($members as $member_id) {
            $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id) VALUES (?, ?)");
            $stmt->execute([$project_id, $member_id]);
        }
        
        header("Location: project_detail.php?id=$project_id");
    }
    exit;
}

// Get all users for member selection
 $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
 $users = $stmt->fetchAll();

// Get managers for dropdown
 $stmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('ADMIN', 'MANAGER') ORDER BY name");
 $managers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Proyek' : 'Proyek Baru'; ?> - Sistem Manajemen Proyek</title>
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
                            <a class="nav-link active" href="projects.php">
                                <i class="bi bi-folder me-2"></i> Proyek
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tasks.php">
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit Proyek' : 'Proyek Baru'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="projects.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="form_project.php<?php echo $is_edit ? '?id=' . $project['id'] : ''; ?>" method="post">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nama Proyek</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $project['name'] ?? ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Deskripsi Proyek</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $project['description'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $project['start_date'] ?? ''; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="end_date" class="form-label">Tanggal Selesai</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $project['end_date'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">Status Proyek</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="PLANNING" <?php echo ($project['status'] ?? '') == 'PLANNING' ? 'selected' : ''; ?>>Perencanaan</option>
                                                <option value="ACTIVE" <?php echo ($project['status'] ?? '') == 'ACTIVE' ? 'selected' : ''; ?>>Aktif</option>
                                                <option value="ON_HOLD" <?php echo ($project['status'] ?? '') == 'ON_HOLD' ? 'selected' : ''; ?>>Ditunda</option>
                                                <option value="COMPLETED" <?php echo ($project['status'] ?? '') == 'COMPLETED' ? 'selected' : ''; ?>>Selesai</option>
                                                <option value="CANCELLED" <?php echo ($project['status'] ?? '') == 'CANCELLED' ? 'selected' : ''; ?>>Dibatalkan</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="priority" class="form-label">Prioritas</label>
                                            <select class="form-select" id="priority" name="priority" required>
                                                <option value="LOW" <?php echo ($project['priority'] ?? '') == 'LOW' ? 'selected' : ''; ?>>Rendah</option>
                                                <option value="MEDIUM" <?php echo ($project['priority'] ?? '') == 'MEDIUM' ? 'selected' : ''; ?>>Sedang</option>
                                                <option value="HIGH" <?php echo ($project['priority'] ?? '') == 'HIGH' ? 'selected' : ''; ?>>Tinggi</option>
                                                <option value="CRITICAL" <?php echo ($project['priority'] ?? '') == 'CRITICAL' ? 'selected' : ''; ?>>Kritis</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="manager_id" class="form-label">Manajer Proyek</label>
                                        <select class="form-select" id="manager_id" name="manager_id" required>
                                            <option value="">Pilih Manajer</option>
                                            <?php foreach ($managers as $manager): ?>
                                                <option value="<?php echo $manager['id']; ?>" <?php echo ($project['manager_id'] ?? '') == $manager['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $manager['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="project-members-select" class="form-label">Anggota Tim</label>
                                        <!-- PERUBAHAN: Ganti select multiple lama dengan select baru untuk Tom Select -->
                                        <select id="project-members-select" name="members[]" multiple placeholder="Pilih Anggota Tim...">
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $project_members) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="projects.php" class="btn btn-secondary me-2">Batal</a>
                                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Simpan Perubahan' : 'Buat Proyek'; ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Tips</h6>
                            </div>
                            <div class="card-body">
                                <ul>
                                    <li>Berikan nama yang jelas dan deskriptif untuk proyek Anda</li>
                                    <li>Tentukan tanggal mulai dan selesai yang realistis</li>
                                    <li>Pilih manajer proyek yang bertanggung jawab</li>
                                    <li>Tambahkan anggota tim yang relevan dengan proyek</li>
                                    <li>Perbarui status proyek secara berkala</li>
                                </ul>
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
            // Inisialisasi Tom Select untuk dropdown anggota proyek
            new TomSelect('#project-members-select', {
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