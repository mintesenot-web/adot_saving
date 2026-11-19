<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guest') {
    header("Location: ../auth/login.php");
    exit();
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// ከዳታቤዝ ጋር ማገናኘት
include '../db.php';

$msg = "";
$msg_class = "";

if (isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $message = trim($_POST['message']);
    
    // መልዕክቱን በትክክለኛው contact_messages ቴብል ውስጥ ማስገባት
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $name, $email, $phone, $message);
        if ($stmt->execute()) {
            $msg = "Message sent successfully!";
            $msg_class = "success";
        } else {
            $msg = "Error sending message. Please try again.";
            $msg_class = "error";
        }
        $stmt->close();
    } else {
        // phone አምድ ከሌለ ያለ ስልክ ቁጥር ብቻ እንዲመዘገብ የሚያደርግ አማራጭ
        $stmt2 = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        if ($stmt2) {
            $stmt2->bind_param("sss", $name, $email, $message);
            if ($stmt2->execute()) {
                $msg = "Message sent successfully!";
                $msg_class = "success";
            } else {
                $msg = "Error sending message.";
                $msg_class = "error";
            }
            $stmt2->close();
        } else {
            $msg = "Database error: 'contact_messages' table issue.";
            $msg_class = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us - Adot Saving (Guest)</title>
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

        .container { flex: 1; max-width: 950px; margin: 40px auto; background: rgba(255, 255, 255, 0.96); padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 90%; }
        
        .contact-header { text-align: center; margin-bottom: 30px; }
        .contact-header h2 { color: #0b1c3c; font-size: 28px; margin: 0 0 8px 0; }
        .contact-header p { color: #666; font-size: 14px; margin: 0; }

        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 35px; }
        @media (max-width: 768px) { .contact-grid { grid-template-columns: 1fr; } }

        .info-box { background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #0b1c3c; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .info-box h3 { color: #0b1c3c; margin-top: 0; font-size: 18px; border-bottom: 2px solid #ddd; padding-bottom: 8px; }
        .info-item { margin-bottom: 15px; font-size: 14px; color: #444; }
        .info-item strong { display: block; color: #0b1c3c; margin-bottom: 3px; }
        .map-link { color: #2980b9; text-decoration: none; font-weight: 600; font-size: 13px; }
        .map-link:hover { text-decoration: underline; }

        .form-box { background: #fff; padding: 25px; border-radius: 8px; border-left: 4px solid #f39c12; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .form-box h3 { color: #0b1c3c; margin-top: 0; font-size: 18px; border-bottom: 2px solid #ddd; padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #0b1c3c; font-size: 13px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus { border-color: #0b1c3c; outline: none; }
        
        .btn-submit { background-color: #27ae60; color: white; padding: 12px; width: 100%; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px; transition: background 0.3s; }
        .btn-submit:hover { background-color: #219653; }

        .alert-msg { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; text-align: center; font-weight: bold; }
        .alert-msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        footer { background-color: #f39c12; color: #0b1c3c; text-align: center; padding: 15px 0; margin-top: auto; font-size: 15px; font-weight: bold; border-top: 2px solid #0b1c3c; }
    </style>
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
            <li><a href="contact.php" class="active">Contact</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="../auth/logout.php" class="logout-btn">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="contact-header">
            <h2>Get in Touch with Us</h2>
            <p>Have questions about Adot Saving Group? Reach out to us using the info below or send a message.</p>
        </div>

        <?php if(!empty($msg)): ?>
            <div class="alert-msg <?php echo $msg_class; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="contact-grid">
            <!-- Contact Info -->
            <div class="info-box">
                <h3>Contact Information</h3>
                <div class="info-item">
                    <strong>📍 Location / Address:</strong>
                    <span>Addis Ababa, Ethiopia</span><br>
                    <a href="https://maps.google.com" target="_blank" class="map-link">Click here to open Map Location</a>
                </div>
                <div class="info-item">
                    <strong>📞 Phone Numbers:</strong>
                    <span>+251 911 093 353</span><br>
                    <span>+251 961 284 452</span><br>
                    <span>+251 947 827 811</span>
                </div>
                <div class="info-item">
                    <strong>📧 Email Address:</strong>
                    <span>adotsaving@gmail.com</span>
                </div>
                <div class="info-item">
                    <strong>🕒 Working Hours:</strong>
                    <span>Monday - Saturday (8:00 AM - 5:00 PM)</span>
                </div>
            </div>

            <!-- Message Form -->
            <div class="form-box">
                <h3>Send Us a Message</h3>
                <form method="POST" autocomplete="off">
                    <div class="form-group">
                        <label>Your Full Name</label>
                        <input type="text" name="name" placeholder="e.g. Abebe Kebede" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="e.g. abebe@example.com" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" placeholder="e.g. +251..." autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Your Message</label>
                        <textarea name="message" rows="4" placeholder="Type your message here..." required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn-submit">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Adot Saving Group. All rights reserved.</p>
    </footer>

</body>
</html>