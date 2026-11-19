<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
// ከዳታቤዝ ጋር ማገናኘት
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Adot Saving Group</title>
    <?php
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <script>
        window.onpageshow = (e) => e.persisted && location.reload();
    </script>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(rgba(11, 28, 60, 0.75), rgba(16, 42, 69, 0.75)), url('../logo.jpg');
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed;
            color: #333; 
            margin: 0; 
            padding: 0; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
        }
        
        .navbar { background-color: #0b1c3c; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f39c12; position: relative; }
        .nav-brand { display: flex; align-items: center; gap: 10px; color: white; text-decoration: none; cursor: pointer; }
        .nav-brand img { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: white; padding: 2px; transition: transform 0.2s; }
        .nav-brand img:hover { transform: scale(1.08); }
        .nav-brand span { font-size: 20px; font-weight: bold; color: #f39c12; }
        
        .nav-links { list-style: none; display: flex; gap: 15px; margin: 0; padding: 0; align-items: center; }
        .nav-links li a { color: white; text-decoration: none; font-weight: 600; font-size: 15px; padding: 8px 15px; border-radius: 4px; transition: all 0.3s; }
        .nav-links li a:hover { background-color: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .nav-links li a.active { background-color: #f39c12; color: #0b1c3c; }

        /* Logout አዝራር ዲዛይን */
        .nav-links li a.logout-btn { background-color: #e74c3c; color: white; }
        .nav-links li a.logout-btn:hover { background-color: #c0392b; color: white; }

        /* Hamburger Menu Button (ለሞባይል) */
        .menu-toggle { display: none; flex-direction: column; cursor: pointer; gap: 5px; background: none; border: none; padding: 5px; }
        .menu-toggle span { width: 25px; height: 3px; background-color: #f39c12; border-radius: 2px; }

        /* ሎጎ ሲነካ የሚከፈት Modal ዲዛይን */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .modal-content { max-width: 85%; max-height: 85%; border-radius: 8px; box-shadow: 0 0 25px rgba(255,255,255,0.2); object-fit: contain; }
        .close-modal { position: absolute; top: 20px; right: 35px; color: #f39c12; font-size: 40px; font-weight: bold; cursor: pointer; }

        .container { 
            flex: 1; 
            max-width: 1000px; 
            margin: 40px auto; 
            background: linear-gradient(rgba(255, 255, 255, 0.31), rgba(255, 255, 255, 0.33)), url('../bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.5); 
            width: 90%; 
            box-sizing: border-box;
        }
        
        .gallery-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .gallery-header h1 { color: #0b1c3c; font-size: 30px; margin: 0; text-shadow: 1px 1px 2px rgba(255,255,255,0.8); }
        .btn-add { background-color: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-add:hover { background-color: #219150; }

        .categories-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-top: 20px; }
        .category-card { background: rgba(255, 255, 255, 0.95); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.15); border-top: 5px solid #0b1c3c; text-decoration: none; transition: transform 0.3s, box-shadow 0.3s; display: flex; flex-direction: column; }
        .category-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.25); }
        .category-card.highlight { border-top-color: #f39c12; }
        
        .category-body { padding: 30px 20px; text-align: center; }
        .category-body h3 { margin: 0 0 10px 0; color: #0b1c3c; font-size: 20px; }
        .category-body p { margin: 0; color: #444; font-size: 14px; }
        .category-footer { background: #f8f9fa; padding: 12px; text-align: center; font-weight: bold; color: #2980b9; font-size: 14px; border-top: 1px solid #eee; }

        footer { background-color: #f39c12; color: #0b1c3c; text-align: center; padding: 5px 0; font-size: 13px; font-weight: bold; border-top: 2px solid #0b1c3c; margin-top: auto; }

        /* 📱 Responsive Design for Mobile Phones */
        @media (max-width: 768px) {
            .menu-toggle { display: flex; }
            .nav-links {
                display: none; flex-direction: column; position: absolute; top: 100%; left: 0; width: 100%;
                background-color: #0b1c3c; padding: 15px 0; border-bottom: 3px solid #f39c12; box-shadow: 0 10px 15px rgba(0,0,0,0.3); z-index: 1000;
            }
            .nav-links.active { display: flex; }
            .nav-links li { width: 100%; text-align: center; }
            .nav-links li a { display: block; padding: 10px; font-size: 16px; }
            
            .container { width: 95% !important; padding: 20px !important; margin: 20px auto; }
            .gallery-header h1 { font-size: 22px; }
            .gallery-header { flex-direction: column; align-items: stretch; text-align: center; }
            .btn-add { text-align: center; display: block; }
        }
    </style>
    <script>
        function openModal() {
            document.getElementById('logoModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('logoModal').style.display = 'none';
        }
        function toggleMenu() {
            document.getElementById('navLinks').classList.toggle('active');
        }
    </script>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a class="nav-brand" onclick="openModal()">
            <img src="../logo.jpg" alt="Adot Logo">
            <span>Adot Saving</span>
        </a>
        <button class="menu-toggle" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links" id="navLinks">
            <li><a href="../home.php">Home</a></li>
            <li><a href="../member/member.php">Member</a></li>
            <li><a href="gallery.php" class="active">Gallery</a></li>
            <li><a href="../contact/contact.php">Contact</a></li>
            <li><a href="../about/about.php">About</a></li>
            <li><a href="../auth/logout.php" class="logout-btn">Logout (admin)</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <div class="gallery-header">
            <div>
                <h1>Adot Saving Group Gallery</h1>
                <p style="color: #333; font-weight: 600; margin: 5px 0 0 0;">Select a category to view photos by date.</p>
            </div>
            <a href="photos.php" class="btn-add">+ Add Photos</a>
        </div>

        <!-- 3ቱ ዋና አማራጮች (Categories Cards) -->
        <div class="categories-grid">
            
            <a href="view_category.php?cat=Monthly Meeting Session" class="category-card">
                <div class="category-body">
                    <h3>Monthly Meeting Session</h3>
                    <p>View all monthly meeting photos and sessions.</p>
                </div>
                <div class="category-footer">View Photos →</div>
            </a>

            <a href="view_category.php?cat=Saving Event Gathering" class="category-card highlight">
                <div class="category-body">
                    <h3>Saving Event Gathering</h3>
                    <p>View all saving event and gathering photos.</p>
                </div>
                <div class="category-footer">View Photos →</div>
            </a>

            <a href="view_category.php?cat=Adot Community Members" class="category-card">
                <div class="category-body">
                    <h3>Adot Community Members</h3>
                    <p>View all community member photos and portraits.</p>
                </div>
                <div class="category-footer">View Photos →</div>
            </a>

        </div>
    </div>

    <!-- Logo Modal -->
    <div id="logoModal" class="modal" onclick="closeModal()">
        <span class="close-modal">&times;</span>
        <img class="modal-content" src="../logo.jpg" alt="Adot Logo Large">
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>

</body>
</html>