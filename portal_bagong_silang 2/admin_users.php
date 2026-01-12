<?php
session_start();
require_once 'config.php';

// ✅ Admin access check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM users WHERE role='resident' ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Residents | Admin</title>
<style>
body { font-family: Arial, sans-serif; background:#f5f7fa; margin:0; padding:20px; }
.container { max-width:100%; margin:0 auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.06); overflow-x:auto; }
h1 { color: #185a9d; margin-bottom:15px; }
table { width:100%; border-collapse:collapse; font-size:14px; min-width:1200px; }
th, td { border:1px solid #e6e6e6; padding:8px; text-align:left; vertical-align:middle; }
th { background:#185a9d; color:#fff; position:sticky; top:0; z-index:2; }
.photo { width:50px; height:50px; object-fit:cover; border-radius:8px; cursor:pointer; }
.btn-delete { background:#dc3545; color:#fff; padding:4px 8px; border-radius:5px; font-size:12px; border:none; cursor:pointer; }
.alert { padding:10px; border-radius:6px; margin-bottom:10px; }
.alert-success { background:#d4edda; color:#155724; }
.alert-error { background:#f8d7da; color:#721c24; }
.modal { display:none; position:fixed; z-index:999; padding-top:60px; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); }
.modal-content { margin:auto; display:block; max-width:80%; border-radius:12px; }
.close { position:absolute; top:25px; right:60px; color:white; font-size:40px; font-weight:bold; cursor:pointer; }
.close:hover { color:#bbb; }
</style>
</head>
<body>
<div class="container">
    <a href="admin_dashboard.php" style="text-decoration:none;color:#185a9d;">⬅ Back to Dashboard</a>
    <h1>👥 Manage Residents</h1>
    <div id="alertMsg"></div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Barangay ID</th>
                <th>Firstname</th>
                <th>Middlename</th>
                <th>Lastname</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Birthdate</th>
                <th>Address</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Password (hashed)</th>
                <th>Occupation</th>
                <th>Civil Status</th>
                <th>Nationality</th>
                <th>Role</th>
                <th>Account Status</th>
                <th>Verified</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if($result && $result->num_rows>0): ?>
            <?php while($row=$result->fetch_assoc()): ?>
                <?php
                    $photo = (!empty($row['barangay_id_photo']) && file_exists($row['barangay_id_photo']))
                        ? $row['barangay_id_photo']
                        : '../uploads/residents/default-avatar.png';
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><img src="<?php echo htmlspecialchars($photo); ?>" class="photo" onclick="showModal('<?php echo htmlspecialchars($photo); ?>')"></td>
                    <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                    <td><?php echo htmlspecialchars($row['middlename']); ?></td>
                    <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                    <td><?php echo htmlspecialchars($row['birthdate']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['password']); ?></td>
                    <td><?php echo htmlspecialchars($row['occupation']); ?></td>
                    <td><?php echo htmlspecialchars($row['civil_status']); ?></td>
                    <td><?php echo htmlspecialchars($row['nationality']); ?></td>
                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                    <td><?php echo htmlspecialchars($row['account_status']); ?></td>
                    <td><?php echo $row['verified'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td><button class="btn-delete" data-user-id="<?php echo $row['id']; ?>">Delete</button></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="20">No residents found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="photoModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
function showModal(src){
    document.getElementById("photoModal").style.display = "block";
    document.getElementById("modalImage").src = src;
}
function closeModal(){
    document.getElementById("photoModal").style.display = "none";
}

document.querySelectorAll('.btn-delete').forEach(btn=>{
    btn.addEventListener('click',function(){
        const userId=this.dataset.userId;
        if(!confirm('Are you sure you want to delete this account?')) return;
        fetch('delete_user.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id:userId})
        }).then(res=>res.json()).then(data=>{
            const alertDiv=document.getElementById('alertMsg');
            if(data.success){
                alertDiv.innerHTML='<div class="alert alert-success">✅ Account deleted successfully.</div>';
                setTimeout(()=>location.reload(),1000);
            } else {
                alertDiv.innerHTML='<div class="alert alert-error">❌ '+(data.message||'Failed to delete account.')+'</div>';
            }
        }).catch(err=>{
            document.getElementById('alertMsg').innerHTML='<div class="alert alert-error">❌ Network error.</div>';
        });
    });
});
</script>
</body>
</html>
