<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include 'db.php';

// አጠቃላይ የአባላት ብዛት
$member_count_query = "SELECT COUNT(*) AS total_members FROM members";
$member_count_res = $conn->query($member_count_query)->fetch_assoc();
$total_members = $member_count_res['total_members'] ?? 0;

// አባላት የቆጠቡት አጠቃላይ የገንዘብ ድምር
$member_total_query = "SELECT SUM(amount) AS member_grand_total FROM savings";
$member_total_res = $conn->query($member_total_query)->fetch_assoc();
$member_grand_total = $member_total_res['member_grand_total'] ?? 0;

// I Care for the Nation ድርጅት ያስገባው አጠቃላይ ገንዘብ
$icare_total_query = "SELECT SUM(amount) AS icare_grand_total FROM icare_savings";
$icare_total_res = $conn->query($icare_total_query)->fetch_assoc();
$icare_grand_total = $icare_total_res['icare_grand_total'] ?? 0;

// አጠቃላይ ግሩፑ ያለበት ገንዘብ
$absolute_grand_total = $member_grand_total + $icare_grand_total;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adot Saving Group - Management System</title>
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
            background: linear-gradient(rgba(11, 28, 60, 0.75), rgba(16, 42, 69, 0.75)), url('bg.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            color: #333; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; 
        }
        .navbar { background-color: #0b1c3c; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f39c12; position: relative; }
        .nav-brand { display: flex; align-items: center; gap: 10px; color: white; text-decoration: none; cursor: pointer; }
        .nav-brand img { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: white; padding: 2px; transition: transform 0.2s; }
        .nav-brand img:hover { transform: scale(1.08); }
        .nav-brand span { font-size: 20px; font-weight: bold; color: #f39c12; }
        
        .nav-links { list-style: none; display: flex; gap: 15px; margin: 0; padding: 0; align-items: center; }
        .nav-links li a { color: white; text-decoration: none; font-weight: 600; font-size: 15px; padding: 8px 15px; border-radius: 4px; transition: all 0.3s ease; }
        .nav-links li a:hover { background-color: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .nav-links li a.active { background-color: #f39c12; color: #0b1c3c; }
        
        .nav-links li a.logout-btn { background-color: #e74c3c; color: white; }
        .nav-links li a.logout-btn:hover { background-color: #c0392b; color: white; }

        /* Hamburger Menu Button (ለሞባይል) */
        .menu-toggle { display: none; flex-direction: column; cursor: pointer; gap: 5px; background: none; border: none; padding: 5px; }
        .menu-toggle span { width: 25px; height: 3px; background-color: #f39c12; border-radius: 2px; }

        .container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; box-sizing: border-box; }
        .welcome-box { 
            background: rgba(255, 255, 255, 0.93); padding: 40px; border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.4); text-align: center; max-width: 650px; width: 100%;
            border-top: 6px solid #f39c12;
            box-sizing: border-box;
        }
        .welcome-box h1 { color: #0b1c3c; margin-top: 0; font-size: 32px; margin-bottom: 10px; }
        .welcome-box p { color: #555; font-size: 16px; margin-bottom: 30px; }
        .stats-container { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        .stat-card { background: #f8f9fa; border-top: 4px solid #0b1c3c; padding: 20px; border-radius: 8px; width: 260px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); box-sizing: border-box; }
        .stat-card h3 { margin: 0 0 8px 0; color: #0b1c3c; font-size: 15px; }
        .stat-card p { margin: 0; font-size: 22px; font-weight: bold; color: #27ae60; }
        
        /* ሎጎ ሲነካ የሚከፈት Modal ዲዛይን */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .modal-content { max-width: 85%; max-height: 85%; border-radius: 8px; box-shadow: 0 0 25px rgba(255,255,255,0.2); object-fit: contain; }
        .close-modal { position: absolute; top: 20px; right: 35px; color: #f39c12; font-size: 40px; font-weight: bold; cursor: pointer; }

        /* Footer */
        footer { background-color: #f39c12; color: #0b1c3c; text-align: center; padding: 5px 0; font-size: 13px; font-weight: bold; border-top: 2px solid #0b1c3c; }

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
            
            .container { padding: 20px 10px; }
            .welcome-box { padding: 25px 15px; }
            .welcome-box h1 { font-size: 24px; }
            .stats-container { flex-direction: column; align-items: center; }
            .stat-card { width: 100%; }
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
            <img src="logo.jpg" alt="Adot Logo">
            <span>Adot Saving</span>
        </a>
        <button class="menu-toggle" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links" id="navLinks">
            <li><a href="home.php" class="active">Home</a></li>
            <li><a href="member/member.php">Member</a></li>
            <li><a href="gallery/gallery.php">Gallery</a></li>
            <li><a href="contact/contact.php">Contact</a></li>
            <li><a href="about/about.php">About</a></li>
            <li><a href="auth/logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <div class="welcome-box">
            <h1>Welcome to Adot Saving Group!</h1>
            <p>Empowering our community through modern, transparent, and secure group savings management.</p>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Members</h3>
                    <p><?php echo $total_members; ?> Members</p>
                </div>
                <div class="stat-card">
                    <h3>Total Savings Amount</h3>
                    <p><?php echo number_format($absolute_grand_total, 2); ?> ETB</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Logo Modal -->
    <div id="logoModal" class="modal" onclick="closeModal()">
        <span class="close-modal">&times;</span>
        <img class="modal-content" src="logo.jpg" alt="Adot Logo Large">
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>

</body>
</html>