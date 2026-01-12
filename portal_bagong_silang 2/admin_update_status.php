<?php
require_once 'config.php';
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['id'], $_POST['status'])) {
    header("Location: admin_manage_requests.php");
    exit;
}

$request_id = intval($_POST['id']);
$new_status = $_POST['status'];
$allowed = ['Pending', 'Approved', 'Ready', 'Received', 'Rejected'];
if (!in_array($new_status, $allowed)) {
    header("Location: admin_manage_requests.php");
    exit;
}


$stmt = $conn->prepare("SELECT user_id, permit_type FROM permits WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    header("Location: admin_manage_requests.php");
    exit;
}
$row = $res->fetch_assoc();
$user_id = $row['user_id'];
$permit_type = $row['permit_type'];
$stmt->close();

$u = $conn->prepare("UPDATE permits SET status = ?, updated_at = NOW() WHERE id = ?");
$u->bind_param("si", $new_status, $request_id);
$u->execute();
$u->close();

$msg = "Your permit request for '{$permit_type}' is now '{$new_status}'.";
$link = "resident_track.php";
$notif = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
$notif->bind_param("iss", $user_id, $msg, $link);
$notif->execute();
$notif->close();

header("Location: admin_manage_requests.php?success=1");
exit;
?>
