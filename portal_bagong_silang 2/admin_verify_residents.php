<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_manage_requests.php");
    exit();
}

$id = intval($_GET['id']);


$stmt = $conn->prepare("
    SELECT id, firstname, middlename, lastname, age, gender, birthdate,
           address, contact_number, email, occupation, civil_status,
           nationality, barangay_id_photo, verified, created_at
    FROM users
    WHERE id = ? AND role = 'resident'
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$resident = $result->fetch_assoc();

if (!$resident) {
    echo "<p>Resident not found.</p>";
    exit();
}


$photo = 'default-avatar.png';
if (!empty($resident['barangay_id_photo'])) {
    $filename = basename($resident['barangay_id_photo']);
    if (file_exists("uploads/" . $filename)) {
        $photo = "uploads/" . $filename;
    } elseif (file_exists($resident['barangay_id_photo'])) {
        $photo = $resident['barangay_id_photo'];
    }
}


$requests = $conn->prepare("
    SELECT pr.id AS request_id, pr.purpose, pr.status, pr.request_date, pr.fee,
    pt.permit_name, pt.sub_type
    FROM permit_requests pr
    JOIN permit_types pt ON pr.permit_type_id = pt.id
    WHERE pr.user_id = ?
    ORDER BY pr.request_date DESC
");
$requests->bind_param("i", $id);
$requests->execute();
$request_result = $requests->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resident Profile | Barangay Bagong Silang</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: "Segoe UI", Arial, sans-serif;
        background: #f5f7fa;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 1000px;
        margin: 40px auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 12px rgba(0,0,0,0.1);
        padding: 30px;
    }
    .back-btn {
        display: inline-block;
        margin-bottom: 20px;
        padding: 10px 18px;
        background: #185a9d;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: 500;
    }
    .back-btn:hover { background: #43cea2; }
    .profile-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 20px;
    }
    .profile-header img {
        width: 160px;
        height: 160px;
        border-radius: 10px;
        object-fit: cover;
        border: 3px solid #185a9d;
    }
    .profile-header h1 {
        margin: 0;
        color: #185a9d;
    }
    .section {
        margin-top: 25px;
    }
    .section h2 {
        background: #185a9d;
        color: white;
        padding: 10px 14px;
        border-radius: 5px;
        font-size: 18px;
        margin-bottom: 10px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px 25px;
        padding: 15px;
    }
    .info-item label {
        font-weight: bold;
        color: #185a9d;
        display: block;
        margin-bottom: 3px;
    }
    .verified {
        color: #28a745;
        font-weight: bold;
    }
    .unverified {
        color: #dc3545;
        font-weight: bold;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        text-align: center;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 10px;
        font-size: 15px;
    }
    th {
        background: #185a9d;
        color: white;
        font-weight: 600;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    @media (max-width: 600px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
        }
        .profile-header img {
            width: 130px;
            height: 130px;
        }
    }
</style>
</head>
<body>

<div class="container">
    <a href="admin_manage_requests.php" class="back-btn">⬅ Back to Manage Requests</a>

    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Resident ID">
        <div>
            <h1><?php echo htmlspecialchars($resident['firstname'].' '.$resident['middlename'].' '.$resident['lastname']); ?></h1>
            <p>Email: <?php echo htmlspecialchars($resident['email']); ?></p>
            <p>Status:
                <span class="<?php echo $resident['verified'] ? 'verified' : 'unverified'; ?>">
                    <?php echo $resident['verified'] ? 'Verified Resident' : 'Unverified'; ?>
                </span>
            </p>
        </div>
    </div>

    <div class="section">
        <h2>Personal Information</h2>
        <div class="info-grid">
            <div><label>Age:</label> <?php echo htmlspecialchars($resident['age']); ?></div>
            <div><label>Gender:</label> <?php echo htmlspecialchars($resident['gender']); ?></div>
            <div><label>Birthdate:</label> <?php echo htmlspecialchars($resident['birthdate']); ?></div>
            <div><label>Civil Status:</label> <?php echo htmlspecialchars($resident['civil_status']); ?></div>
            <div><label>Nationality:</label> <?php echo htmlspecialchars($resident['nationality']); ?></div>
            <div><label>Occupation:</label> <?php echo htmlspecialchars($resident['occupation']); ?></div>
        </div>
    </div>

    <div class="section">
        <h2>Contact Information</h2>
        <div class="info-grid">
            <div><label>Address:</label> <?php echo htmlspecialchars($resident['address']); ?></div>
            <div><label>Contact Number:</label> <?php echo htmlspecialchars($resident['contact_number']); ?></div>
            <div><label>Email:</label> <?php echo htmlspecialchars($resident['email']); ?></div>
            <div><label>Date Registered:</label> <?php echo date('M d, Y', strtotime($resident['created_at'])); ?></div>
        </div>
    </div>

    <div class="section">
        <h2>Permit Requests</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Permit Type</th>
                    <th>Purpose</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th>Date Requested</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($request_result && $request_result->num_rows > 0): ?>
                    <?php while ($req = $request_result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $req['request_id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($req['permit_name']); ?>
                                <?php if (!empty($req['sub_type'])): ?>
                                    <br><small>(<?php echo htmlspecialchars($req['sub_type']); ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($req['purpose']); ?></td>
                            <td>₱<?php echo number_format($req['fee'], 2); ?></td>
                            <td><?php echo htmlspecialchars($req['status']); ?></td>
                            <td><?php echo date('M d, Y g:i A', strtotime($req['request_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No requests found for this resident.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
