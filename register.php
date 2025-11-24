<?php
session_start();
require_once 'db_connect.php';

 $error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password != $confirm_password) {
        $error = "Password tidak cocok!";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Email sudah digunakan!";
        } else {
            // --- PERUBAHAN: Gunakan Transaksi untuk Integritas Data ---
            try {
                $pdo->beginTransaction();

                // 1. Buat user baru (role akan otomatis 'MEMBER' dari default database)
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $password_hash]);
                
                // Dapatkan ID user yang baru dibuat
                $new_user_id = $pdo->lastInsertId();
                
                // --- LOGIKA BARU: Cari atau Buat Tim Default ---
                $default_team_name = 'Anggota Umum';
                $team_id = null;
                
                // Cari apakah tim default sudah ada
                $stmt = $pdo->prepare("SELECT id FROM teams WHERE name = ?");
                $stmt->execute([$default_team_name]);
                $team = $stmt->fetch();
                
                if ($team) {
                    // Jika tim sudah ada, gunakan ID-nya
                    $team_id = $team['id'];
                } else {
                    // Jika tim belum ada, buat tim baru
                    $stmt = $pdo->prepare("INSERT INTO teams (name, description) VALUES (?, ?)");
                    $stmt->execute([$default_team_name, 'Tim untuk seluruh anggota baru yang mendaftar.']);
                    $team_id = $pdo->lastInsertId();
                }
                
                // 2. Tambahkan user baru sebagai anggota tim default
                $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id) VALUES (?, ?)");
                $stmt->execute([$team_id, $new_user_id]);
                
                // Jika semua berhasil, komit transaksi
                $pdo->commit();
                
                // Alihkan ke halaman login dengan pesan sukses
                header("Location: login.php?registered=1");
                exit;

            } catch (Exception $e) {
                // Jika ada error, rollback transaksi dan tampilkan error
                $pdo->rollBack();
                $error = "Terjadi kesalahan saat mendaftar. Silakan coba lagi. Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sistem Manajemen Proyek</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 class="text-center mb-4">Daftar Akun Baru</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="register.php" method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <!-- --- PERUBAHAN: Hapus Bagian Pilihan Peran --- -->
            <!-- Bagian ini dihapus karena peran otomatis 'MEMBER' -->
            <button type="submit" class="btn btn-primary w-100">Daftar</button>
        </form>
        <div class="text-center mt-3">
            <p>Sudah punya akun? <a href="login.php">Login</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>