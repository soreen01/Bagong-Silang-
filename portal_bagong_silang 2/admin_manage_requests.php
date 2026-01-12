<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


$sql = "
    SELECT 
        pr.id AS request_id,
        pr.user_id,
        pr.purpose,
        pr.status,
        pr.request_date,
        pr.fee,
        u.firstname,
        u.lastname,
        u.barangay_id_photo,
        u.verified,
        pt.permit_name,
        pt.sub_type
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
<title>Manage All Requests</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    body { font-family: Arial, sans-serif; background: #f5f6fa; margin: 0; padding: 20px; }
    .container { max-width: 1200px; margin: auto; background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { text-align: center; color: #185a9d; }
    a.back { display: inline-block; margin-bottom: 10px; background: #185a9d; color: #fff; padding: 8px 12px; border-radius: 5px; text-decoration: none; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
    th { background: #185a9d; color: white; }
    .photo { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; cursor: pointer; }
    .badge { padding: 3px 6px; border-radius: 5px; font-weight: bold; color: #fff; }
    .verified { background: #28a745; }
    .not-verified { background: #f39c12; }
    .btn { padding: 5px 8px; border-radius: 5px; color: #fff; text-decoration: none; font-size: 12px; margin: 2px; display: inline-block; }
    .btn-view { background: #007bff; }
    .btn-verify { background: #28a745; }
    .btn-hold { background: #dc3545; }
    .btn-approve { background: #17a2b8; }
    .btn-deny { background: #c82333; }
    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: none; justify-content: center; align-items: center; z-index: 1000; }
    .modal-content { max-width: 80%; max-height: 80%; position: relative; }
    .modal-img { max-width: 100%; max-height: 100%; border-radius: 8px; cursor: zoom-in; transition: transform 0.3s; }
    .modal-img.zoomed { transform: scale(1.6); cursor: zoom-out; }
    .close-modal { position: absolute; top: -30px; right: 0; background: #fff; padding: 5px; border-radius: 50%; cursor: pointer; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
    <a href="admin_dashboard.php" class="back">⬅ Back to Dashboard</a>
    <h1>📋 Manage All Requests</h1>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Resident</th>
                <th>Permit</th>
                <th>Purpose</th>
                <th>Fee</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                $photo = !empty($row['barangay_id_photo']) ? $row['barangay_id_photo'] : 'default-user.png';
                if (!str_starts_with($photo, 'uploads/')) { $photo = 'uploads/barangay_ids/' . $photo; }
        ?>
            <tr>
                <td>#<?= $row['request_id']; ?></td>
                <td>
                    <img src="<?= htmlspecialchars($photo); ?>" class="photo" onclick="openModal('<?= htmlspecialchars($photo); ?>')"><br>
                    <?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?><br>
                    <?= $row['verified'] ? '<span class=\"badge verified\">Verified</span>' : '<span class=\"badge not-verified\">Not Verified</span>'; ?>
                </td>
                <td><?= htmlspecialchars($row['permit_name']); ?></td>
                <td><?= htmlspecialchars($row['purpose']); ?></td>
                <td>₱<?= number_format($row['fee'],2); ?></td>
                <td><?= htmlspecialchars($row['status']); ?></td>
                <td><?= date('M d, Y', strtotime($row['request_date'])); ?></td>
                <td>
                    
                    <a href="admin_view_resident.php?id=<?= $row['user_id']; ?>" class="btn btn-view">View Info</a>

                    
                    <?php if ($row['verified']): ?>
                        <a href="hold_user.php?id=<?= $row['user_id']; ?>" class="btn btn-hold">Unverify</a>
                    <?php else: ?>
                        <a href="verify_user.php?id=<?= $row['user_id']; ?>" class="btn btn-verify">Verify</a>
                    <?php endif; ?>

                    
                    <a href="approve_request.php?id=<?= $row['request_id']; ?>" class="btn btn-approve">Approve</a>
                    <a href="deny_request.php?id=<?= $row['request_id']; ?>" class="btn btn-deny">Deny</a>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="8">No requests found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modalOverlay">
    <div class="modal-content">
        <img id="modalImage" class="modal-img" src="">
        <div class="close-modal" onclick="closeModal()">✖</div>
    </div>
</div>

<script>
function openModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('modalOverlay').style.display = 'flex';
}
function closeModal() {
    let img = document.getElementById('modalImage');
    img.classList.remove('zoomed');
    document.getElementById('modalOverlay').style.display = 'none';
}
document.getElementById('modalImage').addEventListener('click', function(){
    this.classList.toggle('zoomed');
});
</script>
</body>
</html>
