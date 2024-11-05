<?php
session_start();
require '../config.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $photoID = $_POST['photoID'];
    $judulFoto = $_POST['judulFoto'];
    $deskripsiFoto = $_POST['deskripsiFoto'];
    $userID = $_SESSION['UserID'];

    // Verify that the photo belongs to the current user
    $verifyQuery = $conn->prepare("SELECT UserID, LokasiFile FROM foto WHERE FotoID = ?");
    $verifyQuery->bind_param("i", $photoID);
    $verifyQuery->execute();
    $result = $verifyQuery->get_result();
    $photo = $result->fetch_assoc();

    if ($photo && $photo['UserID'] == $userID) {
        $updateFields = ["JudulFoto = ?", "DeskripsiFoto = ?"];
        $paramTypes = "ss";
        $params = [$judulFoto, $deskripsiFoto];

        // Check if a new photo was uploaded
        if (isset($_FILES['photoFile']) && $_FILES['photoFile']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['photoFile']['name'];
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($fileExt, $allowed)) {
                $uploadDir = '../uploads/photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newFilename = uniqid() . '.' . $fileExt;
                $destination = $uploadDir . $newFilename;

                if (move_uploaded_file($_FILES['photoFile']['tmp_name'], $destination)) {
                    // Delete old photo if exists
                    if ($photo['LokasiFile'] && file_exists('../' . $photo['LokasiFile'])) {
                        unlink('../' . $photo['LokasiFile']);
                    }

                    $updateFields[] = "LokasiFile = ?";
                    $paramTypes .= "s";
                    $params[] = 'uploads/photos/' . $newFilename;
                }
            }
        }

        // Add photoID to params array
        $params[] = $photoID;
        $paramTypes .= "i";

        $sql = "UPDATE foto SET " . implode(", ", $updateFields) . " WHERE FotoID = ?";
        $updateQuery = $conn->prepare($sql);
        $updateQuery->bind_param($paramTypes, ...$params);
        
        if ($updateQuery->execute()) {
            header("Location: ../foto.php?edit_success=1");
        } else {
            header("Location: ../foto.php?edit_error=1");
        }
    } else {
        header("Location: ../foto.php?edit_error=2");
    }
} else {
    header("Location: ../foto.php");
}
?>