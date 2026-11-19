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

// 1. አዲስ የአባላት ቁጠባ ሲመዘገብ
if (isset($_POST['save_money'])) {
    $member_id = $_POST['members_id'];
    $amount = $_POST['amount'];
    $saving_date = $_POST['saving_date'];
    $round_month = $_POST['round_month'];

    $stmt = $conn->prepare("INSERT INTO savings (member_id, amount, saving_date, round_month) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $member_id, $amount, $saving_date, $round_month);
    $stmt->execute();
    header("Location: member.php");
    exit();
}

// 2. የ I Care ፈንድ ሲመዘገብ
if (isset($_POST['save_icare'])) {
    $ic_amount = $_POST['amount'];
    $ic_date = $_POST['saving_date'];
    $ic_remark = $_POST['remark'];

    $stmt = $conn->prepare("INSERT INTO icare_savings (amount, saving_date, remark) VALUES (?, ?, ?)");
    $stmt->bind_param("dss", $ic_amount, $ic_date, $ic_remark);
    $stmt->execute();
    header("Location: member.php?show_icare=1");
    exit();
}

// 3. የቀድሞ / የተባረሩ አባላት ቁጠባ ሲመዘገብ
if (isset($_POST['save_former'])) {
    $f_name = trim($_POST['member_name']);
    $f_amount = $_POST['amount'];
    $f_date = $_POST['saving_date'];
    $f_remark = trim($_POST['remark']);

    $stmt = $conn->prepare("INSERT INTO former_savings (member_name, amount, saving_date, remark) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $f_name, $f_amount, $f_date, $f_remark);
    $stmt->execute();
    header("Location: member.php?show_former=1");
    exit();
}

// ጠቅላላ ቁጥሮችን ማስላት
$member_count_query = "SELECT COUNT(*) AS total_members FROM members";
$member_count_res = $conn->query($member_count_query)->fetch_assoc();
$total_members = $member_count_res['total_members'] ?? 0;

$member_total_query = "SELECT SUM(amount) AS member_grand_total FROM savings";
$member_total_res = $conn->query($member_total_query)->fetch_assoc();
$member_grand_total = $member_total_res['member_grand_total'] ?? 0;

// የቀድሞ/የተባረሩ አባላት ጠቅላላ ቁጠባ
$former_total_query = "SELECT SUM(amount) AS former_grand_total FROM former_savings";
$former_total_res = $conn->query($former_total_query)->fetch_assoc();
$former_grand_total = $former_total_res['former_grand_total'] ?? 0;

// የአሁኑ አባላት + የቀድሞ አባላት ጠቅላላ ድምር
$combined_savings_total = $member_grand_total + $former_grand_total;

$icare_total_query = "SELECT SUM(amount) AS icare_grand_total FROM icare_savings";
$icare_total_res = $conn->query($icare_total_query)->fetch_assoc();
$icare_grand_total = $icare_total_res['icare_grand_total'] ?? 0;

$search = $_GET['search'] ?? '';
if ($search != '') {
    $search_safe = $conn->real_escape_string($search);
    $members_query = "
        SELECT m.members_id, m.full_name, m.phone, m.sex, m.photo, m.loan_amount, COALESCE(SUM(s.amount), 0) AS individual_total 
        FROM members m 
        LEFT JOIN savings s ON m.members_id = s.member_id 
        WHERE m.members_id LIKE '%$search_safe%' OR m.full_name LIKE '%$search_safe%'
        GROUP BY m.members_id, m.full_name, m.phone, m.sex, m.photo, m.loan_amount
        ORDER BY m.members_id ASC
    ";
} else {
    $members_query = "
        SELECT m.members_id, m.full_name, m.phone, m.sex, m.photo, m.loan_amount, COALESCE(SUM(s.amount), 0) AS individual_total 
        FROM members m 
        LEFT JOIN savings s ON m.members_id = s.member_id 
        GROUP BY m.members_id, m.full_name, m.phone, m.sex, m.photo, m.loan_amount
        ORDER BY m.members_id ASC
    ";
}
$members_result = $conn->query($members_query);

$icare_history = $conn->query("SELECT * FROM icare_savings ORDER BY icare_id DESC");
$show_icare_modal = isset($_GET['show_icare']) ? true : false;

$former_history = $conn->query("SELECT * FROM former_savings ORDER BY former_id DESC");
$show_former_modal = isset($_GET['show_former']) ? true : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - Adot Saving</title>
    <script>
        window.onpageshow = (e) => e.persisted && location.reload();
    </script>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(rgba(11, 28, 60, 0.85), rgba(16, 42, 69, 0.85)), url('../logo.jpg');
            background-size: cover; background-position: center; background-attachment: scroll;
            color: #333; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 100vh; 
        }
        .navbar { background-color: #0b1c3c; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #f39c12; position: relative; }
        .nav-brand { display: flex; align-items: center; gap: 10px; color: white; text-decoration: none; cursor: pointer; }
        .nav-brand img { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: white; padding: 2px; }
        .nav-brand span { font-size: 20px; font-weight: bold; color: #f39c12; }
        
        .nav-links { list-style: none; display: flex; gap: 15px; margin: 0; padding: 0; align-items: center; }
        .nav-links li a { color: white; text-decoration: none; font-weight: 600; font-size: 15px; padding: 8px 15px; border-radius: 4px; transition: all 0.3s; }
        .nav-links li a:hover { background-color: rgba(243, 156, 18, 0.2); color: #f39c12; }
        .nav-links li a.active { background-color: #f39c12; color: #0b1c3c; }
        .nav-links li a.logout-btn { background-color: #e74c3c; color: white; }
        .nav-links li a.logout-btn:hover { background-color: #c0392b; }

        /* Hamburger Menu Button */
        .menu-toggle { display: none; flex-direction: column; cursor: pointer; gap: 5px; background: none; border: none; padding: 5px; }
        .menu-toggle span { width: 25px; height: 3px; background-color: #f39c12; border-radius: 2px; }

        .container { flex: 1; max-width: 1050px; margin: 30px auto; background: rgba(255, 255, 255, 0.96); padding: 30px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 90%; box-sizing: border-box; }
        
        .stats-container { display: flex; gap: 15px; justify-content: center; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-card { background: #f8f9fa; border-top: 4px solid #0b1c3c; padding: 18px; border-radius: 8px; width: 220px; box-shadow: 0 4px 10px rgba(0,0,0,0.06); text-align: center; transition: transform 0.2s; }
        .stat-card h3 { margin: 0 0 8px 0; color: #0b1c3c; font-size: 14px; }
        .stat-card p { margin: 0; font-size: 20px; font-weight: bold; color: #27ae60; }
        .stat-card small { display: block; margin-top: 5px; font-size: 11px; color: #7f8c8d; }
        
        .stat-card.clickable { cursor: pointer; }
        .stat-card.clickable:hover { transform: translateY(-3px); box-shadow: 0 6px 14px rgba(0,0,0,0.12); }
        
        .stat-card.former-card { border-top-color: #d35400; }
        .stat-card.former-card p { color: #d35400; }

        .stat-card.grand-card { border-top-color: #f39c12; background: #0b1c3c; }
        .stat-card.grand-card h3 { color: #f39c12; }
        .stat-card.grand-card p { color: #2ecc71; font-size: 22px; }
        .stat-card.grand-card small { color: #bdc3c7; }

        .stat-card.icare-card { border-top-color: #8e44ad; }
        .stat-card.icare-card p { color: #8e44ad; }

        /* Form Box & Inputs */
        .form-box { background: #ecf0f1; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; border: 1px solid #bdc3c7; }
        select, input, button { padding: 10px; border: 1px solid #bdc3c7; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .form-box select, .form-box input { flex: 1; }
        .btn { background-color: #27ae60; color: white; padding: 10px 18px; text-decoration: none; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; display: inline-block; text-align: center; }
        .btn:hover { background-color: #219150; }
        .btn-disabled { background-color: #95a5a6 !important; cursor: not-allowed; }
        
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .action-bar div { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .search-box { display: flex; gap: 8px; }
        .search-box input { width: 200px; }

        .table-responsive { width: 100%; overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 14px; vertical-align: middle; }
        th { background-color: #0b1c3c; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        
        td.photo-cell { padding: 0 !important; width: 100px; height: 70px; text-align: center; }
        .member-img { width: 100%; height: 100%; object-fit: cover; display: block; will-change: transform; }
        
        .action-links a { margin-right: 8px; text-decoration: none; font-weight: bold; font-size: 13px; }
        .history-link { color: #2980b9; }
        .edit-link { color: #e67e22; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); justify-content: center; align-items: center; }
        .modal-content { max-width: 85%; max-height: 85%; border-radius: 8px; box-shadow: 0 0 25px rgba(255,255,255,0.2); object-fit: contain; }
        .close-modal { position: absolute; top: 20px; right: 35px; color: #f39c12; font-size: 40px; font-weight: bold; cursor: pointer; }

        .custom-modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .icare-modal { display: <?php echo $show_icare_modal ? 'flex' : 'none'; ?>; }
        .former-modal { display: <?php echo $show_former_modal ? 'flex' : 'none'; ?>; }
        
        .custom-modal-content { background: white; padding: 30px; border-radius: 8px; width: 520px; max-width: 92%; max-height: 85vh; overflow-y: auto; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .close-btn { position: absolute; right: 15px; top: 10px; font-size: 24px; cursor: pointer; color: #7f8c8d; }

        footer { background-color: #f39c12; color: #0b1c3c; text-align: center; padding: 5px 0; font-size: 13px; font-weight: bold; border-top: 2px solid #0b1c3c; margin-top: auto; }

        /* 📱 ለጠባብ ስልኮች ብቻ (Mobile Responsive) */
        @media (max-width: 768px) {
            .menu-toggle { display: flex; }
            .nav-links {
                display: none; flex-direction: column; position: absolute; top: 100%; left: 0; width: 100%;
                background-color: #0b1c3c; padding: 15px 0; border-bottom: 3px solid #f39c12; box-shadow: 0 10px 15px rgba(0,0,0,0.3); z-index: 1000;
            }
            .nav-links.active { display: flex; }
            .nav-links li { width: 100%; text-align: center; }
            .nav-links li a { display: block; padding: 10px; font-size: 16px; }
            
            .container { width: 95% !important; padding: 15px !important; margin: 15px auto; }
            .stat-card { width: 100%; }
            
            .form-box { flex-direction: column; align-items: stretch; padding: 10px; }
            .form-box select, .form-box input, .form-box button { 
                width: 100% !important; 
                height: 32px !important; 
                padding: 4px 8px !important; 
                font-size: 12px !important; 
            }
            
            .action-bar { flex-direction: column; align-items: stretch; gap: 10px; }
            .action-bar div { display: flex; flex-direction: row; flex-wrap: wrap; gap: 8px; width: 100%; }
            .action-bar .btn { flex: 1; min-width: 100px; text-align: center; height: 35px; padding: 6px 10px; font-size: 13px; }
            
            .search-box { max-width: 100%; width: 100%; }
            .search-box input { height: 32px; font-size: 12px; }
        }
    </style>
    <script>
        function comingSoon() {
            alert("Coming Soon! Loan service will be available soon.");
        }
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
            <li><a href="../auth/logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <div class="container">
        
        <!-- Totals Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h3>Active Members Savings</h3>
                <p><?php echo number_format($member_grand_total, 2); ?> ETB</p>
                <small>Current <?php echo $total_members; ?> Members</small>
            </div>
            
            <div class="stat-card former-card clickable" onclick="document.getElementById('formerModal').style.display='flex'">
                <h3>Former Members Fund</h3>
                <p><?php echo number_format($former_grand_total, 2); ?> ETB</p>
                <small>Click to view / add dropped members</small>
            </div>

            <div class="stat-card grand-card">
                <h3>Total Combined Savings</h3>
                <p><?php echo number_format($combined_savings_total, 2); ?> ETB</p>
                <small>Active + Former Members</small>
            </div>

            <div class="stat-card icare-card clickable" onclick="document.getElementById('icareModal').style.display='flex'">
                <h3>I Care for the Nation Fund</h3>
                <p><?php echo number_format($icare_grand_total, 2); ?> ETB</p>
                <small>Click to view / add fund</small>
            </div>
        </div>

        <!-- Member Saving Form -->
        <form method="POST" class="form-box">
            <h4 style="width:100%; margin:0 0 5px 0; color:#0b1c3c; font-size: 14px;">Record Member Saving</h4>
            <select name="members_id" required>
                <option value="">-- Select Member --</option>
                <?php
                $drop_res = $conn->query("SELECT members_id, full_name FROM members");
                while ($m = $drop_res->fetch_assoc()) {
                    echo "<option value='{$m['members_id']}'>{$m['full_name']}</option>";
                }
                ?>
            </select>
            <input type="number" step="0.01" name="amount" placeholder="Amount (ETB)" required>
            <input type="text" name="round_month" placeholder="Round/Month (e.g. Nov)" required>
            <input type="date" name="saving_date" value="<?php echo date('Y-m-d'); ?>" required>
            <button type="submit" name="save_money" class="btn">Save</button>
        </form>

        <!-- Action Bar -->
        <div class="action-bar">
            <div>
                <?php if ($total_members < 16): ?>
                    <a href="create.php" class="btn">+ Add Member (<?php echo $total_members; ?>/16)</a>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>Limit Reached (16/16)</button>
                <?php endif; ?>
                
                <a href="weekly.php" class="btn" style="background-color: #8e44ad;">Weekly Report</a>
                
                <button onclick="comingSoon()" class="btn" style="background-color: #d35400;">Loan Service</button>
            </div>
            
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search ID or Name..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn" style="background-color: #2980b9;">Search</button>
            </form>
        </div>

        <!-- Members Table with Responsive Scroll -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">Photo</th>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Sex</th>
                        <th>Total Savings</th>
                        <th>Loan Taken</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($members_result && $members_result->num_rows > 0) {
                        while ($row = $members_result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td class="photo-cell">
                            <?php 
                            if (!empty($row['photo']) && file_exists('photo/' . $row['photo'])): 
                            ?>
                                <img src="photo/<?php echo htmlspecialchars($row['photo']); ?>" class="member-img" loading="lazy" decoding="async" alt="Photo">
                            <?php else: ?>
                                <img src="photo/default.png" class="member-img" loading="lazy" decoding="async" alt="Default">
                            <?php endif; ?>
                        </td>
                        <td>#<?php echo $row['members_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['sex']); ?></td>
                        <td><strong><?php echo number_format($row['individual_total'], 2); ?> ETB</strong></td>
                        <td><strong style="color: #c0392b;"><?php echo number_format($row['loan_amount'], 2); ?> ETB</strong></td>
                        <td class="action-links">
                            <a href="history.php?id=<?php echo $row['members_id']; ?>" class="history-link">History</a>
                            <a href="edit.php?id=<?php echo $row['members_id']; ?>" class="edit-link">Edit</a>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center;'>No members found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Logo Modal -->
    <div id="logoModal" class="modal" onclick="closeModal()">
        <span class="close-modal">&times;</span>
        <img class="modal-content" src="../logo.jpg" alt="Adot Logo Large">
    </div>

    <!-- Former / Dropped Members Modal -->
    <div id="formerModal" class="custom-modal former-modal">
        <div class="custom-modal-content">
            <span class="close-btn" onclick="document.getElementById('formerModal').style.display='none'">&times;</span>
            <h2 style="color: #0b1c3c; margin-top: 0;">Former / Dropped Members Savings</h2>
            
            <form method="POST" style="background: #fdf2e9; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5cba7;">
                <h4 style="margin-top: 0; color: #d35400;">Add Former Member Saving</h4>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-size:13px; font-weight:bold; margin-bottom:3px;">Former Member Full Name</label>
                    <input type="text" name="member_name" placeholder="e.g. Abebe Kebede" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-size:13px; font-weight:bold; margin-bottom:3px;">Total Saved Amount (ETB)</label>
                    <input type="number" step="0.01" name="amount" placeholder="e.g. 2500" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-size:13px; font-weight:bold; margin-bottom:3px;">Date Recorded / Left</label>
                    <input type="date" name="saving_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-size:13px; font-weight:bold; margin-bottom:3px;">Reason / Remark</label>
                    <input type="text" name="remark" placeholder="e.g. Left group after 3 rounds" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <button type="submit" name="save_former" class="btn" style="background-color: #d35400; width: 100%;">Save Former Member Fund</button>
            </form>

            <h4 style="margin-bottom: 8px; color: #0b1c3c;">Former Members List</h4>
            <table style="width:100%; font-size:13px;">
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Remark</th>
                    <th>Amount</th>
                </tr>
                <?php 
                if ($former_history && $former_history->num_rows > 0) {
                    while ($fm = $former_history->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($fm['member_name']) . "</strong></td>";
                        echo "<td>{$fm['saving_date']}</td>";
                        echo "<td>" . htmlspecialchars($fm['remark'] ?? '-') . "</td>";
                        echo "<td><strong style='color:#d35400;'>" . number_format($fm['amount'], 2) . " ETB</strong></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center;'>No former member records found</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <!-- I Care for the Nation Modal -->
    <div id="icareModal" class="custom-modal icare-modal">
        <div class="custom-modal-content">
            <span class="close-btn" onclick="document.getElementById('icareModal').style.display='none'">&times;</span>
            <h2 style="color: #0b1c3c; margin-top: 0;">I Care for the Nation Fund</h2>
            
            <form method="POST" style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #8e44ad;">Add Fund</h4>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-size:13px; font-weight:bold; margin-bottom:3px;">Amount (ETB)</label>
                    <input type="number" step="0.01" name="amount" placeholder="e.g. 5000" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-size:13px; font-weight:bold; margin-bottom:3px;">Date</label>
                    <input type="date" name="saving_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-size:13px; font-weight:bold; margin-bottom:3px;">Remark</label>
                    <input type="text" name="remark" placeholder="e.g. Support" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <button type="submit" name="save_icare" class="btn" style="background-color: #8e44ad; width: 100%;">Save Fund</button>
            </form>

            <h4 style="margin-bottom: 8px; color: #0b1c3c;">Transaction History</h4>
            <table style="width:100%; font-size:13px;">
                <tr>
                    <th>Date</th>
                    <th>Remark</th>
                    <th>Amount</th>
                </tr>
                <?php 
                if ($icare_history && $icare_history->num_rows > 0) {
                    while ($ic = $icare_history->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$ic['saving_date']}</td>";
                        echo "<td>" . htmlspecialchars($ic['remark'] ?? '-') . "</td>";
                        echo "<td><strong>" . number_format($ic['amount'], 2) . " ETB</strong></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center;'>No records found</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>

</body>
</html>