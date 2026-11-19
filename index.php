<?php
session_start();
// ተጠቃሚው ሎጊን ካደረገ በቀጥታ ወደ home.php ይወስደዋል፣ ካልተደረገ ወደ ሎጊን ፎልደር ይመራዋል
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
} else {
    header("Location: auth/login.php");
    exit();
}
?>