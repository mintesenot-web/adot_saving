<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../db.php';

$error = "";

if (isset($_POST['login'])) {
    $role = $_POST['role'];
    $guest_type = isset($_POST['guest_type']) ? $_POST['guest_type'] : 'no_member';

    if ($role == 'owner') {
        // 1. የአስተዳዳሪ (Owner) መግቢያ
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $hashed_password = md5($password);

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND role = 'owner'");
        $stmt->bind_param("ss", $username, $hashed_password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: ../home.php");
            exit();
        } else {
            $error = "Invalid Owner Username or Password!";
        }
    } else {
        // 2. የ Guest / Visitor መግቢያ
        if ($guest_type == 'member') {
            // አባል (Member) ከሆነ፡ የራሱን ዩዘርናም፣ ፓስወርድ እና ID ማስገባት አለበት
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $member_id_input = trim($_POST['member_id']);
            $hashed_password = md5($password);

            if (empty($member_id_input)) {
                $error = "Please enter your Member ID!";
            } else {
                $stmt = $conn->prepare("SELECT u.*, m.full_name FROM users u JOIN members m ON u.member_id = m.members_id WHERE u.username = ? AND u.password = ? AND u.role = 'guest' AND u.member_id = ?");
                $stmt->bind_param("ssi", $username, $hashed_password, $member_id_input);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['member_id'] = $user['member_id'];
                    $_SESSION['member_name'] = $user['full_name'];
                    header("Location: ../guest/home.php");
                    exit();
                } else {
                    $error = "Invalid Member Username, Password, or ID!";
                }
            }
        } else {
            // 3. ቪዚተር / ጌስት (No Member / Visitor) ከሆነ፡
            // ምንም ሳያወጣና ሳያገባ በራስ-ሰር በጋራው ቪዚተር አካውንት (visitor) ያስገባዋል!
            $visitor_username = 'visitor';
            $visitor_password = md5('AdotVisitor@2026'); 

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND role = 'guest' AND (member_id IS NULL OR member_id = 0)");
            $stmt->bind_param("ss", $visitor_username, $visitor_password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['member_id'] = null;
                $_SESSION['member_name'] = "Visitor";
                header("Location: ../guest/home.php");
                exit();
            } else {
                $error = "Default visitor account not found in database! Please run the setup SQL.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Adot Saving Group</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(11, 28, 60, 0.85), rgba(16, 42, 69, 0.85)), url('../logo.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }
        .login-card h2 {
            margin-top: 0;
            color: #0b1c3c;
            text-align: center;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #0b1c3c;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .btn-login {
            background-color: #f39c12;
            color: #0b1c3c;
            border: none;
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover {
            background-color: #d68910;
        }
        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h2>Adot Saving Login</h2>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Role:</label>
                <select name="role" id="roleSelect" onchange="toggleFormFields()" required>
                    <option value="guest">Guest (Member / Visitor)</option>
                    <option value="owner">Owner (Admin)</option>
                </select>
            </div>

            <div class="form-group" id="guestTypeGroup">
                <label>Guest Type:</label>
                <select name="guest_type" id="guestTypeSelect" onchange="toggleFormFields()" required>
                    <option value="no_member">No Member (Visitor)</option>
                    <option value="member">Member</option>
                </select>
            </div>

            <div class="form-group hidden" id="memberIdGroup">
                <label>Member ID:</label>
                <input type="number" name="member_id" id="memberIdInput" placeholder="Enter your ID (1-16)">
            </div>

            <div class="form-group" id="usernameGroup">
                <label>Username:</label>
                <input type="text" name="username" id="usernameInput">
            </div>

            <div class="form-group" id="passwordGroup">
                <label>Password:</label>
                <input type="password" name="password" id="passwordInput">
            </div>

            <button type="submit" name="login" class="btn-login">Login</button>
        </form>
    </div>

    <script>
        function toggleFormFields() {
            const role = document.getElementById('roleSelect').value;
            const guestTypeGroup = document.getElementById('guestTypeGroup');
            const guestType = document.getElementById('guestTypeSelect').value;
            const memberIdGroup = document.getElementById('memberIdGroup');
            const usernameGroup = document.getElementById('usernameGroup');
            const passwordGroup = document.getElementById('passwordGroup');
            
            const usernameInput = document.getElementById('usernameInput');
            const passwordInput = document.getElementById('passwordInput');

            if (role === 'owner') {
                guestTypeGroup.style.display = 'none';
                memberIdGroup.style.display = 'none';
                usernameGroup.style.display = 'block';
                passwordGroup.style.display = 'block';
                usernameInput.required = true;
                passwordInput.required = true;
            } else {
                guestTypeGroup.style.display = 'block';
                
                if (guestType === 'member') {
                    memberIdGroup.style.display = 'block';
                    usernameGroup.style.display = 'block';
                    passwordGroup.style.display = 'block';
                    usernameInput.required = true;
                    passwordInput.required = true;
                } else {
                    // No Member (Visitor) ከሆነ Username እና Password ሳጥኖቹን ሙሉ በሙሉ ይደብቃቸዋል
                    memberIdGroup.style.display = 'none';
                    usernameGroup.style.display = 'none';
                    passwordGroup.style.display = 'none';
                    usernameInput.required = false;
                    passwordInput.required = false;
                }
            }
        }

        // ገጹ ሲከፈት የመጀመሪያውን ቴስታ (State) ለማስተካከል
        window.onload = toggleFormFields;
        document.getElementById('guestTypeSelect').addEventListener('change', toggleFormFields);
        document.getElementById('roleSelect').addEventListener('change', toggleFormFields);
    </script>

</body>
</html>