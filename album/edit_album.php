<?php
session_start();
require '../config.php';
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $albumID = $_POST['albumID'];
    $albumName = $_POST['albumName'];
    $albumDescription = $_POST['albumDescription'];
    $userID = $_SESSION['UserID'];
    
    // Ambil cover lama jika tidak diganti
    $existingCover = null;
    $query = $conn->prepare("SELECT Cover FROM album WHERE AlbumID = ?");
    $query->bind_param("i", $albumID);
    $query->execute();
    $result = $query->get_result();
    if ($row = $result->fetch_assoc()) {
        $existingCover = $row['Cover'];
    }
    
    // Handle the cover image upload
    $coverImage = $existingCover;
    if (isset($_FILES['albumCover']) && $_FILES['albumCover']['error'] === UPLOAD_ERR_OK) {
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
            die("Error uploading file.");
        }
        
        // Sesuaikan path untuk disimpan di database
        $coverImage = str_replace('../', '', $coverImage);
    }
    
    // Update album details
    $query = $conn->prepare("UPDATE album SET NamaAlbum = ?, Deskripsi = ?, Cover = ? WHERE AlbumID = ? AND UserID = ?");
    $query->bind_param("sssii", $albumName, $albumDescription, $coverImage, $albumID, $userID);
    
    if ($query->execute()) {
        header("Location: ../album.php?success=Album updated successfully");
        exit;
    } else {
        echo "Error updating album: " . $conn->error;
    }
}
?>