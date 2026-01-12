<?php
require_once 'config.php';

$firstname = "Admin";
$lastname = "System";
$age = 30;
$gender = "Male";
$email = "admin@bagongsilang.com";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "admin";
$verified = 1;

$stmt = $conn->prepare("INSERT INTO users (firstname, lastname, age, gender, email, password, role, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssissssi", $firstname, $lastname, $age, $gender, $email, $password, $role, $verified);

if ($stmt->execute()) {
    echo "✅ Admin account created successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}
?>
