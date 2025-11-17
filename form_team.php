<?php
require_once 'auth_check.php';
require_once 'db_connect.php';

// Only Admin and Manager can create/edit teams
if ($_SESSION['user_role'] != 'ADMIN' && $_SESSION['user_role'] != 'MANAGER') {
    header("Location: dashboard.php");
    exit;
}

 $team = null;
 $team_members = [];
 $is_edit = false;

// Check if editing existing team
if (isset($_GET['id'])) {
    $is_edit = true;
    $team_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();
    
    if (!$team) {
        header("Location: teams.php");
        exit;
    }
    
    // Get team members
    $stmt = $pdo->prepare("SELECT user_id FROM team_members WHERE team_id = ?");
    $stmt->execute([$team_id]);
    $team_members = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    // --- LOGIKA BARU: Penanganan "Jadikan Saya Ketua" ---
    if (isset($_POST['make_me_head']) && $_POST['make_me_head'] == '1') {
        $team_head_id = $_SESSION['user_id'];
    } else {
        $team_head_id = $_POST['team_head_id'];
    }
    
    $members = $_POST['members'] ?? [];
    
    // --- LOGIKA BARU: Penanganan Upload Logo ---
    $logo_path = $team['logo_path'] ?? null; // Pertahankan logo lama saat edit
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['logo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Hapus logo lama jika ada
            if ($is_edit && !empty($team['logo_path'])) {
                $old_logo_path = 'uploads/' . $team['logo_path'];
                if (file_exists($old_logo_path)) {
                    unlink($old_logo_path);
                }
            }
            
            $new_filename = 'team_logo_' . uniqid() . '.' . $filetype;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = $new_filename;
            }
        }
    }
    
    if ($is_edit) {
        // Update team
        $stmt = $pdo->prepare("UPDATE teams SET name = ?, description = ?, team_head_id = ?, logo_path = ? WHERE id = ?");
        $stmt->execute([$name, $description, $team_head_id, $logo_path, $team_id]);
        
        // Update team members (remove all and add new ones)
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ?");
        $stmt->execute([$team_id]);
        
        foreach ($members as $member_id) {
            $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
            $stmt->execute([$team_id, $member_id]);
        }
        
        header("Location: team_detail.php?id=$team_id");
    } else {
        // Create new team
        $stmt = $pdo->prepare("INSERT INTO teams (name, description, team_head_id, logo_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $team_head_id, $logo_path]);
        
        $team_id = $pdo->lastInsertId();
        
        // Add team members
        foreach ($members as $member_id) {
            $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
            $stmt->execute([$team_id, $member_id]);
        }
        
        header("Location: team_detail.php?id=$team_id");
    }
    exit;
}

// Get all users for member selection
 $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
 $users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Tim' : 'Tim Baru'; ?> - Sistem Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Tambahkan CSS Tom Select -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .logo-preview {
            max-width: 100px;
            max-height: 100px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            margin-top: 10px;
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
                            <a class="nav-link" href="documents.php">
                                <i class="bi bi-file-earmark me-2"></i> Dokumen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="teams.php">
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
                    <h1 class="h2"><?php echo $is_edit ? 'Edit Tim' : 'Tim Baru'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="teams.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="form_team.php<?php echo $is_edit ? '?id=' . $team['id'] : ''; ?>" method="post" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nama Tim</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $team['name'] ?? ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Deskripsi Tim</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $team['description'] ?? ''; ?></textarea>
                                    </div>
                                    
                                    <!-- --- FITUR BARU: Upload Logo --- -->
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Logo/Avatar Tim</label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                        <?php if ($is_edit && !empty($team['logo_path'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Logo saat ini:</small><br>
                                                <img src="uploads/<?php echo htmlspecialchars($team['logo_path']); ?>" alt="Logo Tim" class="logo-preview">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="team_head_id" class="form-label">Kepala Tim</label>
                                            <select class="form-select" id="team_head_id" name="team_head_id">
                                                <option value="">Pilih Kepala Tim</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?php echo $user['id']; ?>" <?php echo ($team['team_head_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                                        <?php echo $user['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="1" id="make_me_head" name="make_me_head">
                                                <label class="form-check-label" for="make_me_head">
                                                    Jadikan Saya Ketua
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="members-select" class="form-label">Daftar Anggota</label>
                                        <select id="members-select" name="members[]" multiple placeholder="Pilih Anggota Tim...">
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $team_members) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <a href="teams.php" class="btn btn-secondary me-2">Batal</a>
                                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Simpan Perubahan' : 'Buat Tim'; ?></button>
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
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Tom Select untuk dropdown anggota tim
            new TomSelect('#members-select', {
                plugins: {
                    'checkbox_options': {},
                    'remove_button':{
                        'title':'Hapus item ini',
                    }
                },
                create: false,
                maxItems: null
            });

            // --- LOGIKA BARU: Interaktivitas Checkbox "Jadikan Saya Ketua" ---
            const makeMeHeadCheckbox = document.getElementById('make_me_head');
            const teamHeadDropdown = document.getElementById('team_head_id');
            const currentUserId = '<?php echo $_SESSION['user_id']; ?>';

            makeMeHeadCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Pilih user yang sedang login di dropdown
                    teamHeadDropdown.value = currentUserId;
                    // Opsi: non-aktifkan dropdown untuk mencegah perubahan
                    // teamHeadDropdown.disabled = true;
                } else {
                    // Aktifkan kembali dropdown jika checkbox dicentang ulang
                    // teamHeadDropdown.disabled = false;
                }
            });
        });
    </script>
</body>
</html>