<?php
require_once 'config.php';


if (!isLoggedIn() || !isResident()) {
    header("Location: login.php");
    exit();
}

$page_title = 'Resident Dashboard';
include 'header.php';

$user_id = $_SESSION['user_id'];


$user_q = $conn->query("SELECT firstname, lastname FROM users WHERE id = $user_id");
$user = $user_q ? $user_q->fetch_assoc() : ['firstname' => 'Resident', 'lastname' => ''];


$requests_sql = "
    SELECT pr.id, pr.fee, pr.status, pr.request_date,
           pt.permit_name, pt.sub_type
    FROM permit_requests pr
    JOIN permit_types pt ON pr.permit_type_id = pt.id
    WHERE pr.user_id = $user_id
    ORDER BY pr.request_date DESC";
$requests_result = $conn->query($requests_sql);


$total_requests = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE user_id = $user_id")->fetch_assoc()['cnt'] ?? 0;
$pending = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE user_id = $user_id AND status='Pending'")->fetch_assoc()['cnt'] ?? 0;
$approved = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE user_id = $user_id AND status='Approved'")->fetch_assoc()['cnt'] ?? 0;
$ready = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE user_id = $user_id AND status='Ready'")->fetch_assoc()['cnt'] ?? 0;
$received = $conn->query("SELECT COUNT(*) AS cnt FROM permit_requests WHERE user_id = $user_id AND status='Received'")->fetch_assoc()['cnt'] ?? 0;
?>

<div class="container">
    <h1>Welcome, <?php echo e($user['firstname']); ?> 👋</h1>
    <p>Here’s your permit overview.</p>


    <div class="stats">
        <div class="stat-box">
            <h3><?php echo $total_requests; ?></h3>
            <p>Total Requests</p>
        </div>
        <div class="stat-box pending">
            <h3><?php echo $pending; ?></h3>
            <p>Pending</p>
        </div>
        <div class="stat-box approved">
            <h3><?php echo $approved; ?></h3>
            <p>Approved</p>
        </div>
        <div class="stat-box ready">
            <h3><?php echo $ready; ?></h3>
            <p>Ready</p>
        </div>
        <div class="stat-box received">
            <h3><?php echo $received; ?></h3>
            <p>Received</p>
        </div>
    </div>

    <div class="actions">
        <a href="logout.php" class="btn">Logout</a>
    </div>

    <div class="card">
        <h2>Your Recent Requests</h2>
        <?php if ($requests_result && $requests_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Permit Type</th>
                        <th>Fee</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Track</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $requests_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo e($row['id']); ?></strong></td>
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
                            <td>
                                <button class="btn btn-track"
                                    onclick="openTrackingModal('<?php echo e($row['permit_name']); ?>','<?php echo e($row['status']); ?>')">
                                    Track
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You haven’t made any permit requests yet.</p>
        <?php endif; ?>
    </div>
</div>


<div id="trackingModal" class="tracking-modal">
    <div class="tracking-content">
        <h3 id="trackTitle">Permit Tracking</h3>
        <p id="trackStatusText"></p>

        <div class="progress-container">
            <div class="progress-step" id="stepPending">Pending</div>
            <div class="progress-step" id="stepApproved">Approved</div>
            <div class="progress-step" id="stepReady">Ready</div>
            <div class="progress-step" id="stepReceived">Received</div>
        </div>

        <button class="btn btn-secondary" onclick="closeTrackingModal()">Close</button>
    </div>
</div>

<
<style>
.stats {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.stat-box {
    flex: 1;
    min-width: 150px;
    background: #f5f5f5;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
}
.stat-box.pending { background: #f9ca24; color: #fff; }
.stat-box.approved { background: #3498db; color: #fff; }
.stat-box.ready { background: #9b59b6; color: #fff; }
.stat-box.received { background: #2ecc71; color: #fff; }

.btn-track {
    background: #185a9d;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
}
.btn-track:hover { background: #43cea2; }

.tracking-modal {
    display: none;
    position: fixed;
    z-index: 999;
    inset: 0;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
}
.tracking-content {
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    width: 420px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.25);
}
.progress-container {
    display: flex;
    justify-content: space-between;
    margin: 30px 0;
    gap: 8px;
}
.progress-step {
    flex: 1;
    padding: 10px;
    border-radius: 10px;
    background: #e0e0e0;
    font-weight: bold;
    font-size: 13px;
    color: #555;
    transition: all 0.3s ease;
}
.progress-step.active {
    transform: scale(1.05);
}
#stepPending.active,
#stepPending.completed { background: #f9ca24; color: #fff; }
#stepApproved.active,
#stepApproved.completed { background: #3498db; color: #fff; }
#stepReady.active,
#stepReady.completed { background: #9b59b6; color: #fff; }
#stepReceived.active,
#stepReceived.completed { background: #2ecc71; color: #fff; }
</style>


<script>
function openTrackingModal(permitName, status) {
    const modal = document.getElementById("trackingModal");
    modal.style.display = "flex";

    document.getElementById("trackTitle").textContent = permitName + " - Tracking Status";
    document.getElementById("trackStatusText").textContent = "Current Status: " + status;

    const steps = ["Pending", "Approved", "Ready", "Received"];
    steps.forEach(step => {
        const el = document.getElementById("step" + step);
        el.classList.remove("active", "completed");
    });

    for (const step of steps) {
        const el = document.getElementById("step" + step);
        if (step === status) {
            el.classList.add("active");
            break;
        } else {
            el.classList.add("completed");
        }
    }
}
function closeTrackingModal() {
    document.getElementById("trackingModal").style.display = "none";
}
</script>

<?php include 'footer.php'; ?>
