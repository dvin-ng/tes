<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Get projects count
 $stmt = $pdo->query("SELECT COUNT(*) as total FROM projects");
 $projects_count = $stmt->fetch()['total'];

// Get tasks count
 $stmt = $pdo->query("SELECT COUNT(*) as total FROM tasks");
 $tasks_count = $stmt->fetch()['total'];

// Get users count
 $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
 $users_count = $stmt->fetch()['total'];

// Get recent projects
 $stmt = $pdo->query("SELECT p.*, u.name as manager_name FROM projects p LEFT JOIN users u ON p.manager_id = u.id ORDER BY p.created_at DESC LIMIT 5");
 $recent_projects = $stmt->fetchAll();

// Get my tasks
 $stmt = $pdo->prepare("SELECT t.*, p.name as project_name FROM tasks t LEFT JOIN projects p ON t.project_id = p.id WHERE t.assignee_id = ? ORDER BY t.due_date ASC LIMIT 5");
 $stmt->execute([$_SESSION['user_id']]);
 $my_tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .card-stat {
            transition: transform 0.3s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
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
                            <a class="nav-link active" href="dashboard.php">
                                <i class="bi bi-house-door me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="projects.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="form_project.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-plus-circle me-1"></i> Proyek Baru
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 card-stat">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Proyek</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $projects_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-folder-fill fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 card-stat">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Tugas</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $tasks_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check2-square fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2 card-stat">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Pengguna</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $users_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 card-stat">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Tugas Saya</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($my_tasks); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-person-check-fill fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Projects -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Proyek Terbaru</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_projects)): ?>
                                    <p>Belum ada proyek.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Nama Proyek</th>
                                                    <th>Manajer</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_projects as $project): ?>
                                                <tr>
                                                    <td><a href="project_detail.php?id=<?php echo $project['id']; ?>"><?php echo $project['name']; ?></a></td>
                                                    <td><?php echo $project['manager_name'] ?? 'Tidak ada'; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo match($project['status']) {
                                                                'PLANNING' => 'info',
                                                                'ACTIVE' => 'success',
                                                                'ON_HOLD' => 'warning',
                                                                'COMPLETED' => 'primary',
                                                                'CANCELLED' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        ?>"><?php echo $project['status']; ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- My Tasks -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Tugas Saya</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($my_tasks)): ?>
                                    <p>Anda tidak memiliki tugas.</p>
                                <?php else: ?>
                                    <?php foreach ($my_tasks as $task): ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo $task['title']; ?></h6>
                                            <p class="card-text small text-muted">
                                                <i class="bi bi-folder"></i> <?php echo $task['project_name']; ?><br>
                                                <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($task['due_date'])); ?>
                                            </p>
                                            <div class="d-flex justify-content-between">
                                                <span class="badge bg-<?php 
                                                    echo match($task['status']) {
                                                        'TO_DO' => 'secondary',
                                                        'IN_PROGRESS' => 'primary',
                                                        'REVIEW' => 'warning',
                                                        'DONE' => 'success',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo $task['status']; ?></span>
                                                <a href="task_detail.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary">Lihat</a>
                                            </div>
                                        </div>
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