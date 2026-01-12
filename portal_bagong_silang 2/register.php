<?php
require_once 'config.php';
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname   = trim($_POST['firstname']);
    $middlename  = trim($_POST['middlename']);
    $lastname    = trim($_POST['lastname']);
    $age         = (int)$_POST['age'];
    $gender      = trim($_POST['gender']);
    $birthdate   = trim($_POST['birthdate']);
    $address     = trim($_POST['address']);
    $contact     = trim($_POST['contact_number']);
    $email       = trim($_POST['email']);
    $password    = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $occupation  = trim($_POST['occupation']);
    $civil       = trim($_POST['civil_status']);
    $nationality = trim($_POST['nationality']);
    $role        = trim($_POST['role']);
    $account_status = 'Pending';
    $verified = 0;
    $barangay_id_photo = "uploads/residents/default-avatar.png"; // default photo

    // ✅ Ensure upload directory exists
    $target_dir = "uploads/residents/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // ✅ Handle Barangay ID upload
    if (!empty($_FILES['barangay_id']['name'])) {
        $file_name = time() . "_" . basename($_FILES["barangay_id"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["barangay_id"]["tmp_name"], $target_file)) {
                $barangay_id_photo = $target_file;
            } else {
                $message = '<div class="alert alert-danger">❌ Failed to upload Barangay ID. Please try again.</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">⚠️ Invalid file type. Please upload JPG or PNG only.</div>';
        }
    }

    // ✅ Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $message = '<div class="alert alert-danger">❌ Email already registered!</div>';
    } else {
        // ✅ Insert into database
        $stmt = $conn->prepare("INSERT INTO users 
            (firstname, middlename, lastname, age, gender, birthdate, address, contact_number, email, password, occupation, civil_status, nationality, role, account_status, verified, barangay_id_photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssisssssssssssis",
            $firstname, $middlename, $lastname, $age, $gender, $birthdate, $address, $contact,
            $email, $password, $occupation, $civil, $nationality, $role,
            $account_status, $verified, $barangay_id_photo
        );

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">✅ Registration successful! Please wait for admin verification before you can log in.</div>';
        } else {
            $message = '<div class="alert alert-danger">❌ Error: ' . htmlspecialchars($conn->error) . '</div>';
        }
    }
}
?>

<?php include 'header.php'; ?>

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f4f0ff, #ede9fe);
    margin: 0;
    padding: 0;
}
.container {
    max-width: 800px;
    margin: 50px auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 6px 25px rgba(139, 92, 246, 0.2);
    padding: 35px 45px;
    border-top: 6px solid #7e22ce;
}
h1 {
    text-align: center;
    color: #6b21a8;
    margin-bottom: 25px;
    font-size: 26px;
    font-weight: 700;
}
form {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
}
.form-group {
    flex: 1 1 45%;
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #4b5563;
}
.form-group input,
.form-group select {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.form-group input:focus,
.form-group select:focus {
    border-color: #9333ea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.15);
}
button {
    width: 100%;
    background: linear-gradient(90deg, #8b5cf6, #7c3aed);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
}
button:hover {
    transform: translateY(-2px);
    background: linear-gradient(90deg, #7c3aed, #6d28d9);
}
.alert {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}
.alert-success {
    background: #ede9fe;
    color: #5b21b6;
    border: 1px solid #c4b5fd;
}
.alert-danger {
    background: #f3e8ff;
    color: #7e22ce;
    border: 1px solid #c084fc;
}
a {
    color: #6d28d9;
    text-decoration: none;
    font-weight: 600;
}
a:hover {
    text-decoration: underline;
}
@media (max-width: 600px) {
    .form-group { flex: 1 1 100%; }
    .container { margin: 20px; padding: 25px; }
}
</style>

<div class="container">
    <h1>Resident / Admin Registration</h1>
    <?php echo $message; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group"><label>First Name</label><input type="text" name="firstname" required></div>
        <div class="form-group"><label>Middle Name</label><input type="text" name="middlename"></div>
        <div class="form-group"><label>Last Name</label><input type="text" name="lastname" required></div>
        <div class="form-group"><label>Age</label><input type="number" name="age" required></div>
        <div class="form-group">
            <label>Gender</label>
            <select name="gender" required>
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
            </select>
        </div>
        <div class="form-group"><label>Birthdate</label><input type="date" name="birthdate" required></div>
        <div class="form-group"><label>Address</label><input type="text" name="address" required></div>
        <div class="form-group"><label>Contact Number</label><input type="text" name="contact_number" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Occupation</label><input type="text" name="occupation"></div>
        <div class="form-group">
            <label>Civil Status</label>
            <select name="civil_status">
                <option>Single</option>
                <option>Married</option>
                <option>Widowed</option>
            </select>
        </div>
        <div class="form-group"><label>Nationality</label><input type="text" name="nationality" value="Filipino"></div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="resident">Resident</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="form-group" style="flex: 1 1 100%;">
            <label>Upload Barangay ID (for verification)</label>
            <input type="file" name="barangay_id" accept="image/*">
        </div>
        <button type="submit">Register</button>
    </form>

    <p style="text-align:center;margin-top:15px;">
        Already have an account? <a href="login.php">Login</a>
    </p>
</div>

<?php include 'footer.php'; ?>
