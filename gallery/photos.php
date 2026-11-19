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

$message = "";
if (isset($_POST['upload_photos'])) {
    $category = $_POST['category'];
    $upload_date = date('Y-m-d'); // ፎቶዎቹ የተጫኑበት ቀን
    
    // የተመረጡት ፎቶዎች ብዛት መኖሩን ማረጋገጥ
    if (isset($_FILES['photo_files']) && !empty($_FILES['photo_files']['name'][0])) {
        $total_files = count($_FILES['photo_files']['name']);
        $success_count = 0;

        // እያንዳንዱን ፎቶ በሎፕ (Loop) ማቀናበር
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['photo_files']['error'][$i] == 0) {
                $ext = pathinfo($_FILES['photo_files']['name'][$i], PATHINFO_EXTENSION);
                $photo_name = time() . '_' . rand(1000, 9999) . '_' . $i . '.' . $ext;
                
                // ፎቶዎች ከየትኛውም ቦታ ተመርጠው በ gallery/uploads/ ፎልደር ውስጥ ይቀመጣሉ
                if (move_uploaded_file($_FILES['photo_files']['tmp_name'][$i], 'uploads/' . $photo_name)) {
                    $stmt = $conn->prepare("INSERT INTO gallery (photo_name, category, upload_date) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $photo_name, $category, $upload_date);
                    if ($stmt->execute()) {
                        $success_count++;
                    }
                }
            }
        }

        if ($success_count > 0) {
            header("Location: gallery.php");
            exit();
        } else {
            $message = "Failed to upload photos. Please try again.";
        }
    } else {
        $message = "Please select at least one photo from your computer.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Multiple Photos - Adot Saving</title>
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
        .box { max-width: 480px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #0b1c3c; margin-bottom: 20px; }
        .group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 15px; }
        button:hover { background: #219150; }
        .back { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #2980b9; font-weight: bold; }
        .error { color: red; text-align: center; margin-bottom: 10px; }
        .note { font-size: 12px; color: #666; margin-top: 3px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Upload Multiple Gallery Photos</h2>
    
    <?php if($message != ""): ?>
        <p class="error"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="group">
            <label>Select Photos (You can select multiple photos from any folder)</label>
            <!-- multiple አትሪቢዩት በመጨመሩ ከየትኛውም ቦታ ብዙ ፎቶ በአንድ ጊዜ መምረጥ ይቻላል -->
            <input type="file" name="photo_files[]" accept="image/*" multiple required>
            <div class="note">Hold down Ctrl (or Cmd on Mac) to select multiple photos.</div>
        </div>

        <div class="group">
            <label>Photo Category / Description</label>
            <select name="category" required>
                <option value="">-- Select Category --</option>
                <option value="Monthly Meeting Session">Monthly Meeting Session</option>
                <option value="Saving Event Gathering">Saving Event Gathering</option>
                <option value="Adot Community Members">Adot Community Members</option>
            </select>
        </div>

        <button type="submit" name="upload_photos">Upload All Photos</button>
    </form>

    <a href="gallery.php" class="back">← Back to Gallery</a>
</div>
</body>
</html>