<?php
require_once 'config.php';
$page_title = 'Home - Bagong Silang Portal';
include 'header.php';
?>

<div class="container">
    <div class="welcome">
        <h1>Welcome to Bagong Silang</h1>
        <p>Permit Request and Tracking System</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-success" style="margin-right: 10px;">Register Now</a>
            <a href="login.php" class="btn">Login</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>About the System</h2>
        <p>Our digital platform helps residents easily apply for and track barangay permits online. With this system, you can:</p>
        <ul style="margin: 20px 0 0 30px; line-height: 2;">
            <li>Register and create your account</li>
            <li>Request various barangay permits</li>
            <li>Track your permit status in real-time</li>
            <li>View your complete permit history</li>
        </ul>
    </div>

    <div class="card">
        <h2>Available Permits</h2>
        <table>
            <thead>
                <tr>
                    <th>Permit Type</th>
                    <th>Category</th>
                    <th>Fee</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT permit_name, sub_type, fixed_fee FROM permit_types");
                while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['permit_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['sub_type'] ?? '-'); ?></td>
                        <td>₱<?php echo number_format($row['fixed_fee'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
