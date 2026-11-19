<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - Adot Saving Group</title>
    <?php
// ብሮውዘር ኬሽ እንዳይዝ (No Cache) ለማድረግ
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
?>
<script>
    window.onpageshow = (e) => e.persisted && location.reload();
</script>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(rgba(11, 28, 60, 0.75), rgba(16, 42, 69, 0.75)), url('../bg.jpg');
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
        
        .navbar { 
            background-color: #0b1c3c; 
            padding: 12px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 3px solid #f39c12; 
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        .nav-brand img { 
            width: 45px; 
            height: 45px; 
            object-fit: contain; 
            border-radius: 50%;
            background: white;
            padding: 2px;
            transition: transform 0.2s;
        }
        .nav-brand img:hover { transform: scale(1.08); }
        .nav-brand span {
            font-size: 20px;
            font-weight: bold;
            color: #f39c12;
        }
        .nav-links { 
            list-style: none; 
            display: flex; 
            gap: 15px; 
            margin: 0; 
            padding: 0; 
            align-items: center;
        }
        .nav-links li a { 
            color: white; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 15px; 
            padding: 8px 15px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .nav-links li a:hover { 
            background-color: rgba(243, 156, 18, 0.2);
            color: #f39c12; 
        }
        .nav-links li a.active {
            background-color: #f39c12;
            color: #0b1c3c;
        }

        /* Logout (admin) አዝራር ዲዛይን */
        .nav-links li a.logout-btn { background-color: #e74c3c; color: white; }
        .nav-links li a.logout-btn:hover { background-color: #c0392b; color: white; }

        /* ሎጎ ሲነካ የሚከፈት Modal ዲዛይን */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .modal-content { max-width: 85%; max-height: 85%; border-radius: 8px; box-shadow: 0 0 25px rgba(255,255,255,0.2); object-fit: contain; }
        .close-modal { position: absolute; top: 20px; right: 35px; color: #f39c12; font-size: 40px; font-weight: bold; cursor: pointer; }

        .container { 
            flex: 1; 
            max-width: 900px; 
            margin: 40px auto; 
            background: linear-gradient(rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.92)), url('../bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.4); 
            width: 90%; 
        }
        
        .about-header { text-align: center; margin-bottom: 30px; }
        .about-header h1 { color: #0b1c3c; font-size: 32px; margin-bottom: 10px; }
        .about-header p { color: #555; font-size: 16px; }

        .about-section {
            margin-bottom: 25px;
            background: rgba(11, 28, 60, 0.03);
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #f39c12;
        }
        .about-section h3 { color: #0b1c3c; margin-top: 0; font-size: 20px; }
        .about-section p { color: #444; font-size: 15px; line-height: 1.6; margin-bottom: 0; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .feature-box {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #0b1c3c;
            text-align: center;
        }
        .feature-box h4 { color: #0b1c3c; margin-top: 0; font-size: 17px; }
        .feature-box p { color: #666; font-size: 14px; margin-bottom: 0; }

        /* የ Footer ቁመት ከሆም ጋር ተመሳሳይ እንዲሆን ተደርጓል */
        footer { 
            background-color: #f39c12; 
            color: #0b1c3c; 
            text-align: center; 
            padding: 5px 0; 
            margin-top: auto; 
            font-size: 13px; 
            font-weight: bold;
            border-top: 2px solid #0b1c3c; 
        }
    </style>
    <script>
        function openModal() {
            document.getElementById('logoModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('logoModal').style.display = 'none';
        }
    </script>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="home.php" class="nav-brand">
            <img src="../logo.jpg" alt="Adot Logo">
            <span>Adot Saving</span>
        </a>
        <ul class="nav-links">
            <li><a href="home.php">Home</a></li>
            <li><a href="member.php">Member</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="about.php" class="active">About</a></li>
            <!-- Logout (admin) አዝራር -->
            <li><a href="../auth/logout.php" class="logout-btn">Logout (admin)</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <div class="about-header">
            <h1>About Adot Saving Group</h1>
            <p>Empowering our community through structured financial growth, trust, and mutual support.</p>
        </div>

        <div class="about-section">
            <h3>Who We Are</h3>
            <p>Adot Saving Group is a dedicated community-based financial initiative consisting of 16 committed members. Alongside our dedicated saving programs, we proudly embrace the values of <strong>"I Care for the Nation"</strong> to foster social welfare, community support, and sustainable economic development.</p>
        </div>

        <!-- ስለ እናታችን ታየች ታደሰ (ታዩ) የተካተተበት ልዩ ክፍል -->
        <div class="about-section" style="border-left-color: #8e44ad; background: rgba(142, 68, 173, 0.04);">
            <h3 style="color: #8e44ad;">Our Inspiration: Mother Tayech Tagese (Tayu)</h3>
            <p>ይህንን የቁጠባ እና የማህበራዊ ስራ ሃሳብ እንድንጀምር መሠረት የጣችልን እና ሁልጊዜም በበጎ አድራጎት ስራዎቿ የምታነሳሳን ድንቅ እናታችን <strong>ታየች ታገሰ (ታዩ)</strong> ናት። የ<strong>"I Care for the Nation"</strong> ማህበራዊ ስራዎችን በማስተባበርና ህብረተሰቡን በመርዳት የምታሳየው ትጋት ለእኛ ትልቅ ምሳሌ ነው። በጣም ጥሩ እናታችን ናት፤ ለህብረተሰብ እድገት እና ለትውልድ መረዳዳት ያላት ፍቅር ለዚህ ግሩፕ መፈጠር ዋና ምክንያት ሆኗል።</p>
        </div>

        <div class="about-section">
            <h3>Our Mission & Vision</h3>
            <p>Our mission is to build a transparent, reliable, and secure platform where members can systematically save, manage funds, track financial progress, and strengthen community ties. We envision a financially secure community built on strong trust and collaboration.</p>
        </div>

        <div class="features-grid">
            <div class="feature-box">
                <h4>🔒 Secure Management</h4>
                <p>Digital tracking of individual savings, weekly reports, and transparent fund management.</p>
            </div>
            <div class="feature-box">
                <h4>🤝 Community First</h4>
                <p>Strong collaboration with members and the "I Care for the Nation" social initiatives inspired by our mother Tayu.</p>
            </div>
            <div class="feature-box">
                <h4>📊 Transparent Records</h4>
                <p>Easy access to transaction histories, member details, and memorable community moments in our gallery.</p>
            </div>
        </div>
    </div>

    <!-- Logo Modal (የሎጎ ፎቶ በትልቅ ማሳያ) -->
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