<?php
session_start();
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $albumID = $_POST['albumID'];

    // Delete album
    $query = $conn->prepare("DELETE FROM album WHERE AlbumID = ? AND UserID = ?");
    $query->bind_param("ii", $albumID, $_SESSION['UserID']);

    if ($query->execute()) {
        header("Location: ../album.php?success=Album deleted successfully");
        exit;
    } else {
        echo "Error deleting album: " . $conn->error;
    }
}
?>
