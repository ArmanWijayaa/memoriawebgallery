<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $namaLengkap = $_POST['nama_lengkap'];
    $alamat = $_POST['alamat'];
    $profilePhoto = 'asset/profile.png'; // Nama file foto profil yang sudah disiapkan

    $stmt = $conn->prepare("INSERT INTO user (Username, Password, Email, NamaLengkap, Alamat, ProfilePhoto) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $password, $email, $namaLengkap, $alamat, $profilePhoto);

    if ($stmt->execute()) {
        echo "Registrasi berhasil! <a href='login.php'>Login disini</a>";
    } else {
        echo "Registrasi gagal: " . $conn->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi | Memoria</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="asset/memoria_logo.png">
    <style>
        body {
    background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); /* Warna biru gradasi */
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    background: rgba(255, 255, 255, 0.9); /* Lebih transparan */
}

h2 {
    color: #003366; /* Warna biru tua */
    font-weight: 600;
    margin: 20px 0;
    text-align: center;
}

.form-group label {
    font-weight: 500;
    color: #004d80; /* Warna biru gelap */
}

.form-control {
    border-radius: 8px;
    padding: 12px;
    border: 2px solid #b3e0ff; /* Warna biru terang */
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #00c6ff;
    box-shadow: 0 0 0 0.2rem rgba(0, 198, 255, 0.25);
}

.container {
    max-width: 550px; /* Mempersempit lebar maksimal */
    width: 90%;
    margin: 20px auto;
}


.btn-primary {
    background: linear-gradient(to right, #00c6ff, #0072ff);
    border: none;
    padding: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    border-radius: 8px;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 198, 255, 0.4);
}

.login-link {
    color: #00c6ff;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.login-link:hover {
    color: #0072ff;
    text-decoration: none;
}

    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-body p-5">
                <div class="text-center">
                    <i class="fas fa-user-plus register-icon"></i>
                    <h2>Daftar Akun Baru</h2>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user mr-2"></i>Username</label>
                        <input type="text" class="form-control" name="username" id="username" required placeholder="Pilih username Anda">
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock mr-2"></i>Password</label>
                        <input type="password" class="form-control" name="password" id="password" required placeholder="Masukkan password">
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope mr-2"></i>Email</label>
                        <input type="email" class="form-control" name="email" id="email" required placeholder="Masukkan alamat email">
                    </div>
                    <div class="form-group">
                        <label for="nama_lengkap"><i class="fas fa-id-card mr-2"></i>Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" id="nama_lengkap" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="form-group">
                        <label for="alamat"><i class="fas fa-home mr-2"></i>Alamat</label>
                        <textarea class="form-control" name="alamat" id="alamat" required placeholder="Masukkan alamat lengkap"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block mt-4">
                        <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                    </button>
                    <p class="text-center mt-4 mb-0">Sudah punya akun? 
                        <a href="login.php" class="login-link">Login disini</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min. js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>