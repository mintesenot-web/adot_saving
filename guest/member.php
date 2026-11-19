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

$members_list = $conn->query("SELECT * FROM members");

$my_total_saving = 0;
$my_savings_history = null;
$my_data = null;

// ቪዚተር መሆኑን ወይም አለመሆኑን ማረጋገጫ (member_id የሌለው Visitor ከሆነ true ይሆናል)
$is_visitor = (!isset($_SESSION['member_id']) || empty($_SESSION['member_id']));

if (!$is_visitor) {
    $my_member_id = intval($_SESSION['member_id']);
    
    $my_profile_result = $conn->query("SELECT * FROM members WHERE members_id = $my_member_id LIMIT 1");
    $my_data = $my_profile_result->fetch_assoc();

    if ($my_data) {
        $sum_result = $conn->query("SELECT SUM(amount) AS total FROM savings WHERE member_id = $my_member_id");
        if ($sum_row = $sum_result->fetch_assoc()) {
            $my_total_saving = $sum_row['total'] ? $sum_row['total'] : 0;
        }
        $my_savings_history = $conn->query("SELECT * FROM savings WHERE member_id = $my_member_id ORDER BY saving_date DESC");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Adot Saving</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(rgba(11, 28, 60, 0.75), rgba(16, 42, 69, 0.75)), url('../bg.jpg'); 
            background-size: cover; 
            background-position: center;
            background-attachment: scroll; 
            color: #333; 
            margin: 0; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
        }
        .navbar { background-color: #0b1c3c; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f39c12; position: relative; }
        .nav-brand { display: flex; align-items: center; gap: 10px; color: white; text-decoration: none; cursor: pointer; }
        .nav-brand img { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: white; padding: 2px; }
        .nav-brand span { font-size: 20px; font-weight: bold; color: #f39c12; }
        .nav-links { list-style: none; display: flex; gap: 15px; margin: 0; padding: 0; align-items: center; }
        .nav-links li a { color: white; text-decoration: none; font-weight: 600; font-size: 15px; padding: 8px 15px; border-radius: 4px; transition: all 0.3s; }
        .nav-links li a:hover { background-color: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .nav-links li a.active { background-color: #f39c12; color: #0b1c3c; }
        .logout-btn { background-color: #e74c3c; color: white; }

        .menu-toggle { display: none; flex-direction: column; cursor: pointer; gap: 5px; background: none; border: none; padding: 5px; }
        .menu-toggle span { width: 25px; height: 3px; background-color: #f39c12; border-radius: 2px; }

        .container { flex: 1; max-width: 950px; margin: 30px auto; background: rgba(255, 255, 255, 0.95); padding: 30px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); width: 90%; box-sizing: border-box; }
        .my-card { background: #0b1c3c; color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid #f39c12; flex-wrap: wrap; gap: 15px; }
        .my-card h3 { margin: 0 0 5px 0; color: #f39c12; }
        .saving-amount { font-size: 24px; font-weight: bold; color: #2ecc71; }

        h2 { color: #0b1c3c; border-bottom: 2px solid #f39c12; padding-bottom: 8px; margin-top: 30px; font-size: 20px; }
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 15px; border-radius: 6px; box-shadow: 0 3px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #0b1c3c; color: white; }
        tr:hover { background-color: #f1f1f1; }

        /* Rectangular Photo Thumbnail Style */
        .member-thumb {
            width: 100px; height: 90px; object-fit: cover; border-radius: 4px; cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s; border: 2px solid #0b1c3c;
            will-change: transform; display: block;
        }
        .member-thumb:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        /* Modal Styles for Full Image Popup */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); justify-content: center; align-items: center;
        }
        .modal-content {
            max-width: 90%; max-height: 90vh; border-radius: 8px; border: 4px solid #f39c12;
            object-fit: contain; background: white; padding: 5px; box-shadow: 0 5px 25px rgba(0,0,0,0.5);
        }
        .close-modal {
            position: absolute; top: 20px; right: 35px; color: #f1f1f1; font-size: 40px; font-weight: bold; cursor: pointer;
        }
        .close-modal:hover { color: #f39c12; }

        footer { background-color: #f39c12; color: #0b1c3c; text-align: center; padding: 5px 0; font-size: 13px; font-weight: bold; border-top: 2px solid #0b1c3c; margin-top: auto; }

        @media (max-width: 768px) {
            .menu-toggle { display: flex; }
            .nav-links {
                display: none; flex-direction: column; position: absolute; top: 100%; left: 0; width: 100%;
                background-color: #0b1c3c; padding: 15px 0; border-bottom: 3px solid #f39c12; box-shadow: 0 10px 15px rgba(0,0,0,0.3); z-index: 1000;
            }
            .nav-links.active { display: flex; }
            .nav-links li { width: 100%; text-align: center; }
            .nav-links li a { display: block; padding: 10px; font-size: 16px; }
            .container { width: 95%; padding: 15px; margin: 15px auto; }
            .member-thumb { width: 70px; height: 65px; }
            th, td { padding: 8px 10px; font-size: 13px; }
        }
    </style>
    <script>
        window.onpageshow = (e) => e.persisted && location.reload();
        function toggleMenu() {
            document.getElementById('navLinks').classList.toggle('active');
        }
    </script>
</head>
<body>

    <nav class="navbar">
        <a href="home.php" class="nav-brand">
            <img src="../logo.jpg" alt="Logo">
            <span>Adot Saving</span>
        </a>
        <button class="menu-toggle" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav-links" id="navLinks">
            <li><a href="home.php">Home</a></li>
            <li><a href="member.php" class="active">Member</a></li>
            <li><a href="gallery.php">Gallery</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="../auth/logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php if ($my_data): ?>
            <div class="my-card">
                <div>
                    <h3>Welcome, <?php echo htmlspecialchars($my_data['full_name']); ?>!</h3>
                    <p style="margin: 0; color: #ddd;">Your Personal Savings Statement & History</p>
                    <p style="margin: 5px 0 0 0; font-size: 13px; color: #f39c12;">Member ID: <strong><?php echo $my_data['members_id']; ?></strong></p>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 14px; display: block; color: #f39c12;">Total Savings</span>
                    <span class="saving-amount"><?php echo number_format($my_total_saving, 2); ?> ETB</span>
                </div>
            </div>

            <h2>My Savings History</h2>
            <?php if ($my_savings_history && $my_savings_history->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Round / Month</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            while ($sav = $my_savings_history->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($sav['round_month']); ?></td>
                                <td><strong><?php echo number_format($sav['amount'], 2); ?> ETB</strong></td>
                                <td><?php echo htmlspecialchars($sav['saving_date']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: #666; background: #fff; padding: 15px; border-radius: 5px; border-left: 4px solid #f39c12;">
                    No savings records found for your ID yet.
                </p>
            <?php endif; ?>

        <?php else: ?>
            <div class="my-card" style="border-left-color: #3498db;">
                <div>
                    <h3>Welcome, Visitor (<?php echo htmlspecialchars($_SESSION['username']); ?>)!</h3>
                    <p style="margin: 0; color: #ddd;">You are logged in as a Guest Visitor. To view personal savings, please log in with your Member ID.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- All Members List -->
        <h2 style="margin-top: 40px;">All Members List</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <?php if (!$is_visitor): ?>
                            <th>ID</th>
                        <?php endif; ?>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Sex</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $members_list->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="../member/photo/<?php echo htmlspecialchars($row['photo']); ?>" 
                                 class="member-thumb" 
                                 loading="lazy"
                                 decoding="async"
                                 onclick="openModal(this.src)" 
                                 alt="Member Photo" 
                                 title="Click to view full photo">
                        </td>           
                        <?php if (!$is_visitor): ?>
                            <td><?php echo htmlspecialchars($row['members_id']); ?></td>
                        <?php endif; ?>
                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                        <td>
                            <?php 
                            if ($is_visitor) {
                                $full_phone = $row['phone'];
                                if (strlen($full_phone) >= 4) {
                                    echo htmlspecialchars(substr($full_phone, 0, 4) . '******');
                                } else {
                                    echo "09********";
                                }
                            } else {
                                echo htmlspecialchars($row['phone']);
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['sex']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Image Modal Popup -->
    <div id="photoModal" class="modal" onclick="closeModal()">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="fullImage">
    </div>

    <script>
        function openModal(src) {
            const modal = document.getElementById('photoModal');
            const fullImg = document.getElementById('fullImage');
            modal.style.display = "flex";
            fullImg.src = src;
        }

        function closeModal() {
            document.getElementById('photoModal').style.display = "none";
        }
    </script>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>
</body>
</html>