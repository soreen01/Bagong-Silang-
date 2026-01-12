<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}


$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = intval($input['id']);


if ($user_id === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}


$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
}

$stmt->close();
$conn->close();
