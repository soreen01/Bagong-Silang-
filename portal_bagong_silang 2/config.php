<?php


$host = 'localhost';
$user = 'root';
$pass = ''; 
$db   = 'bagong_silang';


$conn = new mysqli($host, $user, $pass, $db);


if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}


$conn->set_charset('utf8mb4');


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function clean($data) {
    global $conn;
    return htmlspecialchars(trim($conn->real_escape_string($data)), ENT_QUOTES, 'UTF-8');
}

function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isResident() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'resident';
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
