<?php

require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: login.php");
    exit();
}

$page_title = 'Admin Dashboard';
include 'header.php';

$total = 0;
$pending = 0;
$approved = 0;
$ready = 0;
$received = 0;
$total_residents = 0;
$total_revenue = 0.00;

$q = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests");
if ($q) $total = (int) ($q->fetch_assoc()['cnt'] ?? 0);

$q = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE status='Pending'");
if ($q) $pending = (int) ($q->fetch_assoc()['cnt'] ?? 0);

$q = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE status='Approved'");
if ($q) $approved = (int) ($q->fetch_assoc()['cnt'] ?? 0);

$q = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE status='Ready'");
if ($q) $ready = (int) ($q->fetch_assoc()['cnt'] ?? 0);

$q = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE status='Received'");
if ($q) $received = (int) ($q->fetch_assoc()['cnt'] ?? 0);

$q = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='resident'");
if ($q) $total_residents = (int) ($q->fetch_assoc()['cnt'] ?? 0);

$q = $conn->query("SELECT SUM(fee) AS total FROM permit_requests WHERE status='Received'");
if ($q) $total_revenue = (float) ($q->fetch_assoc()['total'] ?? 0.00);


$recent_sql = "
    SELECT pr.id, pr.fee, pr.status, pr.request_date,
           pt.permit_name, pt.sub_type,
           u.firstname, u.lastname
    FROM permit_requests pr
    JOIN permit_types pt ON pr.permit_type_id = pt.id
    JOIN users u ON pr.user_id = u.id
    ORDER BY pr.request_date DESC
    LIMIT 10
";
$recent_result = $conn->query($recent_sql);
?>

<div class="container">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo e($_SESSION['firstname'] ?? 'Admin'); ?> </p>

    <div class="stats">
        <div class="stat-box">
            <h3><?php echo (int)$total; ?></h3>
            <p>Total Requests</p>
        </div>
        <div class="stat-box pending">
            <h3><?php echo (int)$pending; ?></h3>
            <p>Pending</p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h3><?php echo (int)$approved; ?></h3>
            <p>Approved</p>
        </div>
        <div class="stat-box ready">
            <h3><?php echo (int)$ready; ?></h3>
            <p>Ready</p>
        </div>
        <div class="stat-box received">
            <h3><?php echo (int)$received; ?></h3>
            <p>Received</p>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <h3><?php echo (int)$total_residents; ?></h3>
            <p>Registered Residents</p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);">
            <h3>₱<?php echo number_format($total_revenue, 2); ?></h3>
            <p>Total Revenue (Received)</p>
        </div>
    </div>

    <div class="actions">
        <a href="admin_manage.php" class="btn btn-success">Manage All Requests</a>
        <a href="admin_users.php" class="btn">View All Residents</a>
    </div>

    <div class="card">
        <h2>Recent Permit Requests</h2>
        <?php if ($recent_result && $recent_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Resident</th>
                        <th>Permit Type</th>
                        <th>Fee</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recent_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo e($row['id']); ?></strong></td>
                            <td><?php echo e($row['firstname'] . ' ' . $row['lastname']); ?></td>
                            <td>
                                <?php echo e($row['permit_name']); ?>
                                <?php if (!empty($row['sub_type'])): ?>
                                    <br><small><?php echo e($row['sub_type']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><strong>₱<?php echo number_format($row['fee'], 2); ?></strong></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                    <?php echo e($row['status']); ?>
                                </span>
                            </td>
                            <td><?php echo e(date('M d, Y g:i A', strtotime($row['request_date']))); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No permit requests yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
