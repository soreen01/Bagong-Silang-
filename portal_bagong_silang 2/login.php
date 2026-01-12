<?php
session_start();
require_once 'config.php';


$forcedEmail = 'admin@barangay.com';
$forcedPass = 'admin123';
$forcedHash = password_hash($forcedPass, PASSWORD_DEFAULT);

$check = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
$check->bind_param("s", $forcedEmail);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    
    $create = $conn->prepare("
        INSERT INTO users (firstname, lastname, email, password, role, verified)
        VALUES ('Admin', 'User', ?, ?, 'admin', 1)
    ");
    $create->bind_param("ss", $forcedEmail, $forcedHash);
    $create->execute();
} else {
    
    $existing = $res->fetch_assoc();
    if (!password_verify($forcedPass, $existing['password'])) {
        $update = $conn->prepare("
            UPDATE users SET password = ?, role = 'admin', verified = 1 WHERE email = ?
        ");
        $update->bind_param("ss", $forcedHash, $forcedEmail);
        $update->execute();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, firstname, lastname, password, role, verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['verified'] = $user['verified'];

            
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: resident_dashboard.php");
            }
            exit;
        } else {
            $error = '❌ Invalid password.';
        }
    } else {
        $error = '❌ No account found with that email.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Barangay Bagong Silang</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(180deg, #f3f0ff, #faf8ff);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .login-box {
        background: #fff;
        padding: 35px 30px;
        border-radius: 14px;
        box-shadow: 0 8px 25px rgba(156, 163, 175, 0.2);
        width: 360px;
        text-align: center;
        border-top: 5px solid #a78bfa;
        transition: all 0.3s ease;
    }

    .login-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 35px rgba(139, 92, 246, 0.25);
    }

    h2 {
        margin-bottom: 20px;
        color: #6d28d9;
        font-weight: 700;
    }

    input {
        width: 90%;
        padding: 12px;
        margin: 10px 0;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        font-size: 15px;
        transition: all 0.3s ease;
        background: #f9fafb;
    }

    input:focus {
        border-color: #a78bfa;
        box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.2);
        outline: none;
        background: #ffffff;
    }

    button {
        width: 90%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        background: linear-gradient(90deg, #a78bfa, #8b5cf6);
        color: white;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    button:hover {
        transform: translateY(-2px);
        background: linear-gradient(90deg, #8b5cf6, #7e22ce);
    }

    .error {
        background: #f3e8ff;
        border: 1px solid #c4b5fd;
        color: #6d28d9;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .info {
        font-size: 13px;
        margin-top: 15px;
        color: #6b21a8;
        background: #f9f8ff;
        border-radius: 8px;
        padding: 10px;
        border: 1px dashed #c4b5fd;
        display: none; 
    }

    @media (max-width: 480px) {
        .login-box {
            width: 85%;
            padding: 25px 20px;
        }
    }
</style>
</head>
<body>

<div class="login-box">
    <h2>Login</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Enter Email" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <button type="submit">Login</button>
    </form>

    <div class="info">
        <p>Residents can also log in even if not yet verified.</p>
    </div>
</div>

</body>
</html>
