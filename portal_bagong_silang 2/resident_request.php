<?php
session_start();
require_once 'config.php';

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $permit_ids = $_POST['permit_type_id'] ?? [];
    $purpose = trim($_POST['purpose']);

    if (empty($permit_ids)) {
        $message = "<div class='error'>⚠️ Please select at least one permit.</div>";
    } else {
        $success = 0;
        foreach ($permit_ids as $pid) {
            $stmt = $conn->prepare("SELECT fixed_fee FROM permit_types WHERE id=?");
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $stmt->bind_result($fee);
            $stmt->fetch();
            $stmt->close();

            $insert = $conn->prepare("
                INSERT INTO permit_requests (user_id, permit_type_id, purpose, fee)
                VALUES (?, ?, ?, ?)
            ");
            $insert->bind_param("iisd", $user_id, $pid, $purpose, $fee);
            if ($insert->execute()) $success++;
            $insert->close();
        }

        if ($success > 0)
            $message = "<div class='success'>✅ Successfully submitted $success request(s)! Waiting for admin approval.</div>";
        else
            $message = "<div class='error'>❌ No requests submitted. Please try again.</div>";
    }
}

// ✅ Fetch available permits
$permit_types = $conn->query("SELECT id, permit_name, sub_type, fixed_fee FROM permit_types ORDER BY permit_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Request Permit</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
  body {
    font-family: 'Poppins', sans-serif;
    background: #f5f7fa;
    margin: 0;
    padding: 0;
  }

  .container {
    max-width: 900px;
    background: white;
    margin: 50px auto;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    padding: 30px;
  }

  h2 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
  }

  .permit-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
  }

  .permit-item {
    border: 2px solid #ddd;
    border-radius: 12px;
    padding: 15px;
    background: #fafafa;
    transition: 0.3s;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .permit-item:hover {
    background: #eef5ff;
    border-color: #007bff;
  }

  input[type="checkbox"] {
    transform: scale(1.4);
    accent-color: #007bff;
  }

  textarea {
    width: 100%;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 10px;
    resize: vertical;
    font-size: 15px;
  }

  button {
    width: 100%;
    padding: 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
  }

  button:hover {
    background: #0056b3;
  }

  .success, .error {
    text-align: center;
    font-weight: 600;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
  }

  .success { background: #d4edda; color: #155724; }
  .error { background: #f8d7da; color: #721c24; }

  /* ✅ Back button */
  .back-btn {
    display: inline-block;
    padding: 10px 20px;
    margin-bottom: 20px;
    background: #6c757d;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: 0.3s;
  }

  .back-btn:hover {
    background: #5a6268;
  }
</style>
</head>
<body>

<div class="container">
  <a href="resident_dashboard.php" class="back-btn">← Back</a>

  <h2>Request a Permit</h2>
  <?= $message ?>

  <form method="POST">
    <div class="permit-list">
      <?php while ($row = $permit_types->fetch_assoc()): ?>
        <label class="permit-item">
          <input type="checkbox" name="permit_type_id[]" value="<?= $row['id'] ?>">
          <div>
            <strong><?= htmlspecialchars($row['permit_name']) ?></strong><br>
            <small><?= htmlspecialchars($row['sub_type'] ?? '') ?> — ₱<?= number_format($row['fixed_fee'], 2) ?></small>
          </div>
        </label>
      <?php endwhile; ?>
    </div>

    <div class="form-group">
      <label><b>Purpose</b></label>
      <textarea name="purpose" placeholder="Write your purpose..." required></textarea>
    </div>

    <button type="submit">Submit Request</button>
  </form>
</div>

</body>
</html>
