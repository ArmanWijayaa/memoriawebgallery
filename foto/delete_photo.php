<?php
session_start();
require '../config.php';

// Pastikan hanya permintaan POST yang diterima
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['photo_id'])) {
    // Validasi dan ambil photo_id dari parameter GET
    $photoID = $_GET['photo_id'];

    if (is_numeric($photoID)) {
        $photoID = (int)$photoID; // Casting ke integer untuk keamanan

        // Query untuk menghapus foto
        $query = $conn->prepare("DELETE FROM foto WHERE FotoID = ? AND UserID = ?");
        $query->bind_param("ii", $photoID, $_SESSION['UserID']); // Pastikan pengguna hanya dapat menghapus foto miliknya

        if ($query->execute()) {
            // Redirect ke halaman foto dengan pesan sukses
            header("Location: ../foto.php?success=Foto berhasil dihapus");
            exit;
        } else {
            // Tampilkan pesan error jika penghapusan gagal
            echo "Error deleting photo: " . htmlspecialchars($conn->error);
        }
    } else {
        // Jika photo_id tidak valid
        echo "Invalid photo ID.";
    }
} else {
    // Jika tidak ada photo_id yang disediakan
    header("Location: ../foto.php");
    exit;
}
?>
