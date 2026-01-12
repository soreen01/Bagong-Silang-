<?php
require_once 'config.php';

// ✅ Only allow logged-in residents
if (!isLoggedIn() || !isResident()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- Handle Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $contact = trim($_POST['contact_number']);
    $occupation = trim($_POST['occupation']);
    $civil_status = $_POST['civil_status'];
    $nationality = trim($_POST['nationality']);

    $update = $conn->prepare("
        UPDATE users 
        SET firstname=?, middlename=?, lastname=?, email=?, address=?, contact_number=?, 
            occupation=?, civil_status=?, nationality=? 
        WHERE id=?
    ");
    $update->bind_param("sssssssssi", 
        $firstname, $middlename, $lastname, $email, $address, $contact,
        $occupation, $civil_status, $nationality, $user_id
    );

    if ($update->execute()) {
        $success = "✅ Profile updated successfully!";
    } else {
        $error = "❌ Failed to update profile. Please try again.";
    }
}

// --- Get Resident Info ---
$user_q = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_q->bind_param("i", $user_id);
$user_q->execute();
$user = $user_q->get_result()->fetch_assoc();

// --- Get Permit History ---
$permits_q = $conn->prepare("
    SELECT pr.id, pt.permit_name, pt.sub_type, pr.purpose, pr.fee, pr.status, pr.request_date
    FROM permit_requests pr
    JOIN permit_types pt ON pr.permit_type_id = pt.id
    WHERE pr.user_id = ?
    ORDER BY pr.request_date DESC
");
$permits_q->bind_param("i", $user_id);
$permits_q->execute();
$permits = $permits_q->get_result();

// --- Total Fee ---
$total_q = $conn->prepare("SELECT SUM(fee) AS total FROM permit_requests WHERE user_id = ?");
$total_q->bind_param("i", $user_id);
$total_q->execute();
$total = $total_q->get_result()->fetch_assoc()['total'] ?? 0;

$page_title = 'Resident Profile & History';
include 'header.php';
?>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f7fb;
}
.container {
    max-width: 1000px;
    margin: 40px auto;
    background: #fff;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
h2 {
    color: #185a9d;
    margin-bottom: 20px;
}
form label {
    font-weight: bold;
}
form input, select {
    width: 100%;
    padding: 8px;
    margin: 6px 0 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
}
button {
    background: #185a9d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
button:hover { background: #43cea2; }
.message {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 6px;
}
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    text-align: left;
    padding: 10px;
    border-bottom: 1px solid #eee;
}
th {
    background-color: #185a9d;
    color: white;
}
.status {
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 6px;
}
.status.Pending { background: #fbc531; color: #fff; }
.status.Approved { background: #4cd137; color: #fff; }
.status.Ready { background: #0097e6; color: #fff; }
.status.Received { background: #00a8ff; color: #fff; }
.total {
    text-align: right;
    margin-top: 15px;
    font-size: 1.2em;
    font-weight: bold;
}
.btn-back {
    display: inline-block;
    background: #185a9d;
    color: #fff;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 6px;
    margin-top: 20px;
}
.btn-back:hover {
    background: #43cea2;
}
</style>

<div class="container">
    <h2>👤 My Profile & Permit History</h2>

    <?php if ($success): ?><div class="message success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>

    <form method="POST">
        <label>First Name</label>
        <input type="text" name="firstname" value="<?= htmlspecialchars($user['firstname']); ?>" required>

        <label>Middle Name</label>
        <input type="text" name="middlename" value="<?= htmlspecialchars($user['middlename']); ?>">

        <label>Last Name</label>
        <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>

        <label>Address</label>
        <input type="text" name="address" value="<?= htmlspecialchars($user['address']); ?>">

        <label>Contact Number</label>
        <input type="text" name="contact_number" value="<?= htmlspecialchars($user['contact_number']); ?>">

        <label>Occupation</label>
        <input type="text" name="occupation" value="<?= htmlspecialchars($user['occupation']); ?>">

        <label>Civil Status</label>
        <select name="civil_status">
            <?php
            $statuses = ['Single', 'Married', 'Widowed'];
            foreach ($statuses as $status) {
                $sel = ($status == $user['civil_status']) ? 'selected' : '';
                echo "<option value='$status' $sel>$status</option>";
            }
            ?>
        </select>

        <label>Nationality</label>
        <input type="text" name="nationality" value="<?= htmlspecialchars($user['nationality']); ?>">

        <button type="submit" name="update_profile">💾 Save Changes</button>
    </form>

    <hr>
    <h3>📜 Permit Request History</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Permit Type</th>
                <th>Purpose</th>
                <th>Fee (₱)</th>
                <th>Status</th>
                <th>Date Requested</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($permits->num_rows > 0): ?>
                <?php $i = 1; while ($row = $permits->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($row['permit_name']); ?>
                            <?php if (!empty($row['sub_type'])): ?>
                                <br><small><?= htmlspecialchars($row['sub_type']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['purpose']); ?></td>
                        <td>₱<?= number_format($row['fee'], 2); ?></td>
                        <td><span class="status <?= $row['status']; ?>"><?= htmlspecialchars($row['status']); ?></span></td>
                        <td><?= date("M d, Y g:i A", strtotime($row['request_date'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">No permit requests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total">💰 Total Amount Paid: ₱<?= number_format($total, 2); ?></div>

    <a href="resident_dashboard.php" class="btn-back">← Back to Dashboard</a>
</div>

<?php include 'footer.php'; ?>
