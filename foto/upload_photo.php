<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

// Menggunakan path relatif yang benar untuk config
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userID = $_SESSION['UserID'];
    $judulFoto = $_POST['judulFoto'];
    $deskripsiFoto = $_POST['deskripsiFoto'];
    $albumID = !empty($_POST['albumID']) ? $_POST['albumID'] : null;
    $tanggalUnggah = date('Y-m-d H:i:s');

    // Handle file upload
    $targetDir = "../uploads/foto/"; // Ubah path ke direktori uploads/foto
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = uniqid() . '_' . basename($_FILES["gambar"]["name"]);
    $targetFile = $targetDir . $fileName;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));

    // Check if image file is actual image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["gambar"]["tmp_name"]);
        if($check === false) {
            header("Location: ../foto.php?error=1&message=File is not an image.");
            exit;
        }
    }
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        header("Location: ../foto.php");
        exit;
    }

    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFile)) {
        // Menyesuaikan path yang akan disimpan di database
        $databasePath = "uploads/foto/" . $fileName; // Path relatif untuk database
        
        // Insert into database
        $query = $conn->prepare("INSERT INTO foto (JudulFoto, DeskripsiFoto, TanggalUnggah, LokasiFile, AlbumID, UserID, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $query->bind_param("sssssss", $judulFoto, $deskripsiFoto, $tanggalUnggah, $databasePath, $albumID, $userID, $tanggalUnggah);
        
        if ($query->execute()) {
            header("Location: ../foto.php?success=1");
            exit;
        } else {
            unlink($targetFile); // Delete the uploaded file if database insert fails
            header("Location: ../foto.php?error=1&message=Database error.");
            exit;
        }
    } else {
        header("Location: ../foto.php?error=1&message=Error uploading file.");
        exit;
    }
} else {
    header("Location: ../foto.php");
    exit;
}
?>