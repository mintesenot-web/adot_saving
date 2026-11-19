<?php
ob_start();
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include '../db.php';

$selected_month = $_GET['report_month'] ?? date('Y-m');

// ዶውንሎድ (CSV Export) ሲጠየቅ የሚሰራ ሎጂክ
if (isset($_GET['download']) && $_GET['download'] == 'csv') {
    if (ob_get_length()) ob_clean();

    $filename = "Adot_Monthly_Report_" . $selected_month . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM ለ Amharic ጽሁፎች
    
    fputcsv($output, array('Member ID', 'Full Name', 'Phone', 'Total Saved Amount (ETB)', 'Expected (120 ETB)', 'Penalty (ETB)', 'Total Due / Status'));
    
    $csv_query = "
        SELECT m.members_id, m.full_name, m.phone, 
               COALESCE(SUM(s.amount), 0) AS total_saved,
               COUNT(s.saving_id) AS weeks_paid
        FROM members m 
        LEFT JOIN savings s ON m.members_id = s.member_id AND DATE_FORMAT(s.saving_date, '%Y-%m') = ?
        GROUP BY m.members_id, m.full_name, m.phone
        ORDER BY m.members_id ASC
    ";
    $stmt = $conn->prepare($csv_query);
    $stmt->bind_param("s", $selected_month);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $weeks_paid = $row['weeks_paid'];
        $penalty = ($weeks_paid <= 3) ? 10 : 0; 
        $expected = 120; 
        $total_due = ($expected - $row['total_saved']) + $penalty;
        if($total_due < 0) $total_due = 0;
        
        fputcsv($output, array(
            $row['members_id'],
            $row['full_name'],
            $row['phone'],
            $row['total_saved'],
            $expected,
            $penalty,
            $total_due
        ));
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Report & Penalty Tracker - Adot Saving Group</title>
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

        .container { flex: 1; max-width: 1100px; margin: 30px auto; background: rgba(255, 255, 255, 0.96); padding: 30px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 90%; box-sizing: border-box; }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .header-flex h2 { color: #0b1c3c; margin: 0; }
        
        .filter-form { display: flex; gap: 10px; align-items: center; background: #ecf0f1; padding: 10px 15px; border-radius: 6px; border: 1px solid #bdc3c7; }
        .filter-form input[type="month"] { padding: 8px; border: 1px solid #bdc3c7; border-radius: 4px; font-size: 14px; }
        
        .btn { background-color: #27ae60; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: bold; border: none; cursor: pointer; font-size: 14px; display: inline-block; }
        .btn:hover { background-color: #219150; }
        .btn-back { background-color: #2980b9; margin-bottom: 20px; }
        .btn-download { background-color: #e67e22; margin-left: 10px; }
        .btn-download:hover { background-color: #d35400; }

        .summary-card { background: #f8f9fa; border-left: 5px solid #27ae60; padding: 15px 20px; border-radius: 6px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .summary-card h4 { margin: 0; color: #0b1c3c; font-size: 18px; }
        .summary-card span { font-size: 20px; font-weight: bold; color: #27ae60; }

        .table-responsive { width: 100%; overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; min-width: 750px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 14px; vertical-align: middle; }
        th { background-color: #0b1c3c; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        
        td.photo-cell { padding: 5px !important; width: 50px; text-align: center; }
        .member-img { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; display: inline-block; vertical-align: middle; }

        .badge-completed { background-color: #d4edda; color: #155724; padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 13px; }
        .badge-penalty { background-color: #f8d7da; color: #721c24; padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 13px; }

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
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <a href="member.php" class="btn btn-back">← Back to Members</a>
            <a href="weekly.php?report_month=<?php echo $selected_month; ?>&download=csv" class="btn btn-download">📥 Download Monthly Report</a>
        </div>

        <div class="header-flex">
            <h2>Monthly Savings & Penalty Report</h2>
            <form method="GET" class="filter-form">
                <label style="font-weight: bold; font-size: 14px; color: #0b1c3c;">Select Month:</label>
                <input type="month" name="report_month" value="<?php echo htmlspecialchars($selected_month); ?>" required>
                <button type="submit" class="btn">Filter</button>
            </form>
        </div>

        <?php
        $sum_stmt = $conn->prepare("SELECT SUM(amount) AS month_total FROM savings WHERE DATE_FORMAT(saving_date, '%Y-%m') = ?");
        $sum_stmt->bind_param("s", $selected_month);
        $sum_stmt->execute();
        $sum_res = $sum_stmt->get_result()->fetch_assoc();
        $month_total = $sum_res['month_total'] ?? 0;
        ?>

        <div class="summary-card">
            <h4>Report Month: <span style="color: #0b1c3c;"><?php echo htmlspecialchars($selected_month); ?></span></h4>
            <div>Total Collected: <span><?php echo number_format($month_total, 2); ?> ETB</span></div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Weeks Paid</th>
                        <th>Total Saved</th>
                        <th>Penalty (10 ETB)</th>
                        <th>Status / Due Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $report_query = "
                        SELECT m.members_id, m.full_name, m.phone, m.photo, 
                               COALESCE(SUM(s.amount), 0) AS total_saved,
                               COUNT(s.saving_id) AS weeks_paid
                        FROM members m 
                        LEFT JOIN savings s ON m.members_id = s.member_id AND DATE_FORMAT(s.saving_date, '%Y-%m') = ?
                        GROUP BY m.members_id, m.full_name, m.phone, m.photo
                        ORDER BY m.members_id ASC
                    ";
                    $rep_stmt = $conn->prepare($report_query);
                    $rep_stmt->bind_param("s", $selected_month);
                    $rep_stmt->execute();
                    $rep_result = $rep_stmt->get_result();

                    if ($rep_result && $rep_result->num_rows > 0) {
                        while ($row = $rep_result->fetch_assoc()) {
                            $weeks_paid = $row['weeks_paid'];
                            $total_saved = $row['total_saved'];
                            
                            $penalty = ($weeks_paid <= 3 && $weeks_paid >= 0) ? 10 : 0;
                            $expected_amount = 120; 
                            $deficit = $expected_amount - $total_saved;
                            if($deficit < 0) $deficit = 0;
                            
                            $total_due_with_penalty = $deficit + $penalty;

                            echo "<tr>";
                            echo "<td class='photo-cell'>";
                            if (!empty($row['photo']) && file_exists('photo/' . $row['photo'])) {
                                echo "<img src='photo/" . htmlspecialchars($row['photo']) . "' class='member-img' alt='Photo'>";
                            } else {
                                echo "<img src='photo/default.png' class='member-img' alt='Default'>";
                            }
                            echo "</td>";

                            echo "<td>#" . $row['members_id'] . "</td>";
                            echo "<td><strong>" . htmlspecialchars($row['full_name']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                            echo "<td>" . $weeks_paid . " / 4 Weeks</td>";
                            echo "<td>" . number_format($total_saved, 2) . " ETB</td>";
                            echo "<td><strong style='color: #c0392b;'>" . ($penalty > 0 ? "+ " . $penalty . " ETB" : "0.00 ETB") . "</strong></td>";
                            
                            echo "<td>";
                            if ($weeks_paid >= 4 && $total_saved >= 120) {
                                echo "<span class='badge-completed'>Completed ✓</span>";
                            } else {
                                echo "<span class='badge-penalty'>Due: <strong>" . number_format($total_due_with_penalty, 2) . " ETB</strong> (incl. penalty)</span>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' style='text-align:center;'>No members found.</td></tr>";
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

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>

</body>
</html>
<?php ob_end_flush(); ?>