<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guest') {
    header("Location: ../auth/login.php");
    exit();
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// ከዳታቤዝ ጋር ማገናኘት (ከ guest ፎልደር ወደ ውጭ ወጥቶ db.php ይጠራል)
include '../db.php';

$category = $_GET['cat'] ?? '';
if (empty($category)) {
    header("Location: gallery.php");
    exit();
}

// የተመረጠውን ምድብ ፎቶዎች በቀን ቅደም ተከተል ማምጣት
$stmt = $conn->prepare("SELECT * FROM gallery WHERE category = ? ORDER BY upload_date DESC, gallery_id DESC");
$stmt->bind_param("s", $category);
$stmt->execute();
$gallery_result = $stmt->get_result();

// ፎቶዎችን በቀናቸው (Date) መክፈል
$grouped_photos = [];
if ($gallery_result && $gallery_result->num_rows > 0) {
    while ($row = $gallery_result->fetch_assoc()) {
        $grouped_photos[$row['upload_date']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($category); ?> - Adot Saving</title>
    <script>
        window.onpageshow = (e) => e.persisted && location.reload();
    </script>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(rgba(11, 28, 60, 0.85), rgba(16, 42, 69, 0.85)), url('../bg.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            color: #333; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; 
        }
        .navbar { background-color: #0b1c3c; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f39c12; }
        .nav-brand { display: flex; align-items: center; gap: 10px; color: white; text-decoration: none; cursor: pointer; }
        .nav-brand img { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: white; padding: 2px; }
        .nav-brand span { font-size: 20px; font-weight: bold; color: #f39c12; }
        .nav-links { list-style: none; display: flex; gap: 15px; margin: 0; padding: 0; align-items: center; }
        .nav-links li a { color: white; text-decoration: none; font-weight: 600; font-size: 15px; padding: 8px 15px; border-radius: 4px; transition: all 0.3s; }
        .nav-links li a:hover { background-color: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .nav-links li a.active { background-color: #f39c12; color: #0b1c3c; }
        .logout-btn { background-color: #e74c3c; color: white; }

        .container { flex: 1; max-width: 1000px; margin: 40px auto; background: rgba(255, 255, 255, 0.96); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 90%; }
        
        .gallery-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .gallery-header h1 { color: #0b1c3c; font-size: 26px; margin: 0; }
        .btn-back { background-color: #2980b9; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; }
        .btn-back:hover { background-color: #1f6391; }

        .date-section { margin-bottom: 35px; }
        .date-title { color: #0b1c3c; font-size: 17px; border-bottom: 2px solid #f39c12; padding-bottom: 5px; margin-bottom: 15px; font-weight: bold; }
        
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .gallery-item { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: flex; flex-direction: column; cursor: pointer; transition: transform 0.3s; }
        .gallery-item:hover { transform: translateY(-5px); }
        
        .gallery-img-box {
            width: 100%;
            height: 200px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 3px solid #f39c12;
            overflow: hidden;
        }
        .gallery-item img { 
            max-width: 100%; 
            max-height: 100%; 
            object-fit: contain; 
        }
        
        .gallery-caption { padding: 12px; text-align: center; background: #fff; }
        .gallery-caption h4 { margin: 0; color: #0b1c3c; font-size: 14px; }

        /* Lightbox Modal */
        .lightbox-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .lightbox-content {
            max-width: 85%;
            max-height: 75vh;
            object-fit: contain;
            border: 3px solid #f39c12;
            border-radius: 5px;
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(243, 156, 18, 0.8);
            color: #0b1c3c;
            padding: 15px 20px;
            font-size: 24px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .lightbox-nav:hover { background-color: #f39c12; }
        .lightbox-prev { left: 30px; }
        .lightbox-next { right: 30px; }
        .lightbox-caption {
            color: #fff;
            margin-top: 15px;
            font-size: 18px;
            text-align: center;
        }

        footer { background-color: #f39c12; color: #0b1c3c; text-align: center; padding: 15px 0; margin-top: auto; font-size: 15px; font-weight: bold; border-top: 2px solid #0b1c3c; }
    </style>
</head>
<body>

    <!-- Navbar (ሁሉም ሊንኮች እዛው guest ፎልደር ውስጥ እንዲቆዩ ተደርገዋል) -->
    <nav class="navbar">
        <a href="home.php" class="nav-brand">
            <img src="../logo.jpg" alt="Adot Logo">
            <span>Adot Saving</span>
        </a>
        <ul class="nav-links">
            <li><a href="home.php">Home</a></li>
            <li><a href="member.php">Member</a></li>
            <li><a href="gallery.php" class="active">Gallery</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="../auth/logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="gallery-header">
            <div>
                <h1>Category: <?php echo htmlspecialchars($category); ?></h1>
                <p style="color: #555; margin: 5px 0 0 0;">Click on any photo to view in large screen with navigation.</p>
            </div>
            <!-- Back አዝራር ወደ guest/gallery.php ይመልሳል -->
            <a href="gallery.php" class="btn-back">← Back to Categories</a>
        </div>

        <?php 
        $all_photos_js = [];
        if (!empty($grouped_photos)) {
            foreach ($grouped_photos as $date => $photos) {
                echo '<div class="date-section">';
                echo '<div class="date-title">📅 Date: ' . htmlspecialchars($date) . ' (' . count($photos) . ' Photos)</div>';
                echo '<div class="gallery-grid">';
                foreach ($photos as $row) {
                    // ፎቶዎቹ የሚገኙበትን ፖዝ ከ guest ፎልደር አንፃር ወደ ዋናው ../gallery/uploads/ ጠቁመናል
                    $all_photos_js[] = ['src' => '../gallery/uploads/' . $row['photo_name'], 'caption' => $row['category'] . ' (' . $row['upload_date'] . ')'];
                    $index = count($all_photos_js) - 1;
                    
                    echo '<div class="gallery-item" onclick="openLightbox(' . $index . ')">';
                    echo '<div class="gallery-img-box">';
                    echo '<img src="../gallery/uploads/' . htmlspecialchars($row['photo_name']) . '" alt="Gallery Image">';
                    echo '</div>';
                    echo '<div class="gallery-caption">';
                    echo '<h4>' . htmlspecialchars($row['category']) . '</h4>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo "<p style='text-align: center; color: #7f8c8d; padding: 30px;'>No photos found in this category yet.</p>";
        }
        ?>
    </div>

    <!-- Lightbox Modal -->
    <div id="lightboxModal" class="lightbox-modal">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <button class="lightbox-nav lightbox-prev" onclick="changeSlide(-1)">&#10094;</button>
        <img class="lightbox-content" id="lightboxImg">
        <button class="lightbox-nav lightbox-next" onclick="changeSlide(1)">&#10095;</button>
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>

    <!-- JavaScript -->
    <script>
        const photosList = <?php echo json_encode($all_photos_js); ?>;
        let currentIndex = 0;

        function openLightbox(index) {
            currentIndex = index;
            document.getElementById('lightboxModal').style.display = 'flex';
            updateLightbox();
        }

        function closeLightbox() {
            document.getElementById('lightboxModal').style.display = 'none';
        }

        function changeSlide(direction) {
            currentIndex += direction;
            if (currentIndex >= photosList.length) {
                currentIndex = 0;
            } else if (currentIndex < 0) {
                currentIndex = photosList.length - 1;
            }
            updateLightbox();
        }

        function updateLightbox() {
            if(photosList.length > 0) {
                document.getElementById('lightboxImg').src = photosList[currentIndex].src;
                document.getElementById('lightboxCaption').innerText = photosList[currentIndex].caption;
            }
        }

        document.addEventListener('keydown', function(event) {
            if(document.getElementById('lightboxModal').style.display === 'flex') {
                if(event.key === 'ArrowLeft') {
                    changeSlide(-1);
                } else if(event.key === 'ArrowRight') {
                    changeSlide(1);
                } else if(event.key === 'Escape') {
                    closeLightbox();
                }
            }
        });
    </script>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>

</body>
</html>