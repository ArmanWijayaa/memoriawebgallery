<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

$query = $conn->prepare("
    SELECT a.AlbumID, a.NamaAlbum, a.Deskripsi, a.TanggalDibuat, a.Cover,
           (SELECT GROUP_CONCAT(f.LokasiFile ORDER BY f.created_at LIMIT 3) 
            FROM foto f 
            WHERE f.AlbumID = a.AlbumID) AS thumbnails
            FROM album a
            WHERE a.UserID = ?
            ORDER BY a.TanggalDibuat DESC
");
$query->bind_param("i", $_SESSION['UserID']);
if (!$query->execute()) {
    die("Database query failed: " . $query->error);
}
$result = $query->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Albums - Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="asset/memoria_logo.png">
    <style>
        :root {
            --ig-background: #FAFAFA;
            --ig-border: #DBDBDB;
            --ig-text: #262626;
        }

        body {
            background-color: var(--ig-background);
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

        /* Main Content Styles */
        .main-content {
            margin-left: 220px;
            padding: 30px;
            max-width: 935px;
            margin-right: 320px;
        }

        .album-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        /* Album Card Styles */
        .album-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .album-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .album-cover {
            position: relative;
            padding-bottom: 100%;
            overflow: hidden;
        }

        .album-cover img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .album-info {
            padding: 16px;
        }

        .album-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--ig-text);
        }

        .album-description {
            color: #8e8e8e;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2px;
            margin-top: 12px;
        }

        .thumbnail {
            position: relative;
            padding-bottom: 100%;
        }

        .thumbnail img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 0.9rem;
            border-radius: 4px;
            flex: 1;
        }

        /* Header Section */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--ig-border);
        }

        /* Add Album Button */
        .btn-add-album {
            background-color: #0095f6;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-add-album:hover {
            background-color: #0081d6;
            color: white;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid var(--ig-border);
            padding: 16px 24px;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            border-top: 1px solid var(--ig-border);
            padding: 16px 24px;
        }

        /* Form Controls */
        .form-control {
            border: 1px solid var(--ig-border);
            border-radius: 4px;
            padding: 8px 12px;
        }

        .form-control:focus {
            border-color: #0095f6;
            box-shadow: none;
        }

        /* Brand Logo */
        .brand {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            color: var(--ig-text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h4 class="m-0">My Albums</h4>
            <button class="btn btn-add-album" data-bs-toggle="modal" data-bs-target="#addAlbumModal">
                <i class="fas fa-plus me-2"></i>Create Album
            </button>
        </div>

        <div class="album-grid">
            <?php while ($album = $result->fetch_assoc()): ?>
                <div class="album-card">
                    <div class="album-cover">
                    <img src="<?php echo htmlspecialchars($album['Cover']); ?>" alt="Album Cover" onerror="this.onerror=null;this.src='img/album-cover.png';">
                    </div>

                    <div class="album-info">
                        <h5 class="album-title"><?php echo htmlspecialchars($album['NamaAlbum']); ?></h5>
                        <p class="album-description"><?php echo htmlspecialchars($album['Deskripsi']); ?></p>

                        <div class="thumbnail-grid">
                            <?php
                            $thumbnails = explode(',', $album['thumbnails']);
                            for ($i = 0; $i < min(3, count($thumbnails)); $i++):
                            ?>
                                <div class="thumbnail">
                                    <img src="<?php echo htmlspecialchars($thumbnails[$i]); ?>" alt="Thumbnail">
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="action-buttons">
                            <button class="btn btn-action btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAlbumModal<?php echo $album['AlbumID']; ?>">
                                <i class="fas fa-edit"></i>Edit
                            </button>
                            <button class="btn btn-action btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAlbumModal<?php echo $album['AlbumID']; ?>">
                                <i class="fas fa-trash"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Edit Album Modal -->
                <div class="modal fade" id="editAlbumModal<?php echo $album['AlbumID']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="album/edit_album.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Album</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="albumID" value="<?php echo $album['AlbumID']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Album Name</label>
                                        <input type="text" class="form-control" name="albumName" value="<?php echo htmlspecialchars($album['NamaAlbum']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="albumDescription" rows="3" required><?php echo htmlspecialchars($album['Deskripsi']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Cover Image</label>
                                        <input type="file" class="form-control" name="albumCover">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Album Modal -->
                <div class="modal fade" id="deleteAlbumModal<?php echo $album['AlbumID']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="album/delete_album.php" method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title">Delete Album</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="albumID" value="<?php echo $album['AlbumID']; ?>">
                                    <p>Are you sure you want to delete the album <strong><?php echo htmlspecialchars($album['NamaAlbum']); ?></strong>?</p>
                                    <p class="text-muted small">This action cannot be undone.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Delete Album</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- Add Album Modal -->
<div class="modal fade" id="addAlbumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="album/add_album.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Album</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Album Name</label>
                        <input type="text" class="form-control" name="albumName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="albumDescription" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover Image</label>
                        <input type="file" class="form-control" name="albumCover" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Album</button>
                </div>
            </form>
        </div>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>