<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

 $document = null;
 $is_edit = false;

// Check if editing existing document
if (isset($_GET['id'])) {
    $is_edit = true;
    $document_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        header("Location: documents.php");
        exit;
    }
    
    // Check if user has permission to edit this document
    if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER' && $document['uploaded_by_id'] != $_SESSION['user_id']) {
        header("Location: documents.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $related_to = $_POST['related_to'];
    $project_id = $related_to == 'project' ? $_POST['project_id'] : null;
    $task_id = $related_to == 'task' ? $_POST['task_id'] : null;
    $version = $_POST['version'];
    $category = $_POST['category'];
    $uploaded_by_id = $_SESSION['user_id'];
    
    // Handle file upload
    $file_path = '';
    $file_name = '';
    $file_size = 0;
    $file_type = '';
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['file']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Create a unique filename to prevent overwriting
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
                $file_path = $new_filename;
                $file_name = $filename;
                $file_size = $_FILES['file']['size'];
                $file_type = $filetype;
            } else {
                $error = "Gagal mengunggah file.";
            }
        } else {
            $error = "Tipe file tidak diizinkan.";
        }
    } elseif ($is_edit) {
        // Keep old file if no new file is uploaded during edit
        $file_path = $document['file_path'];
        $file_name = $document['file_name'];
        $file_size = $document['file_size'];
        $file_type = $document['file_type'];
    } else {
        $error = "Harap pilih file untuk diunggah.";
    }
    
    if (!isset($error)) {
        if ($is_edit) {
            $stmt = $pdo->prepare("UPDATE documents SET title = ?, description = ?, file_path = ?, file_name = ?, file_size = ?, file_type = ?, version = ?, category = ?, project_id = ?, task_id = ? WHERE id = ?");
            $stmt->execute([$title, $description, $file_path, $file_name, $file_size, $file_type, $version, $category, $project_id, $task_id, $document_id]);
            header("Location: project_detail.php?id=" . ($document['project_id'] ?: '#documents'));
        } else {
            $stmt = $pdo->prepare("INSERT INTO documents (title, description, file_path, file_name, file_size, file_type, version, category, uploaded_by_id, project_id, task_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $file_path, $file_name, $file_size, $file_type, $version, $category, $uploaded_by_id, $project_id, $task_id]);
            
            if ($project_id) {
                header("Location: project_detail.php?id=$project_id#documents");
            } elseif ($task_id) {
                header("Location: task_detail.php?id=$task_id");
            } else {
                header("Location: documents.php");
            }
        }
        exit;
    }
}

// Get projects for dropdown
 $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY name");
 $projects = $stmt->fetchAll();

// Get tasks for dropdown
 $stmt = $pdo->query("SELECT id, title FROM tasks ORDER BY title");
 $tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Dokumen' : 'Unggah Dokumen Baru'; ?> - Sistem Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
                            <a class="nav-link" href="tasks.php">
                                <i class="bi bi-check2-square me-2"></i> Tugas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="documents.php">
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit Dokumen' : 'Unggah Dokumen Baru'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="documents.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="form_document.php<?php echo $is_edit ? '?id=' . $document['id'] : ''; ?>" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Judul Dokumen</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $document['title'] ?? ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="file" class="form-label">Pilih Berkas</label>
                                        <input type="file" class="form-control" id="file" name="file" <?php echo !$is_edit ? 'required' : ''; ?>>
                                        <?php if ($is_edit && $document['file_name']): ?>
                                            <div class="form-text">File saat ini: <?php echo $document['file_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Deskripsi</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $document['description'] ?? ''; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Terkait Dengan</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="related_to" id="relatedProject" value="project" <?php echo ($document['project_id'] || !isset($_GET['task_id'])) ? 'checked' : ''; ?> required>
                                                <label class="form-check-label" for="relatedProject">Proyek</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="related_to" id="relatedTask" value="task" <?php echo ($document['task_id'] || isset($_GET['task_id'])) ? 'checked' : ''; ?> required>
                                                <label class="form-check-label" for="relatedTask">Tugas</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3" id="projectSelectContainer">
                                        <label for="project_id" class="form-label">Pilih Proyek</label>
                                        <select class="form-select" id="project_id" name="project_id">
                                            <option value="">Pilih Proyek</option>
                                            <?php foreach ($projects as $project): ?>
                                                <option value="<?php echo $project['id']; ?>" <?php echo ($document['project_id'] ?? (isset($_GET['project_id']) ? $_GET['project_id'] : '')) == $project['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $project['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3 d-none" id="taskSelectContainer">
                                        <label for="task_id" class="form-label">Pilih Tugas</label>
                                        <select class="form-select" id="task_id" name="task_id">
                                            <option value="">Pilih Tugas</option>
                                            <?php foreach ($tasks as $task): ?>
                                                <option value="<?php echo $task['id']; ?>" <?php echo ($document['task_id'] ?? (isset($_GET['task_id']) ? $_GET['task_id'] : '')) == $task['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $task['title']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="version" class="form-label">Versi</label>
                                            <input type="text" class="form-control" id="version" name="version" value="<?php echo $document['version'] ?? '1.0'; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="category" class="form-label">Kategori</label>
                                            <select class="form-select" id="category" name="category">
                                                <option value="Desain" <?php echo ($document['category'] ?? '') == 'Desain' ? 'selected' : ''; ?>>Desain</option>
                                                <option value="Dokumen" <?php echo ($document['category'] ?? '') == 'Dokumen' ? 'selected' : ''; ?>>Dokumen</option>
                                                <option value="Laporan" <?php echo ($document['category'] ?? '') == 'Laporan' ? 'selected' : ''; ?>>Laporan</option>
                                                <option value="Lainnya" <?php echo ($document['category'] ?? '') == 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="uploaded_by" class="form-label">Diunggah Oleh</label>
                                        <input type="text" class="form-control" id="uploaded_by" name="uploaded_by" value="<?php echo $_SESSION['user_name']; ?>" readonly>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="documents.php" class="btn btn-secondary me-2">Batal</a>
                                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Simpan Perubahan' : 'Unggah Dokumen'; ?></button>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const relatedProject = document.getElementById('relatedProject');
            const relatedTask = document.getElementById('relatedTask');
            const projectSelectContainer = document.getElementById('projectSelectContainer');
            const taskSelectContainer = document.getElementById('taskSelectContainer');
            const projectIdSelect = document.getElementById('project_id');
            const taskIdSelect = document.getElementById('task_id');

            function toggleSelects() {
                if (relatedProject.checked) {
                    projectSelectContainer.classList.remove('d-none');
                    taskSelectContainer.classList.add('d-none');
                    projectIdSelect.setAttribute('required', 'required');
                    taskIdSelect.removeAttribute('required');
                } else {
                    projectSelectContainer.classList.add('d-none');
                    taskSelectContainer.classList.remove('d-none');
                    projectIdSelect.removeAttribute('required');
                    taskIdSelect.setAttribute('required', 'required');
                }
            }

            relatedProject.addEventListener('change', toggleSelects);
            relatedTask.addEventListener('change', toggleSelects);
            
            // Initial state
            toggleSelects();
        });
    </script>
</body>
</html>