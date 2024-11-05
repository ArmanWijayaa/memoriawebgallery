<?php
session_start();
require 'config.php'; // Ganti dengan file koneksi database Anda

// Jika pengguna sudah login, arahkan ke dashboard
if (isset($_SESSION['UserID'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ambil user berdasarkan username
    $query = $conn->prepare("SELECT UserID, Username, Password FROM user WHERE Username = ?");
    $query->bind_param("s", $username);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userID = $user['UserID'];
        $hashedPassword = $user['Password'];

        // Verifikasi password
        if (password_verify($password, $hashedPassword)) {
            $_SESSION['UserID'] = $userID; // Set UserID ke sesi
            $_SESSION['Username'] = $username; // Simpan username jika diperlukan
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Username tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Memoria</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="asset/memoria_logo.png">
    <style>
        body {
    background: linear-gradient(135deg, #e0f7fa, #e8eaf6, #f3e5f5, #fbe9e7);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.container {
    max-width: 500px; /* Lebar maksimal lebih sempit */
    width: 90%;
    margin: 20px;
}


.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    background: linear-gradient(145deg, #ffffff, #f3f3f7);
}

h2 {
    color: #4a4e69; /* ungu lembut */
    font-weight: 600;
    margin: 20px 0;
}

.form-group label {
    font-weight: 500;
    color: #6d6875; /* kombinasi ungu dan abu */
}

.form-control {
    border-radius: 8px;
    padding: 12px;
    border: 2px solid #e2e8f0;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #b56576; /* merah maroon lembut */
    box-shadow: 0 0 0 0.2rem rgba(181, 101, 118, 0.25);
}

.btn-primary {
    background: linear-gradient(to right, #4a90e2, #b56576);
    border: none;
    padding: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    border-radius: 8px;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.login-icon {
    font-size: 48px;
    color: #4a90e2;
    margin-bottom: 20px;
}

.alert {
    border-radius: 8px;
    font-weight: 500;
}

.register-link {
    color: #4a90e2; /* biru lembut */
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.register-link:hover {
    color: #b56576; /* merah maroon lembut */
    text-decoration: none;
}

.input-group-text {
    background: none;
    border: 2px solid #e2e8f0;
    border-left: none;
    cursor: pointer;
}

.password-toggle {
    color: #6d6875; /* ungu-abu lembut */
}

    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-body p-5">
                <div class="text-center">
                    <i class="fas fa-user-circle login-icon"></i>
                    <h2>Login To Memoria</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user mr-2"></i>Username</label>
                        <input type="text" class="form-control" name="username" required placeholder="Enter your username">
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock mr-2"></i>Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" id="password" required placeholder="Enter your password">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePassword()">
                                    <i class="fas fa-eye password-toggle"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block mt-4">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                    <p class="text-center mt-4 mb-0">Belum Punya Akun?
                        <a href="register.php" class="register-link">Daftar disini</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>