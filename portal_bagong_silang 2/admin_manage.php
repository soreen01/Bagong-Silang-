<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


$check_column = $conn->query("SHOW COLUMNS FROM permit_requests LIKE 'previous_status'");
if ($check_column->num_rows === 0) {
    $conn->query("ALTER TABLE permit_requests ADD COLUMN previous_status ENUM('Pending','Approved','Ready','Received','Rejected') DEFAULT NULL");
}


if (isset($_GET['update_status'], $_GET['id'])) {
    $request_id = intval($_GET['id']);
    $new_status = $_GET['update_status'];
    $allowed = ['Pending', 'Approved', 'Ready', 'Received', 'Rejected'];

    if (in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("SELECT status FROM permit_requests WHERE id=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->bind_result($old_status);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE permit_requests SET previous_status=?, status=? WHERE id=?");
        $stmt->bind_param("ssi", $old_status, $new_status, $request_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_manage_requests.php");
    exit();
}


if (isset($_GET['undo'], $_GET['id'])) {
    $request_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT previous_status FROM permit_requests WHERE id=?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($previous_status);
    $stmt->fetch();
    $stmt->close();

    if (!empty($previous_status)) {
        $stmt = $conn->prepare("UPDATE permit_requests SET status=?, previous_status=NULL WHERE id=?");
        $stmt->bind_param("si", $previous_status, $request_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_manage_requests.php");
    exit();
}


$sql = "
    SELECT pr.id AS request_id, pr.user_id, pr.purpose, pr.status, pr.previous_status, pr.request_date, pr.fee,
           u.firstname, u.lastname, u.barangay_id_photo, u.verified,
           pt.permit_name, pt.sub_type
    FROM permit_requests pr
    JOIN users u ON pr.user_id = u.id
    JOIN permit_types pt ON pr.permit_type_id = pt.id
    ORDER BY pr.request_date DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Requests</title>
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f7f5fc;
    margin: 0;
}
.container {
    width: 95%;
    margin: 20px auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(139, 92, 246, 0.2);
    padding: 20px;
    border-top: 5px solid #8b5cf6;
}
h1 {
    text-align: center;
    color: #6b21a8;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
th, td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}
th {
    background: #8b5cf6;
    color: white;
}
tr:nth-child(even) { background: #faf5ff; }

.status-badge {
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
}
.status-Pending { background: #6b7280; color: white; }
.status-Approved { background: #4f46e5; color: white; }
.status-Ready { background: #facc15; color: black; }
.status-Received { background: #16a34a; color: white; }
.status-Rejected { background: #dc2626; color: white; }

.verify-badge {
    font-weight: 600;
    border-radius: 8px;
    padding: 4px 8px;
    display: inline-block;
}
.verified { background: #d9f99d; color: #166534; }
.pending { background: #fef3c7; color: #92400e; }

.view-photo-btn {
    background-color: #7b4b94;
    color: white;
    border: none;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
}
.view-photo-btn:hover { background-color: #9b5dc9; }

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: center;
}
.btn {
    text-decoration: none;
    color: white;
    border-radius: 5px;
    font-size: 12px;
    padding: 6px 8px;
    flex: 1;
    text-align: center;
}
.btn-pending { background: #6b7280; }
.btn-approved { background: #4f46e5; }
.btn-ready { background: #facc15; color: black; }
.btn-received { background: #16a34a; }
.btn-rejected { background: #dc2626; }
.btn-undo { background: #9333ea; }

.photo-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.85);
    text-align: center;
}
.photo-modal img {
    max-width: 80%;
    max-height: 80%;
    border-radius: 10px;
    margin-top: 60px;
    box-shadow: 0 0 20px #000;
}
.photo-close {
    position: absolute;
    top: 25px; right: 50px;
    color: white;
    font-size: 40px;
    cursor: pointer;
}
</style>
</head>
<body>

<div class="container">
    <h1>📋 Manage Permit Requests</h1>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Resident</th>
                <th>Barangay ID</th>
                <th>Resident Status</th>
                <th>Permit Type</th>
                <th>Purpose</th>
                <th>Fee</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($r = $result->fetch_assoc()): ?>
                <?php
                $photoPath = '';
                if (!empty($r['barangay_id_photo'])) {
                    $photo = trim($r['barangay_id_photo']);
                    $photoPath = (strpos($photo, 'uploads/') === 0) ? $photo : 'uploads/' . $photo;
                    $fileFull = __DIR__ . '/' . $photoPath;
                }
                ?>
                <tr>
                    <td>#<?php echo $r['request_id']; ?></td>
                    <td><?php echo htmlspecialchars($r['firstname'] . ' ' . $r['lastname']); ?></td>
                    <td>
                        <?php if (!empty($photoPath) && file_exists($fileFull)): ?>
                            <button class="view-photo-btn" data-photo="<?php echo htmlspecialchars($photoPath); ?>">📷 View ID</button>
                        <?php else: ?>
                            <span style="color:#aaa;">No Photo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['verified']): ?>
                            <span class="verify-badge verified">Verified</span>
                        <?php else: ?>
                            <span class="verify-badge pending">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($r['permit_name']); ?><br>
                        <small><?php echo htmlspecialchars($r['sub_type']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($r['purpose']); ?></td>
                    <td>₱<?php echo number_format($r['fee'], 2); ?></td>
                    <td><span class="status-badge status-<?php echo $r['status']; ?>"><?php echo $r['status']; ?></span></td>
                    <td><?php echo date('M d, Y g:i A', strtotime($r['request_date'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="?update_status=Pending&id=<?php echo $r['request_id']; ?>" class="btn btn-pending">Pending</a>
                            <a href="?update_status=Approved&id=<?php echo $r['request_id']; ?>" class="btn btn-approved">Approve</a>
                            <a href="?update_status=Ready&id=<?php echo $r['request_id']; ?>" class="btn btn-ready">Ready</a>
                            <a href="?update_status=Received&id=<?php echo $r['request_id']; ?>" class="btn btn-received">Received</a>
                            <a href="?update_status=Rejected&id=<?php echo $r['request_id']; ?>" class="btn btn-rejected">Reject</a>
                            <?php if (!empty($r['previous_status'])): ?>
                                <a href="?undo=1&id=<?php echo $r['request_id']; ?>" class="btn btn-undo">Undo</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="10">No requests found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>


<div id="photoModal" class="photo-modal">
<span class="photo-close">&times;</span>
<img id="modalImage" src="">
</div>

<script>
document.querySelectorAll(".view-photo-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        const photoSrc = this.dataset.photo;
        const modal = document.getElementById("photoModal");
        const modalImg = document.getElementById("modalImage");
        modalImg.src = photoSrc;
        modal.style.display = "block";
    });
});

document.querySelector(".photo-close").onclick = function() {
    document.getElementById("photoModal").style.display = "none";
};

window.onclick = function(event) {
    const modal = document.getElementById("photoModal");
    if (event.target === modal) modal.style.display = "none";
};
</script>

</body>
</html>
