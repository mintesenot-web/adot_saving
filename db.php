<?php
// ሴሽን ቀድሞ ያልጀመረ ከሆነ ማስጀመር
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. የዳታቤዝ ማገናኛ ዝርዝሮች (ለ Localhost እና ለ InfinityFree የሚስተካከሉ)
$host = "localhost";
$user = "root";
$password = "";
$dbname = "abot_saving";

$conn = mysqli_connect($host, $user, $password, $dbname);

// ማገናኛውን ማረጋገጥ
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// ዩኒኮድ/አማርኛ ፊደላት በትክክል እንዲነበቡ ማድረግ
mysqli_set_charset($conn, "utf8");


// --- 2. አውቶ-ሎጋውት (AUTO LOGOUT - PHP በኩል) ---
if (isset($_SESSION['user_id']) || isset($_SESSION['username'])) {
    
    $timeout_duration = 3000; // 5 ደቂቃ

    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity'];
        
        if ($elapsed_time > $timeout_duration) {
            session_unset();     
            session_destroy();   
            
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $server_host = $_SERVER['HTTP_HOST'];
            
            $base_path = (strpos($server_host, 'localhost') !== false || $server_host == '127.0.0.1') ? '/abot_saving/' : '/';
            
            header("Location: " . $protocol . $server_host . $base_path . "auth/login.php?timeout=1");
            exit();
        }
    }
    
    $_SESSION['last_activity'] = time();
}
?>
<script>
    let idleSeconds = 0;

    // በየ 1 ሰከንዱ የሚቆጥር ቆጣሪ
    function timerIncrement() {
        idleSeconds++;
        
        // 50 ሰከንድ ሲሞላ (ማስጠንቀቂያ: ለሙከራ 50 ሰከንድ ተደርጓል)
        if (idleSeconds > 50) {
            // ሎካልሆስትም ሆነ ሰርቨር ላይ በትክክል እንዲሰራ ዲናሚክ ዩአርኤል
            let baseUrl = window.location.origin;
            if (window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1") {
                baseUrl += "/abot_saving";
            }
            window.location.href = baseUrl + "/auth/logout.php?timeout=1";
        }
    }

    // ተጠቃሚው ማუსን ወይም ኪቦርድ ሲነካ ሰዓቱ ወደ ዜሮ ይመለሳል
    window.onload = resetTimer;
    window.onmousemove = resetTimer;
    window.onkeypress = resetTimer;
    window.onclick = resetTimer;
    window.onscroll = resetTimer;

    let idleInterval = setInterval(timerIncrement, 1000); // በየ 1 ሰከንዱ

    function resetTimer() {
        idleSeconds = 0;
    }
</script>