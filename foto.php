<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

$userID = $_SESSION['UserID'];
$query = $conn->prepare("SELECT Username, NamaLengkap, ProfilePhoto FROM user WHERE UserID = ?");
$query->bind_param("i", $userID);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if ($user) {
    $username = $user['Username'];
    $namalengkap = $user['NamaLengkap'];
    $profilePicture = isset($user['ProfilePhoto']) && $user['ProfilePhoto'] ? $user['ProfilePhoto'] : 'img/profile.png';
} else {
    echo "User tidak ditemukan.";
    exit;
}

// Get user's albums
$albumsQuery = $conn->prepare("SELECT * FROM album WHERE UserID = ?");
$albumsQuery->bind_param("i", $userID);
$albumsQuery->execute();
$albumsResult = $albumsQuery->get_result();

// Get user's photos
$photosQuery = $conn->prepare("SELECT f.*, a.NamaAlbum FROM foto f LEFT JOIN album a ON f.AlbumID = a.AlbumID WHERE f.UserID = ? ORDER BY f.created_at DESC");
$photosQuery->bind_param("i", $userID);
$photosQuery->execute();
$photosResult = $photosQuery->get_result();

// Bagian untuk pindah album
if (isset($_POST['update_album'])) {
    $photoID = $_POST['photoID'];
    $newAlbumID = $_POST['albumID'];

    $updateAlbumQuery = $conn->prepare("UPDATE foto SET AlbumID = ? WHERE FotoID = ?");
    $updateAlbumQuery->bind_param("ii", $newAlbumID, $photoID);
    $updateAlbumQuery->execute();
    header("Location: foto.php?success=1");
}

//edit profil
if (isset($_POST['update_profile'])) {
    $newNamaLengkap = $_POST['namaLengkap'];
    $updateFields = ["NamaLengkap = ?"];
    $paramTypes = "s";
    $params = [$newNamaLengkap];

    // Check if a new profile photo was uploaded
    if (isset($_FILES['profilePhoto']) && $_FILES['profilePhoto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profilePhoto']['name'];
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed)) {
            $uploadDir = 'uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFilename = uniqid() . '.' . $fileExt;
            $destination = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['profilePhoto']['tmp_name'], $destination)) {
                $updateFields[] = "ProfilePhoto = ?";
                $paramTypes .= "s";
                $params[] = $destination;
            }
        }
    }

    $params[] = $userID;
    $paramTypes .= "i";

    $sql = "UPDATE user SET " . implode(", ", $updateFields) . " WHERE UserID = ?";
    $updateQuery = $conn->prepare($sql);
    $updateQuery->bind_param($paramTypes, ...$params);

    if ($updateQuery->execute()) {
        // Refresh the user data after update
        $query->execute();
        $result = $query->get_result();
        $user = $result->fetch_assoc();
        $namalengkap = $user['NamaLengkap'];
        $profilePicture = isset($user['ProfilePhoto']) && $user['ProfilePhoto'] ? $user['ProfilePhoto'] : 'img/profile.png';

        header("Location: foto.php?profile_updated=1");
        exit;
    }
}

// Tambahkan query untuk mengambil foto
$query = $conn->prepare("
    SELECT f.FotoID, f.JudulFoto, f.DeskripsiFoto, f.LokasiFile, f.TanggalUnggah
    FROM foto f
    WHERE f.UserID = ?
    ORDER BY f.TanggalUnggah DESC
");
$query->bind_param("i", $_SESSION['UserID']);
if (!$query->execute()) {
    die("Database query failed: " . $query->error);
}
$result = $query->get_result();


// Pesan sukses jika ada
$success = isset($_GET['success']) ? $_GET['success'] : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="asset/memoria_logo.png">
    <style>
        :root {
            --ig-primary: #262626;
            --ig-secondary: #fafafa;
            --ig-border: #dbdbdb;
        }

        body {
            background-color: var(--ig-secondary);
        }

        .navbar {
            background-color: white;
            border-bottom: 1px solid var(--ig-border);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 220px;
            position: fixed;
            height: 100vh;
            border-right: 1px solid var(--ig-border);
            background: white;
            padding: 20px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            padding: 12px 15px;
            border-radius: 8px;
            color: var(--ig-text);
            margin: 4px 0;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover {
            background-color: #F5F5F5;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            font-size: 1.2rem;
            width: 24px;
        }

        .content-wrapper {
            margin-top: 80px;
        }


        .profile-header {
            border-bottom: 1px solid var(--ig-border);
            padding: 20px 0;
            margin-bottom: 20px;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e1306c;
            padding: 2px;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .photo-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        .photo-info {
            padding: 12px;
        }

        .photo-title {
            font-weight: 600;
            color: #333;
        }

        .photo-description {
            color: #666;
            font-size: 0.85rem;
        }

        .photo-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .photo-item {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            cursor: pointer;
        }

        .photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .photo-item:hover .photo-overlay {
            opacity: 1;
        }


        .modal-content {
            border-radius: 12px;
        }

        .upload-area {
            border: 2px dashed var(--ig-border);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .upload-area:hover {
            background-color: var(--ig-secondary);
        }

        .story-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid #e1306c;
            padding: 2px;
            margin-right: 15px;
        }

        .story-circle img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .upload-area {
            transition: border 0.3s;
        }

        .upload-area.border-primary {
            border: 2px dashed #007bff;
        }

        /* Pindah album */
        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border-radius: 0.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
            transition: background-color 0.3s, color 0.3s;
        }

        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }

        .btn-success {
            transition: background-color 0.3s, color 0.3s;
        }

        .btn-success:hover {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="#" class="brand">
            <i class="fas fa-camera-retro"></i>
            Gallery
        </a>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a class="nav-link active" href="album.php">
                <i class="fas fa-images"></i>
                <span>Albums</span>
            </a>
            <a class="nav-link" href="foto.php">
                <i class="fas fa-camera"></i>
                <span>Photos</span>
            </a>
            <form action="logout.php" method="POST" class="mt-auto">
                <button class="nav-link text-danger border-0 w-100 text-start">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </form>
        </nav>
    </div>


    <?php if (isset($_GET['profile_updated']) && $_GET['profile_updated'] == 1): ?>
        <div class="alert alert-success" role="alert">
            Profile berhasil diperbarui!
        </div>
    <?php endif; ?>

    <?php if ($success == 1): ?>
        <div class="alert alert-success" role="alert">
            Foto berhasil diunggah!
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="col-10 offset-2">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-3 text-center">
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>" class="profile-pic" alt="Profile">
                </div>
                <div class="col-9">
                    <h2><?php echo htmlspecialchars($namalengkap); ?></h2>
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#editProfileModal">
                        <button class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#editProfileModal">Edit Profile</button>
                    </a>
                    <a class="nav-link" href="#" data-toggle="modal" data-target="#addPhotoModal">
                        <i class="fas fa-plus-square"></i> Add Photo
                    </a>
                </div>
            </div>
        </div>

        <!-- Photo Grid -->
        <h4>Postingan Saya</h4>
        <div class="photo-grid">
            <?php while ($photo = $photosResult->fetch_assoc()): ?>
                <div class="photo-item">
                    <img src="<?php echo htmlspecialchars($photo['LokasiFile']); ?>" alt="<?php echo htmlspecialchars($photo['JudulFoto']); ?>">
                    <div class="photo-overlay">
                        <div class="text-center">
                            <p><?php echo htmlspecialchars($photo['NamaAlbum']); ?></p>
                            <div class="btn-group-vertical">
                                <button onclick="openEditPhotoModal(<?php echo $photo['FotoID']; ?>, '<?php echo htmlspecialchars(addslashes($photo['JudulFoto'])); ?>', '<?php echo htmlspecialchars(addslashes($photo['DeskripsiFoto'])); ?>')" class="btn btn-sm btn-light mb-2">
                                    <i class="fas fa-edit"></i> Edit Photo
                                </button>
                                <button onclick="openEditAlbumModal(<?php echo $photo['FotoID']; ?>)" class="btn btn-sm btn-light mb-2">
                                    <i class="fas fa-folder"></i> Move Album
                                </button>
                                <a href="foto/delete_photo.php?photo_id=<?php echo $photo['FotoID']; ?>" class="btn btn-sm btn-light" onclick="return confirm('Are you sure you want to delete this photo?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        </div>
    </div>
    </div>
    </div>

    <!-- Modal Tambah Foto -->
    <div class="modal fade" id="addPhotoModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Photo</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="foto/upload_photo.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="judulFoto">Judul Foto</label>
                            <input type="text" class="form-control" id="judulFoto" name="judulFoto" required>
                        </div>

                        <div class="form-group">
                            <label for="deskripsiFoto">Deskripsi Foto</label>
                            <textarea class="form-control" id="deskripsiFoto" name="deskripsiFoto" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="album">Album</label>
                            <select class="form-control" id="album" name="albumID">
                                <option value="">Pilih Album</option>
                                <?php while ($album = $albumsResult->fetch_assoc()): ?>
                                    <option value="<?php echo $album['AlbumID']; ?>">
                                        <?php echo htmlspecialchars($album['NamaAlbum']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="upload-area" id="uploadArea" onclick="document.getElementById('file').click();">
                                <input type="file" id="file" name="gambar" accept="image/*" required style="display: none;">
                                <div id="preview">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                                    <p>Click your photo here</p>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Upload Photo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- editing photos -->
    <div class="modal fade" id="editPhotoModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Photo</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="foto/edit_photo.php" method="POST">
                        <input type="hidden" name="photoID" id="editPhotoID">
                        <div class="form-group">
                            <label for="editJudulFoto">Photo Title</label>
                            <input type="text" class="form-control" id="editJudulFoto" name="judulFoto" required>
                        </div>

                        <div class="form-group">
                            <label for="editDeskripsiFoto">Photo Description</label>
                            <textarea class="form-control" id="editDeskripsiFoto" name="deskripsiFoto" rows="3"></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal Edit Album Foto -->
    <div class="modal fade" id="editAlbumModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Album Foto</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="foto.php" method="POST">
                        <input type="hidden" name="photoID" id="editPhotoID">
                        <div class="form-group">
                            <label for="album">Pilih Album Baru</label>
                            <select class="form-control" id="album" name="albumID" required>
                                <?php
                                $albumsResult->data_seek(0); // Reset album query result
                                while ($album = $albumsResult->fetch_assoc()): ?>
                                    <option value="<?php echo $album['AlbumID']; ?>">
                                        <?php echo htmlspecialchars($album['NamaAlbum']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-success" name="update_album">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Profil dengan Preview -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="foto.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group text-center mb-4">
                        <div class="profile-preview-container">
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>"
                                id="profilePreview"
                                class="profile-pic mb-3"
                                style="width: 150px; height: 150px; cursor: pointer; object-fit: cover;"
                                onclick="document.getElementById('profilePhoto').click();">
                            <div class="preview-overlay">
                                <i class="fas fa-camera"></i>
                                <span>Change Photo</span>
                            </div>
                        </div>
                        <input type="file"
                            id="profilePhoto"
                            name="profilePhoto"
                            accept="image/*"
                            style="display: none;"
                            onchange="previewImage(this, 'profilePreview')">
                        <div>
                            <small class="text-muted">Click the image to change profile photo</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="namaLengkap">Full Name</label>
                        <input type="text"
                            class="form-control"
                            id="namaLengkap"
                            name="namaLengkap"
                            value="<?php echo htmlspecialchars($namalengkap); ?>"
                            required>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>





    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('file').onchange = function(e) {
            var reader = new FileReader();
            reader.onload = function(event) {
                var preview = document.getElementById('preview');
                preview.innerHTML = '<img src="' + event.target.result + '" alt="Preview" style="width:100%; height:auto;"/>';
            }
            reader.readAsDataURL(this.files[0]);
        }

        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('file');

        // Drag & drop effect
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('border-primary');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('border-primary');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('border-primary');
            fileInput.files = e.dataTransfer.files;
        });

        function openEditAlbumModal(photoID) {
            document.getElementById('editPhotoID').value = photoID;
            $('#editAlbumModal').modal('show');
        }

        function previewProfile(input) { //edit profil
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        function openEditPhotoModal(photoID, judul, deskripsi) {
            document.getElementById('editPhotoID').value = photoID;
            document.getElementById('editJudulFoto').value = judul;
            document.getElementById('editDeskripsiFoto').value = deskripsi;
            $('#editPhotoModal').modal('show');
        }
    </script>




</body>

</html>