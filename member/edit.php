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

$id = $_GET['id'] ?? 0;
$message = "";

$stmt = $conn->prepare("SELECT * FROM members WHERE members_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Member not found!'); window.location.href='member.php';</script>";
    exit();
}
$member = $result->fetch_assoc();

if (isset($_POST['update_member'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $sex = $_POST['sex'];
    
    $photo_name = $member['photo'];

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
        
        move_uploaded_file($_FILES['photo']['tmp_name'], 'photo/' . $photo_name);
        
        if (!empty($member['photo']) && file_exists('photo/' . $member['photo'])) {
            @unlink('photo/' . $member['photo']);
        }
    }

    $update_stmt = $conn->prepare("UPDATE members SET full_name = ?, phone = ?, sex = ?, photo = ? WHERE members_id = ?");
    $update_stmt->bind_param("ssssi", $full_name, $phone, $sex, $photo_name, $id);
    
    if ($update_stmt->execute()) {
        header("Location: member.php");
        exit();
    } else {
        $message = "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Member - Adot Saving</title>
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
        .current-img { display: block; margin-bottom: 10px; width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc; }
        button { width: 100%; padding: 12px; background: #e67e22; color: white; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 15px; }
        button:hover { background: #d35400; }
        .back { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #2980b9; font-weight: bold; }
    </style>
</head>
<body>
<div class="box">
    <h2>Edit Member Information</h2>
    
    <?php if($message != ""): ?>
        <p style="color:red; text-align:center;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
        </div>

        <div class="group">
            <label>Phone Number</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
        </div>

        <div class="group">
            <label>Sex</label>
            <select name="sex" required>
                <option value="M" <?php if($member['sex'] == 'M') echo 'selected'; ?>>Male (M)</option>
                <option value="F" <?php if($member['sex'] == 'F') echo 'selected'; ?>>Female (F)</option>
            </select>
        </div>

        <div class="group">
            <label>Current Profile Photo</label>
            <?php if (!empty($member['photo']) && file_exists('photo/' . $member['photo'])): ?>
                <img src="photo/<?php echo htmlspecialchars($member['photo']); ?>" class="current-img" alt="Photo">
            <?php else: ?>
                <img src="photo/default.png" class="current-img" alt="Default">
            <?php endif; ?>
            
            <label style="margin-top: 10px;">Upload New Photo (Optional)</label>
            <input type="file" name="photo" accept="image/*">
        </div>

        <button type="submit" name="update_member">Update Member</button>
    </form>

    <a href="member.php" class="back">← Back to Members</a>
</div>
</body>
</html>