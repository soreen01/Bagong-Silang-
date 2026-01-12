<?php
require_once 'config.php';

if (!isLoggedIn() || !isResident()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$result = $conn->prepare("SELECT permit_type, purpose, status, amount, created_at FROM permits WHERE user_id = ? ORDER BY created_at DESC");
$result->bind_param("i", $user_id);
$result->execute();
$permits = $result->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Permit Tracking</title>
<style>
body { font-family:'Poppins',sans-serif;background:#f3f4f6;color:#1f2937;margin:0;}
.container {width:95%;max-width:1000px;margin:50px auto;background:white;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
h2{text-align:center;color:#1e3a8a;margin-bottom:25px;}
table{width:100%;border-collapse:collapse;}
th,td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:left;}
th{background:#2563eb;color:white;}
.badge{padding:5px 10px;border-radius:20px;color:white;font-weight:600;}
.Pending{background:#f59e0b;}
.Approved{background:#3b82f6;}
.Ready{background:#10b981;}
.Received{background:#22c55e;}
.Rejected{background:#ef4444;}
.amount{font-weight:600;color:#2563eb;}
.empty{text-align:center;font-style:italic;color:#6b7280;margin-top:20px;}
</style>
</head>
<body>
<div class="container">
<h2>📍 My Permit Tracking</h2>
<?php if ($permits->num_rows > 0): ?>
<table>
<tr>
<th>#</th>
<th>Permit Type</th>
<th>Purpose</th>
<th>Status</th>
<th>Amount</th>
<th>Date Requested</th>
</tr>
<?php $i=1; while($row=$permits->fetch_assoc()): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($row['permit_type']) ?></td>
<td><?= htmlspecialchars($row['purpose'] ?? 'N/A') ?></td>
<td><span class="badge <?= $row['status'] ?>"><?= $row['status'] ?></span></td>
<td class="amount">₱<?= number_format($row['amount'],2) ?></td>
<td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p class="empty">You have not submitted any permit requests yet.</p>
<?php endif; ?>
</div>
</body>
</html>
