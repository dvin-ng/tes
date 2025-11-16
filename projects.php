<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Get projects with manager name
 $stmt = $pdo->query("SELECT p.*, u.name as manager_name FROM projects p LEFT JOIN users u ON p.manager_id = u.id ORDER BY p.created_at DESC");
 $projects = $stmt->fetchAll();

// Get users for manager dropdown
 $stmt = $pdo->query("SELECT id, name FROM users WHERE role IN ('ADMIN', 'MANAGER') ORDER BY name");
 $managers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyek - Sistem Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .project-card {
            transition: transform 0.3s;
        }
        .project-card:hover {
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
                    <h1 class="h2">Proyek</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="form_project.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Proyek Baru
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filter -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Cari proyek..." id="searchInput">
                            <button class="btn btn-outline-secondary" type="button" id="searchButton">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" id="statusFilter">
                            <option value="">Semua Status</option>
                            <option value="PLANNING">Perencanaan</option>
                            <option value="ACTIVE">Aktif</option>
                            <option value="ON_HOLD">Ditunda</option>
                            <option value="COMPLETED">Selesai</option>
                            <option value="CANCELLED">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <!-- Projects Grid -->
                <div class="row" id="projectsContainer">
                    <?php if (empty($projects)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">Belum ada proyek.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($projects as $project): ?>
                        <div class="col-lg-4 col-md-6 mb-4 project-item" data-status="<?php echo $project['status']; ?>">
                            <div class="card h-100 project-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo $project['name']; ?></h6>
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
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo substr($project['description'], 0, 100) . (strlen($project['description']) > 100 ? '...' : ''); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?php echo $project['manager_name'] ?? 'Tidak ada'; ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($project['start_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between">
                                        <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Detail
                                        </a>
                                        <?php if ($_SESSION['user_role'] == 'ADMIN' || $_SESSION['user_role'] == 'MANAGER' || $project['manager_id'] == $_SESSION['user_id']): ?>
                                        <div class="btn-group" role="group">
                                            <a href="form_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-project" data-id="<?php echo $project['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                    Apakah Anda yakin ingin menghapus proyek ini? Tindakan ini tidak dapat dibatalkan.
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
            // Delete project functionality
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            
            document.querySelectorAll('.delete-project').forEach(button => {
                button.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-id');
                    confirmDeleteBtn.href = `handle_delete_project.php?id=${projectId}`;
                    deleteModal.show();
                });
            });
            
            // Filter functionality
            const statusFilter = document.getElementById('statusFilter');
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const projectItems = document.querySelectorAll('.project-item');
            
            function filterProjects() {
                const statusValue = statusFilter.value.toLowerCase();
                const searchValue = searchInput.value.toLowerCase();
                
                projectItems.forEach(item => {
                    const status = item.getAttribute('data-status').toLowerCase();
                    const title = item.querySelector('.card-header h6').textContent.toLowerCase();
                    const description = item.querySelector('.card-text').textContent.toLowerCase();
                    
                    const statusMatch = statusValue === '' || status === statusValue;
                    const searchMatch = searchValue === '' || title.includes(searchValue) || description.includes(searchValue);
                    
                    if (statusMatch && searchMatch) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
            
            statusFilter.addEventListener('change', filterProjects);
            searchButton.addEventListener('click', filterProjects);
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    filterProjects();
                }
            });
        });
    </script>
</body>
</html>