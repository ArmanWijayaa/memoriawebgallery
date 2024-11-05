<?php
session_start();
require '../config.php';
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

$errors = []; // Array untuk menyimpan pesan kesalahan

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $albumName = trim($_POST['albumName']);
    $albumDescription = trim($_POST['albumDescription']);
    $userID = $_SESSION['UserID'];

    // Validasi input
    if (empty($albumName)) {
        $errors[] = "Nama album harus diisi.";
    }
    if (empty($albumDescription)) {
        $errors[] = "Deskripsi album harus diisi.";
    }

    // Handle the cover image upload
    $coverImage = 'img/album-cover.png'; // Default cover
    if (isset($_FILES['albumCover']) && $_FILES['albumCover']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['albumCover']['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Hanya file gambar JPEG, PNG, dan GIF yang diperbolehkan.";
        } else {
            $targetDir = "../uploads/albums/";

            // Buat direktori jika belum ada
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // Buat nama file unik
            $fileName = uniqid() . '-' . basename($_FILES['albumCover']['name']);
            $coverImage = $targetDir . $fileName;

            // Pindahkan file upload
            if (!move_uploaded_file($_FILES['albumCover']['tmp_name'], $coverImage)) {
                $errors[] = "Terjadi kesalahan saat mengupload file.";
            }

            // Sesuaikan path untuk disimpan di database
            $coverImage = str_replace('../', '', $coverImage);
        }
    }

    // Jika ada kesalahan, tampilkan pesan kesalahan
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    } else {
        // Insert the new album into the database
        $query = $conn->prepare("INSERT INTO album (NamaAlbum, Deskripsi, Cover, UserID, TanggalDibuat) VALUES (?, ?, ?, ?, NOW())");
        $query->bind_param("sssi", $albumName, $albumDescription, $coverImage, $userID);

        if ($query->execute()) {
            header("Location: ../album.php");
            exit;
        } else {
            die("Query database gagal: " . $query->error);
        }
    }
}
?>
