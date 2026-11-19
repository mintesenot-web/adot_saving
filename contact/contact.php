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

$success_msg = "";
$error_msg = "";

// ፎርሙ ተሞልቶ ሲላክ (Submit ሲደረግ)
if (isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $message = trim($_POST['message']);

    if (!empty($name) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, message, status) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $name, $email, $phone, $message);
        if ($stmt->execute()) {
            $success_msg = "Thank you! Your message has been sent successfully. We will get back to you soon.";
        } else {
            $error_msg = "Error sending message. Please try again.";
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// 1. ተጠቃሚው Contact ገጹን ሲከፍት (ሲያያቸው) ያልተነበቡትን መልዕክቶች በሙሉ ወደ 1 (Read) መቀየር
$conn->query("UPDATE contact_messages SET status = 1 WHERE status = 0");

// 2. አሁንም ያልተነበቡ (ወይም አዲስ የገቡ) መልዕክቶች ብዛት ለናቪጌሽን ባር ባጅ (Badge) ማምጣት
$msg_count_res = $conn->query("SELECT COUNT(*) AS unread_msgs FROM contact_messages WHERE status = 0");
$msg_count_row = $msg_count_res->fetch_assoc();
$unread_messages = $msg_count_row['unread_msgs'] ?? 0;

// 3. አጠቃላይ የገቢ መልዕክቶች በሙሉ ሰንጠረዡ ላይ እንዲታዩ ማምጣት
$messages_list = $conn->query("SELECT * FROM contact_messages ORDER BY message_id DESC");

// አጠቃላይ የርዕስ (Total Inbox) ብዛት ለማሳየት
$total_count_res = $conn->query("SELECT COUNT(*) AS total_msgs FROM contact_messages");
$total_count_row = $total_count_res->fetch_assoc();
$total_messages = $total_count_row['total_msgs'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Adot Saving Group</title>
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
            position: relative;
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; color: white; text-decoration: none; cursor: pointer; }
        .nav-brand img { width: 45px; height: 45px; object-fit: contain; border-radius: 50%; background: white; padding: 2px; transition: transform 0.2s; }
        .nav-brand img:hover { transform: scale(1.08); }
        .nav-brand span { font-size: 20px; font-weight: bold; color: #f39c12; }
        
        .nav-links { list-style: none; display: flex; gap: 15px; margin: 0; padding: 0; align-items: center; }
        .nav-links li a { color: white; text-decoration: none; font-weight: 600; font-size: 15px; padding: 8px 15px; border-radius: 4px; transition: all 0.3s ease; position: relative; }
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

        .msg-badge {
            background-color: #e74c3c;
            color: white;
            padding: 2px 7px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 50%;
            margin-left: 5px;
            display: inline-block;
        }

        .container { 
            flex: 1; 
            max-width: 950px; 
            margin: 40px auto; 
            background: linear-gradient(rgba(255, 255, 255, 0.93), rgba(255, 255, 255, 0.93)), url('../bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.4); 
            width: 90%; 
            box-sizing: border-box;
        }
        
        .contact-header { text-align: center; margin-bottom: 30px; }
        .contact-header h1 { color: #0b1c3c; font-size: 32px; margin-bottom: 10px; }
        .contact-header p { color: #555; font-size: 16px; }

        .contact-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .contact-info {
            background: rgba(11, 28, 60, 0.05);
            padding: 25px;
            border-radius: 8px;
            border-left: 5px solid #0b1c3c;
        }
        .contact-info h3 { color: #0b1c3c; margin-top: 0; font-size: 20px; }
        .info-item { margin-bottom: 15px; font-size: 15px; color: #333; }
        .info-item strong { color: #0b1c3c; display: block; margin-bottom: 3px; }
        .info-item a { color: #2980b9; text-decoration: none; font-weight: bold; }
        .info-item a:hover { text-decoration: underline; }

        .contact-form-box {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-top: 5px solid #f39c12;
        }
        .contact-form-box h3 { color: #0b1c3c; margin-top: 0; font-size: 20px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; color: #333; }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .form-group textarea { resize: vertical; height: 100px; }
        
        .btn-submit {
            background-color: #27ae60;
            color: white;
            padding: 12px;
            border: none;
            width: 100%;
            font-weight: bold;
            font-size: 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-submit:hover { background-color: #219150; }

        .alert-success { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-weight: bold; }

        .messages-section {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-top: 5px solid #0b1c3c;
            margin-top: 30px;
        }
        .messages-section h3 { color: #0b1c3c; margin-top: 0; margin-bottom: 15px; font-size: 20px; }
        
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 10px 12px; border: 1px solid #ddd; text-align: left; font-size: 14px; vertical-align: top; }
        th { background-color: #0b1c3c; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        /* Footer */
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
            .contact-header h1 { font-size: 24px; }
            .contact-wrapper { grid-template-columns: 1fr; }
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
            <li><a href="../member/member.php">Member</a></li>
            <li><a href="../gallery/gallery.php">Gallery</a></li>
            <li>
                <a href="contact.php" class="active">
                    📬 Contact 
                    <?php if ($unread_messages > 0): ?>
                        <span class="msg-badge"><?php echo $unread_messages; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="../about/about.php">About</a></li>
            <li><a href="../auth/logout.php" class="logout-btn">Logout (admin)</a></li>
        </ul>
    </nav>

    <!-- Container -->
    <div class="container">
        <div class="contact-header">
            <h1>Get in Touch with Us</h1>
            <p>Have questions about Adot Saving Group? Reach out to us using the info below or send a message.</p>
        </div>

        <?php if (!empty($success_msg)) echo "<div class='alert-success'>$success_msg</div>"; ?>
        <?php if (!empty($error_msg)) echo "<div class='alert-error'>$error_msg</div>"; ?>

        <div class="contact-wrapper">
            <!-- Contact Info -->
            <div class="contact-info">
                <h3>Contact Information</h3>
                
                <div class="info-item">
                    <strong>📍 Location / Address:</strong>
                    <a href="https://maps.app.goo.gl/LGxS7XKQwLPP1f4K9" target="_blank">Click here to open Map Location</a>
                </div>

                <div class="info-item">
                    <strong>📞 Phone Numbers:</strong>
                    +251 911 893 353 <br>
                    +251 961 284 452 <br>
                    +251 947 827 811
                </div>

                <div class="info-item">
                    <strong>📧 Email Address:</strong>
                    adotsaving@gmail.com
                </div>

                <div class="info-item">
                    <strong>⏰ Working Hours:</strong>
                    Monday - Saturday (8:00 AM - 5:00 PM)
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-box">
                <h3>Send Us a Message</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Your Full Name</label>
                        <input type="text" name="name" required placeholder="e.g. Abebe Kebede">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="e.g. abebe@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" placeholder="e.g. +251...">
                    </div>
                    <div class="form-group">
                        <label>Your Message</label>
                        <textarea name="message" required placeholder="Type your message here..."></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn-submit">Send Message</button>
                </form>
            </div>
        </div>

        <!-- Inbox Messages -->
        <div class="messages-section">
            <h3>📥 Inbox Messages (<?php echo $total_messages; ?>)</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Sender Name</th>
                            <th>Email / Phone</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($messages_list && $messages_list->num_rows > 0) {
                            while ($msg = $messages_list->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($msg['created_at']) . "</td>";
                                echo "<td><strong>" . htmlspecialchars($msg['name']) . "</strong></td>";
                                echo "<td>" . htmlspecialchars($msg['email']) . "<br><small>" . htmlspecialchars($msg['phone']) . "</small></td>";
                                echo "<td>" . nl2br(htmlspecialchars($msg['message'])) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; color:#777;'>No messages received yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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