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

$message = "";
if (isset($_POST['add_member'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $sex = $_POST['sex'];
    
    // የ 16 አባላት ገደብ ማረጋገጥ
    $count_result = $conn->query("SELECT COUNT(*) as total FROM members");
    $row_count = $count_result->fetch_assoc();
    
    if ($row_count['total'] >= 16) {
        $message = "Error: Maximum limit of 16 members reached!";
    } else {
        $photo_name = "";
        // ከየትኛውም ቦታ የተመረጠውን ፎቶ ወደ photo/ ፎልደር ኮፒ ማድረግ
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            if (!is_dir('photo')) {
                mkdir('photo', 0777, true);
            }
            move_uploaded_file($_FILES['photo']['tmp_name'], 'photo/' . $photo_name);
        }

        $stmt = $conn->prepare("INSERT INTO members (full_name, phone, sex, photo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $full_name, $phone, $sex, $photo_name);
        
        if ($stmt->execute()) {
            header("Location: member.php");
            exit();
        } else {
            $message = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Member - Adot Saving</title>
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
        body { font-family: Arial, sans-serif; background: #f4f7f6; margin: 40px; }
        .box { max-width: 450px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #0b1c3c; margin-bottom: 20px; }
        .group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 15px; }
        button:hover { background: #219150; }
        .back { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #2980b9; font-weight: bold; }
        .error { color: red; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Add New Member</h2>
    
    <?php if($message != ""): ?>
        <p class="error"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="group">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
        </div>

        <div class="group">
            <label>Phone Number</label>
            <input type="text" name="phone" required>
        </div>

        <div class="group">
            <label>Sex</label>
            <select name="sex" required>
                <option value="">-- Select Sex --</option>
                <option value="M">Male (M)</option>
                <option value="F">Female (F)</option>
            </select>
        </div>

        <div class="group">
            <label>Profile Photo</label>
            <input type="file" name="photo" accept="image/*" required>
        </div>

        <button type="submit" name="add_member">Save Member</button>
    </form>

    <a href="member.php" class="back">← Back to Members</a>
</div>
</body>
</html>