<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guest') {
    header("Location: ../auth/login.php");
    exit();
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include '../db.php';
// ካታጎሪዎችን ከዋናው የጋለሪ ቴብል ወይም ካታጎሪ ማምጣት
$categories_res = $conn->query("SELECT * FROM categories"); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gallery - Adot Saving Group</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(rgba(11, 28, 60, 0.75), rgba(16, 42, 69, 0.75)), url('../bg.jpg'); 
            background-size: cover; background-position: center; background-attachment: fixed;
            color: #333; margin: 0; display: flex; flex-direction: column; min-height: 100vh; 
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

        .container { flex: 1; max-width: 1000px; margin: 40px auto; background: rgba(255, 255, 255, 0.93); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); width: 90%; }
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
        }
        .gallery-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .gallery-header h2 { color: #0b1c3c; margin: 0; font-size: 26px; }
        .gallery-header p { color: #666; margin: 5px 0 0 0; font-size: 14px; }

        /* ካታጎሪ ኪዩብ/ካርዶች ዲዛይን (ከአድሚኑ ጋር ተመሳሳይ) */
        .category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .cat-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-top: 4px solid #0b1c3c; text-align: center; transition: transform 0.2s; }
        .cat-card:hover { transform: translateY(-4px); }
        .cat-card h3 { color: #0b1c3c; margin: 0 0 10px 0; font-size: 20px; }
        .cat-card p { color: #555; font-size: 13px; margin-bottom: 20px; }
        .cat-card a { display: inline-block; background: #0b1c3c; color: white; padding: 8px 20px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.3s; }
        .cat-card a:hover { background: #f39c12; color: #0b1c3c; }

        footer { background-color: #f39c12; color: #0b1c3c; text-align: center; padding: 5px 0; font-size: 13px; font-weight: bold; border-top: 2px solid #0b1c3c; margin-top: auto; }
    </style>
    <script>window.onpageshow = (e) => e.persisted && location.reload();</script>
</head>
<body>

    <nav class="navbar">
        <a href="home.php" class="nav-brand">
            <img src="../logo.jpg" alt="Logo">
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

    <div class="container">
        <div class="gallery-header">
            <div>
                <h2>Adot Saving Group Gallery</h2>
                <p>Select a category to view photos by date and events.</p>
            </div>
            <!-- እዚህ ጋር የነበረው "+ Add Photos" አዝራር ለእንግዳው ሙሉ በሙሉ ተወግዷል -->
        </div>

        <div class="category-grid">
            <?php if ($categories_res && $categories_res->num_rows > 0): ?>
                <?php while ($cat = $categories_res->fetch_assoc()): ?>
                    <div class="cat-card">
                        <h3><?php echo htmlspecialchars($cat['category_name']); ?></h3>
                        <p><?php echo htmlspecialchars($cat['description'] ?? 'View all photos and session records.'); ?></p>
                        <!-- አገናኙ ወደ guest/view_category.php ይመራል -->
                        <a href="view_category.php?cat=<?php echo urlencode($cat['category_name']); ?>">View Photos</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- ፋብሪኬትድ ወይም ነባር ካታጎሪዎች ካሉ በዲዛይን ማሳያነት -->
                <div class="cat-card">
                    <h3>Monthly Meeting Session</h3>
                    <p>View all monthly meeting photos and sessions.</p>
                    <a href="view_category.php?cat=Monthly Meeting Session">View Photos</a>
                </div>
                <div class="cat-card">
                    <h3>Saving Event Gathering</h3>
                    <p>View all saving event and gathering photos.</p>
                    <a href="view_category.php?cat=Saving Event Gathering">View Photos</a>
                </div>
                <div class="cat-card">
                    <h3>Adot Community Members</h3>
                    <p>View all community member photos and portraits.</p>
                    <a href="view_category.php?cat=Adot Community Members">View Photos</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>
</body>
</html>