<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

// Ambil data pengguna
$userID = $_SESSION['UserID'];
$query = $conn->prepare("SELECT Username, ProfilePhoto, NamaLengkap FROM user WHERE UserID = ?");
$query->bind_param("i", $userID);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Ambil semua pengguna lain
$otherUsersQuery = $conn->prepare("SELECT UserID, Username, NamaLengkap, ProfilePhoto FROM user WHERE UserID != ? ORDER BY NamaLengkap ASC");
$otherUsersQuery->bind_param("i", $userID);
$otherUsersQuery->execute();
$otherUsersResult = $otherUsersQuery->get_result();

if ($user) {
    $username = $user['Username'];
    $namalengkap = $user['NamaLengkap'];
    $profilePicture = isset($user['ProfilePhoto']) && $user['ProfilePhoto'] ? $user['ProfilePhoto'] : 'img/profile.png';
} else {
    echo "User tidak ditemukan.";
    exit;
}

// Ambil semua album
$albumsQuery = $conn->prepare("SELECT a.*, u.NamaLengkap AS Owner FROM album a JOIN user u ON a.UserID = u.UserID ORDER BY a.created_at DESC");
$albumsQuery->execute();
$albumsResult = $albumsQuery->get_result();

// Modified photo query to include all photos with like counts
$photosQuery = $conn->prepare("
    SELECT f.*, 
           u.NamaLengkap,
           u.ProfilePhoto AS UserProfilePhoto,
           (SELECT COUNT(*) FROM likefoto WHERE FotoID = f.FotoID) as LikeCount,
           (SELECT COUNT(*) FROM likefoto WHERE FotoID = f.FotoID AND UserID = ?) as UserLiked
    FROM foto f 
    JOIN user u ON f.UserID = u.UserID
    ORDER BY f.created_at DESC
");

// ngambil postingan foto
$photosQuery->bind_param("i", $userID);
$photosQuery->execute();
$photosResult = $photosQuery->get_result();

// Handle AJAX requests
if (isset($_GET['album_id'])) {
    $albumID = $_GET['album_id'];
    $photosQuery = $conn->prepare("SELECT * FROM foto WHERE AlbumID = ?");
    $photosQuery->bind_param("i", $albumID);
    $photosQuery->execute();
    $photosResult = $photosQuery->get_result();
    $photos = [];
    while ($photo = $photosResult->fetch_assoc()) {
        $photos[] = $photo;
    }
    echo json_encode($photos);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'toggleLike') {
    $fotoID = $_POST['fotoID'];
    $checkLike = $conn->prepare("SELECT LikeID FROM likefoto WHERE FotoID = ? AND UserID = ?");
    $checkLike->bind_param("ii", $fotoID, $userID);
    $checkLike->execute();
    $likeResult = $checkLike->get_result();

    if ($likeResult->num_rows > 0) {
        $deleteLike = $conn->prepare("DELETE FROM likefoto WHERE FotoID = ? AND UserID = ?");
        $deleteLike->bind_param("ii", $fotoID, $userID);
        $deleteLike->execute();
        echo json_encode(['status' => 'unliked']);
    } else {
        $addLike = $conn->prepare("INSERT INTO likefoto (FotoID, UserID, TanggalLike) VALUES (?, ?, NOW())");
        $addLike->bind_param("ii", $fotoID, $userID);
        $addLike->execute();
        echo json_encode(['status' => 'liked']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'addComment') {
    $fotoID = $_POST['fotoID'];
    $comment = $_POST['comment'];
    $addComment = $conn->prepare("INSERT INTO komentarfoto (FotoID, UserID, IsiKomentar, TanggalKomentar) VALUES (?, ?, ?, NOW())");
    $addComment->bind_param("iis", $fotoID, $userID, $comment);
    $addComment->execute();
    echo json_encode([
        'status' => 'success',
        'commentID' => $conn->insert_id,
        'username' => $namalengkap,
        'comment' => $comment,
        'date' => date('F j, Y')
    ]);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'getComments') {
    $fotoID = $_GET['fotoID'];
    $commentsQuery = $conn->prepare("
        SELECT k.*, u.NamaLengkap, u.ProfilePhoto 
        FROM komentarfoto k 
        JOIN user u ON k.UserID = u.UserID 
        WHERE k.FotoID = ? 
        ORDER BY k.TanggalKomentar DESC
    ");
    $commentsQuery->bind_param("i", $fotoID);
    $commentsQuery->execute();
    $comments = $commentsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($comments);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Home Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="asset/memoria_logo.png">
    <style>
        :root {
            --background-color: #f0f4fa; /* Biru sangat terang */
        --border-color: #dcdde1; /* Abu-abu terang */
        --text-color: #4b4f58; /* Abu-abu gelap */
        --primary-color: #a7c7e7; /* Biru lembut */
        --secondary-color: #d8bfd8; /* Ungu lembut */
        --highlight-color: #800000; /* Merah maroon */
        --hover-color: #e6e0f8; /* Ungu terang */
        }

        body {
            background-color: var(--background-color);
            font-family: Arial, sans-serif;
            margin: 0;
            padding-top: 60px;
            overflow: hidden;
        }

        /* Navbar Styling */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand img {
            transition: transform 0.3s ease;
        }

        .navbar-brand img:hover {
            transform: scale(1.1);
        }

        .main-container {
            display: flex;
            height: calc(100vh - 60px);
        }

        .sidebar {
            width: 250px;
            padding: 20px;
            border-right: 1px solid var(--border-color);
            background: #ffffff;
            height: 100%;
            position: sticky;
            top: 60px;
            overflow-y: auto;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            height: 100%;
            padding-bottom: 50px;
        }

        .album-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            text-align: center;
            transition: transform 0.2s;
            flex: 0 0 calc(50% - 10px);
            margin: 5px;
            height: auto;
        }

        .album-card:hover {
            transform: scale(1.03);
        }

        .album-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .post-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin: 24px auto;
            overflow: hidden;
            width: 90%;
            max-width: 650px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .post-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .post-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
        }

        .post-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .post-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            padding: 30px;
        }

        .post-actions {
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid var(--border-color);
            background-color: #f9f9f9;
        }

        .post-action-button {
            background-color: transparent;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 14px;
            transition: color 0.3s;
        }

        .post-action-button:hover {
            color: #0056b3;
        }

        .action-btn {
            background: none;
            border: none;
            padding: 8px;
            color: var(--text-color);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .right-sidebar {
            width: 300px;
            padding: 20px;
            border-left: 1px solid var(--border-color);
            background: #ffffff;
            position: sticky;
            top: 60px;
            height: calc(100vh - 60px);
        }

        .profile-card {
            padding: 20px 0;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
            z-index: 1;
        }

        .online-users {
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }

        .online-users {
            max-height: 400px;
            overflow-y: auto;
            border-top: 1px solid var(--border-color);
            padding-top: 10px;
        }

        .online-users img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 12px;
        }

        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e1306c;
        }

        .profile-pic {
            border: 3px solid #e1306c;
        }

        .like-btn.liked {
            color: #dc3545;
        }

        .post-action-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .comment-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .comments-container {
            max-height: 400px;
            overflow-y: auto;
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-bottom: 10px;
        }

        .comment-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .comment-input {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 10px;
            border-top: 1px solid #eee;
            z-index: 10;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                border-right: none;
                position: relative;
                top: 0;
            }

            .right-sidebar {
                width: 100%;
                position: relative;
                height: auto;
                border-left: none;
                border-top: 1px solid var(--border-color);
            }

            .main-content {
                padding-top: 20px;
            }

            .album-card {
                flex: 0 0 calc(33.33% - 10px);
            }

            .post-card {
                width: 100%;
                max-width: 100%;
                margin: 16px 0;
            }

            .comments-container,
            .online-users {
                max-height: 300px;
            }
        }

        /* Footer Style */
        .footer {
            background: #ffffff;
            text-align: center;
            padding: 10px 0;
            border-top: 1px solid var(--border-color);
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top shadow-sm">
        <div class="container-fluid">
            <a href="" class="navbar-brand d-flex align-items-center">
                <img src="asset/memoria_logo.png" width="50" height="50">
            </a>
            <h2 class="gallery-title">Gallery</h2>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="mx-auto text-center">
                    <h4>Welcome, <?php echo htmlspecialchars($namalengkap); ?>!</h4>
                </div>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="dashboard.php">Home</a>
                    <a class="nav-link" href="album.php">Albums</a>
                    <a class="nav-link" href="foto.php">Photos</a>
                </div>
                <form action="logout.php" method="POST" class="d-inline">
                    <button class="btn btn-outline-danger nav-link-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <!-- Sidebar for Albums -->
        <aside class="sidebar">
            <h3>Albums</h3>
            <div class="album-highlight">
                <?php while ($album = $albumsResult->fetch_assoc()): ?>
                    <div class="album-card">
                        <img src="<?php echo htmlspecialchars($album['Cover'] ?: 'img/default-album.png'); ?>" alt="Album Cover">
                        <div class="p-2">
                            <strong><?php echo htmlspecialchars($album['NamaAlbum']); ?></strong>
                            <br>
                            <small class="text-muted">By: <?php echo htmlspecialchars($album['Owner']); ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <?php while ($photo = $photosResult->fetch_assoc()): ?>
                <div class="post-card">
                    <div class="post-header d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($photo['UserProfilePhoto'] ?: 'img/profile.png'); ?>" alt="Profile" class="profile-pic">
                        <div class="ms-3">
                            <strong><?php echo htmlspecialchars($photo['NamaLengkap']); ?></strong>
                        </div>
                        <div class="ms-3">
                            <small class="text-muted">
                                <?php echo date('F j, Y', strtotime($photo['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <img src="<?php echo htmlspecialchars($photo['LokasiFile']); ?>" class="post-image" alt="Post">
                    <div class="post-actions">
                        <button class="post-action-button like-btn <?php echo $photo['UserLiked'] ? 'liked' : ''; ?>"
                            data-foto-id="<?php echo $photo['FotoID']; ?>">
                            <i class="fas fa-heart"></i>
                            <span class="like-count"><?php echo $photo['LikeCount']; ?></span>
                        </button>
                        <button class="post-action-button comment-btn"
                            data-foto-id="<?php echo $photo['FotoID']; ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#commentModal">
                            <i class="fas fa-comment"></i>
                            Comment
                        </button>
                    </div>
                    <div class="p-3">
                        <p class="mb-1">
                            <strong><?php echo htmlspecialchars($photo['JudulFoto']); ?></strong>
                        </p>
                        <p><?php echo htmlspecialchars($photo['DeskripsiFoto']); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </main>

        <!-- Comment Modal -->
        <div class="modal fade" id="commentModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Comments</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-7">
                                <img src="" class="img-fluid modal-photo" alt="Post">
                            </div>
                            <div class="col-md-5">
                                <div class="comment-input">
                                    <form id="commentForm" class="d-flex gap-2">
                                        <input type="text" class="form-control" placeholder="Add a comment...">
                                        <button type="submit" class="btn btn-primary">Post</button>
                                    </form>
                                </div>
                                <div class="comments-container"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar for Online Users -->
        <aside class="right-sidebar">
            <div class="profile-card">
                <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile" class="profile-photo">
                <h2><?php echo htmlspecialchars($namalengkap); ?></h2>
                <p>@<?php echo htmlspecialchars($username); ?></p>
            </div>
            <h3>Pengguna Lain</h3>
            <div class="online-users">
                <?php while ($otherUser = $otherUsersResult->fetch_assoc()): ?>
                    <div class="user-item">
                        <img src="<?php echo htmlspecialchars($otherUser['ProfilePhoto'] ?: 'img/profile.png'); ?>" alt="User Photo">
                        <span><?php echo htmlspecialchars($otherUser['NamaLengkap']); ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </aside>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Your Gallery. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle likes
            document.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const fotoID = this.dataset.fotoId;
                    const likeCount = this.querySelector('.like-count');

                    fetch('dashboard.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=toggleLike&fotoID=${fotoID}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'liked') {
                                this.classList.add('liked');
                                likeCount.textContent = parseInt(likeCount.textContent) + 1;
                            } else {
                                this.classList.remove('liked');
                                likeCount.textContent = parseInt(likeCount.textContent) - 1;
                            }
                        });
                });
            });

            // Handle comments
            let currentFotoID = null;

            document.querySelectorAll('.comment-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    currentFotoID = this.dataset.fotoId;
                    const modal = document.querySelector('#commentModal');
                    const photoUrl = this.closest('.post-card').querySelector('.post-image').src;
                    modal.querySelector('.modal-photo').src = photoUrl;

                    // Load comments
                    loadComments(currentFotoID);
                });
            });

            function loadComments(fotoID) {
                fetch(`dashboard.php?action=getComments&fotoID=${fotoID}`)
                    .then(response => response.json())
                    .then(comments => {
                        const container = document.querySelector('.comments-container');
                        container.innerHTML = '';

                        comments.forEach(comment => {
                            container.innerHTML += `
                                <div class="comment-item">
                                    <strong>${comment.NamaLengkap}</strong>
                                    <p>${comment.IsiKomentar}</p>
                                    <small class="text-muted">${new Date(comment.TanggalKomentar).toLocaleDateString()}</small>
                                </div>
                            `;
                        });
                    });
            }

            // Handle comment submission
            document.getElementById('commentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const input = this.querySelector('input');
                const comment = input.value.trim();

                if (comment && currentFotoID) {
                    fetch('dashboard.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=addComment&fotoID=${currentFotoID}&comment=${encodeURIComponent(comment)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                input.value = '';
                                loadComments(currentFotoID);
                            }
                        });
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>