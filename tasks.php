<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Get tasks with project and assignee info
 $stmt = $pdo->query("SELECT t.*, p.name as project_name, u.name as assignee_name FROM tasks t LEFT JOIN projects p ON t.project_id = p.id LEFT JOIN users u ON t.assignee_id = u.id ORDER BY t.due_date ASC");
 $tasks = $stmt->fetchAll();

// Get projects for filter
 $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY name");
 $projects = $stmt->fetchAll();

// Get users for filter
 $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
 $users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas - Sistem Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa
                    }
        .task-card {
            transition: transform 0.3s;
        }
        .task-card:hover {
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
                    <h1 class="h2">Tugas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="form_task.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Tugas Baru
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <select class="form-select" id="projectFilter">
                            <option value="">Semua Proyek</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>"><?php echo $project['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="assigneeFilter">
                            <option value="">Semua Penanggung Jawab</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo $user['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="statusFilter">
                            <option value="">Semua Status</option>
                            <option value="TO_DO">To Do</option>
                            <option value="IN_PROGRESS">In Progress</option>
                            <option value="REVIEW">Review</option>
                            <option value="DONE">Done</option>
                        </select>
                    </div>
                </div>

                <!-- Tasks Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <div class="alert alert-info">Belum ada tugas.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Judul Tugas</th>
                                            <th>Proyek</th>
                                            <th>Penanggung Jawab</th>
                                            <th>Prioritas</th>
                                            <th>Status</th>
                                            <th>Tenggat Waktu</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tasksTableBody">
                                        <?php foreach ($tasks as $task): ?>
                                        <tr data-project-id="<?php echo $task['project_id']; ?>" data-assignee-id="<?php echo $task['assignee_id']; ?>" data-status="<?php echo $task['status']; ?>">
                                            <td><a href="task_detail.php?id=<?php echo $task['id']; ?>"><?php echo $task['title']; ?></a></td>
                                            <td><a href="project_detail.php?id=<?php echo $task['project_id']; ?>"><?php echo $task['project_name']; ?></a></td>
                                            <td><?php echo $task['assignee_name'] ?? 'Tidak ada'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($task['priority']) {
                                                        'LOW' => 'success',
                                                        'MEDIUM' => 'info',
                                                        'HIGH' => 'warning',
                                                        'CRITICAL' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo $task['priority']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($task['status']) {
                                                        'TO_DO' => 'secondary',
                                                        'IN_PROGRESS' => 'primary',
                                                        'REVIEW' => 'warning',
                                                        'DONE' => 'success',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo $task['status']; ?></span>
                                            </td>
                                            <td><?php echo $task['due_date'] ? date('d M Y', strtotime($task['due_date'])) : '-'; ?></td>
                                            <td>
                                                <a href="task_detail.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $task['created_by_id'] == $_SESSION['user_id']): ?>
                                                <a href="form_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger delete-task" data-id="<?php echo $task['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus tugas ini? Tindakan ini tidak dapat dibatalkan.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Delete task functionality
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            document.querySelectorAll('.delete-task').forEach(button => {
                button.addEventListener('click', function() {
                    const taskId = this.getAttribute('data-id');
                    confirmDeleteBtn.href = `handle_delete_task.php?id=${taskId}`;
                    deleteModal.show();
                });
            });
            
            // Filter functionality
            const projectFilter = document.getElementById('projectFilter');
            const assigneeFilter = document.getElementById('assigneeFilter');
            const statusFilter = document.getElementById('statusFilter');
            const taskRows = document.querySelectorAll('#tasksTableBody tr');
            
            function filterTasks() {
                const projectValue = projectFilter.value;
                const assigneeValue = assigneeFilter.value;
                const statusValue = statusFilter.value;
                
                taskRows.forEach(row => {
                    const projectId = row.getAttribute('data-project-id');
                    const assigneeId = row.getAttribute('data-assignee-id');
                    const status = row.getAttribute('data-status');
                    
                    const projectMatch = projectValue === '' || projectId === projectValue;
                    const assigneeMatch = assigneeValue === '' || assigneeId === assigneeValue;
                    const statusMatch = statusValue === '' || status === statusValue;
                    
                    if (projectMatch && assigneeMatch && statusMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            projectFilter.addEventListener('change', filterTasks);
            assigneeFilter.addEventListener('change', filterTasks);
            statusFilter.addEventListener('change', filterTasks);
        });
    </script>
</body>
</html>