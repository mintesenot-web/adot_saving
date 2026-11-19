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

// የአባሉን ID መቀበል
$member_id = $_GET['id'] ?? 0;
if (empty($member_id)) {
    header("Location: member.php");
    exit();
}

// 1. የአባሉን የግል መረጃ ማምጣት
$member_stmt = $conn->prepare("SELECT * FROM members WHERE members_id = ?");
$member_stmt->bind_param("i", $member_id);
$member_stmt->execute();
$member_result = $member_stmt->get_result();

if ($member_result->num_rows == 0) {
    header("Location: member.php");
    exit();
}
$member = $member_result->fetch_assoc();

// 2. የዚህን አባል የቁጠባ ታሪክ በሙሉ (History) ማምጣት
$history_stmt = $conn->prepare("SELECT * FROM savings WHERE member_id = ? ORDER BY saving_date DESC, saving_id DESC");
$history_stmt->bind_param("i", $member_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// 3. አጠቃላይ የቆጠበውን ገንዘብ ድምር ማስላት
$total_stmt = $conn->prepare("SELECT SUM(amount) AS total FROM savings WHERE member_id = ?");
$total_stmt->bind_param("i", $member_id);
$total_stmt->execute();
$total_res = $total_stmt->get_result()->fetch_assoc();
$grand_total = $total_res['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member History - Adot Saving</title>
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
            background: linear-gradient(rgba(11, 28, 60, 0.85), rgba(16, 42, 69, 0.85)), url('../logo.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            color: #333; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; 
        }
        .navbar { background-color: #0b1c3c; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f39c12; position: relative; }
        .nav-brand { display: flex; align-items: center; gap: 10px; color: white; text-decoration: none; }
        .nav-brand img { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: white; padding: 2px; }
        .nav-brand span { font-size: 20px; font-weight: bold; color: #f39c12; }
        
        .nav-links { list-style: none; display: flex; gap: 15px; margin: 0; padding: 0; align-items: center; }
        .nav-links li a { color: white; text-decoration: none; font-weight: 600; font-size: 15px; padding: 8px 15px; border-radius: 4px; transition: all 0.3s; }
        .nav-links li a:hover { background-color: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .nav-links li a.active { background-color: #f39c12; color: #0b1c3c; }
        .nav-links li a.logout-btn { background-color: #e74c3c; color: white; }
        .nav-links li a.logout-btn:hover { background-color: #c0392b; color: white; }

        /* Hamburger Menu Button ለሞባይል */
        .menu-toggle { display: none; flex-direction: column; cursor: pointer; gap: 5px; background: none; border: none; padding: 5px; }
        .menu-toggle span { width: 25px; height: 3px; background-color: #f39c12; border-radius: 2px; }

        .container { flex: 1; max-width: 900px; margin: 30px auto; background: rgba(255, 255, 255, 0.96); padding: 30px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 90%; box-sizing: border-box; }
        
        .profile-header { display: flex; align-items: center; gap: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .profile-img { width: 90px; height: 90px; border-radius: 8px; object-fit: cover; border: 2px solid #0b1c3c; }
        .profile-info h2 { margin: 0 0 5px 0; color: #0b1c3c; font-size: 24px; }
        .profile-info p { margin: 3px 0; color: #555; font-size: 14px; }
        
        .history-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .total-badge { background: #27ae60; color: white; padding: 8px 15px; border-radius: 5px; font-weight: bold; font-size: 15px; }
        
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 14px; }
        th { background-color: #0b1c3c; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        .btn-back { background-color: #2980b9; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; display: inline-block; margin-bottom: 20px; }
        .btn-back:hover { background-color: #1f6391; }

        footer { background-color: #f39c12; color: #0b1c3c; text-align: center; padding: 15px 0; margin-top: auto; font-size: 15px; font-weight: bold; border-top: 2px solid #0b1c3c; }

        /* Responsive Design ለሞባይል */
        @media (max-width: 768px) {
            .menu-toggle { display: flex; }
            .nav-links {
                display: none; flex-direction: column; position: absolute; top: 100%; left: 0; width: 100%;
                background-color: #0b1c3c; padding: 15px 0; border-bottom: 3px solid #f39c12; box-shadow: 0 10px 15px rgba(0,0,0,0.3); z-index: 1000;
            }
            .nav-links.active { display: flex; }
            .nav-links li { width: 100%; text-align: center; }
            .nav-links li a { display: block; padding: 10px; font-size: 16px; }
            .container { width: 95% !important; padding: 15px !important; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="../home.php" class="nav-brand">
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
            <li><a href="member.php" class="active">Member</a></li>
            <li><a href="../gallery/gallery.php">Gallery</a></li>
            <li><a href="../contact/contact.php">Contact</a></li>
            <li><a href="../about/about.php">About</a></li>
            <li><a href="../auth/logout.php" class="logout-btn">Logout (admin)</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <div class="container">
        
        <a href="member.php" class="btn-back">← Back to Members</a>

        <!-- Member Profile Header -->
        <div class="profile-header">
            <?php if (!empty($member['photo']) && file_exists('photo/' . $member['photo'])): ?>
                <img src="photo/<?php echo htmlspecialchars($member['photo']); ?>" class="profile-img" alt="Member Photo">
            <?php else: ?>
                <img src="photo/default.png" class="profile-img" alt="Default Photo">
            <?php endif; ?>
            
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($member['full_name']); ?> (#<?php echo $member['members_id']; ?>)</h2>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?></p>
                <p><strong>Sex:</strong> <?php echo htmlspecialchars($member['sex']); ?></p>
            </div>
        </div>

        <div class="history-header-flex">
            <h3 style="margin: 0; color: #0b1c3c;">Saving Transaction History</h3>
            <div class="total-badge">Total Savings: <?php echo number_format($grand_total, 2); ?> ETB</div>
        </div>

        <!-- History Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Round / Month</th>
                        <th>Saving Date</th>
                        <th>Amount (ETB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($history_result && $history_result->num_rows > 0) {
                        $count = 1;
                        while ($row = $history_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo htmlspecialchars($row['round_month']); ?></td>
                        <td><?php echo htmlspecialchars($row['saving_date']); ?></td>
                        <td><strong><?php echo number_format($row['amount'], 2); ?> ETB</strong></td>
                    </tr>
                    <?php 
                        endwhile; 
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; color:#777;'>No saving history found for this member.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('navLinks').classList.toggle('active');
        }
    </script>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>

</body>
</html>