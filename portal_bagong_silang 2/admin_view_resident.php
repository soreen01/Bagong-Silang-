<?php
session_start();
require_once 'config.php';

// ✅ Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ✅ Handle status updates
if (isset($_GET['update_status'], $_GET['id'])) {
    $user_id = intval($_GET['id']);
    $new_status = $_GET['update_status'];

    $allowed = ['Verified', 'Rejected', 'Pending'];
    if (in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE users SET account_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_verify_residents.php");
    exit();
}

// ✅ Fetch all residents
$sql = "SELECT id, firstname, lastname, email, barangay_id_photo, account_status 
        FROM users WHERE role = 'resident' ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Residents | Barangay Bagong Silang</title>
<style>
    body {
        font-family: "Segoe UI", Arial, sans-serif;
        background: #f5f3fa;
        margin: 0;
        padding: 0;
    }
    .container {
        width: 95%;
        max-width: 1100px;
        margin: 40px auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 25px;
    }
    h1 {
        text-align: center;
        color: #5a3ea6;
        margin-bottom: 25px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
        font-size: 15px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 10px;
    }
    th {
        background-color: #5a3ea6;
        color: white;
    }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .photo {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
        border: 2px solid #5a3ea6;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    .photo:hover { transform: scale(1.05); }
    .btn {
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 5px;
        font-size: 13px;
        color: white;
        margin: 2px;
        display: inline-block;
    }
    .btn-verify { background: #4CAF50; }
    .btn-reject { background: #e53935; }
    .status-badge {
        padding: 5px 8px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
    }
    .status-Pending { background: #6c757d; }
    .status-Verified { background: #4CAF50; }
    .status-Rejected { background: #e53935; }

    /* ✅ Modal popup for full photo view */
    .modal {
        display: none;
        position: fixed;
        z-index: 999;
        padding-top: 60px;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.85);
    }
    .modal-content {
        margin: auto;
        display: block;
        max-width: 80%;
        border-radius: 12px;
        box-shadow: 0 0 20px rgba(255,255,255,0.2);
    }
    .close {
        position: absolute;
        top: 25px;
        right: 60px;
        color: white;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
    }
    .close:hover { color: #bbb; }

    @media (max-width: 768px) {
        th, td { font-size: 14px; padding: 8px; }
        .photo { width: 50px; height: 50px; }
    }
</style>
</head>
<body>

<div class="container">
    <h1>👥 Verify Resident Accounts</h1>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Resident</th>
                <th>Email</th>
                <th>Barangay ID</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    
                    $photo = 'default-avatar.png';
                    if (!empty($row['barangay_id_photo'])) {
                        $filename = basename($row['barangay_id_photo']);
                        if (file_exists("uploads/" . $filename)) {
                            $photo = "uploads/" . $filename;
                        } elseif (file_exists($row['barangay_id_photo'])) {
                            $photo = $row['barangay_id_photo'];
                        }
                    }
                ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['firstname'].' '.$row['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <?php if (!empty($row['barangay_id_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($photo); ?>" 
                                 class="photo" 
                                 alt="ID"
                                 onclick="showModal('<?php echo htmlspecialchars($photo); ?>')">
                        <?php else: ?>
                            <small>No ID Uploaded</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $row['account_status']; ?>">
                            <?php echo htmlspecialchars($row['account_status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="?update_status=Verified&id=<?php echo $row['id']; ?>" 
                           class="btn btn-verify" 
                           onclick="return confirm('Are you sure you want to APPROVE this resident?')">
                           Approve
                        </a>
                        <a href="?update_status=Rejected&id=<?php echo $row['id']; ?>" 
                           class="btn btn-reject" 
                           onclick="return confirm('Are you sure you want to REJECT this resident?')">
                           Reject
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No residents found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>


<div id="photoModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
function showModal(src) {
    const modal = document.getElementById("photoModal");
    const modalImg = document.getElementById("modalImage");
    modal.style.display = "block";
    modalImg.src = src;
}
function closeModal() {
    document.getElementById("photoModal").style.display = "none";
}
</script>

</body>
</html>
