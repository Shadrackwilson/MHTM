<?php
// admin/profile.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$admin_id = $_SESSION['admin_id'];
$page_title = "My Profile";

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $username = sanitize($_POST['username']);
        
        $sql = "UPDATE admins SET full_name = ?, email = ?, username = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$full_name, $email, $username, $admin_id])) {
            $_SESSION['admin_name'] = $full_name;
            $_SESSION['admin_username'] = $username;
            logActivity($pdo, $admin_id, 'Update Profile', 'Updated personal profile information');
            setFlash('success', 'Profile updated successfully!');
        }
    }

    if (isset($_POST['update_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $current_pass = $stmt->fetchColumn();

        if (password_verify($old_pass, $current_pass)) {
            if ($new_pass === $confirm_pass) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $admin_id]);
                logActivity($pdo, $admin_id, 'Change Password', 'Updated account password');
                setFlash('success', 'Password changed successfully!');
            } else {
                setFlash('danger', 'New passwords do not match!');
            }
        } else {
            setFlash('danger', 'Current password is incorrect!');
        }
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $target_dir = "../uploads/admins/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = "admin_" . $admin_id . "_" . time() . "." . $ext;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $stmt = $pdo->prepare("UPDATE admins SET photo = ? WHERE id = ?");
            $stmt->execute([$filename, $admin_id]);
            $_SESSION['admin_photo'] = $filename;
            logActivity($pdo, $admin_id, 'Update Photo', 'Changed profile picture');
            setFlash('success', 'Profile picture updated!');
        }
    }
    redirect('profile.php');
}

// Fetch current admin data
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 text-center p-4">
            <div class="mb-3">
                <img src="<?php echo $admin['photo'] ? '../uploads/admins/' . $admin['photo'] : '../assets/img/default-admin.png'; ?>" 
                     class="rounded-circle border shadow-sm" width="120" height="120" style="object-fit: cover;">
            </div>
            <h5 class="fw-bold mb-1"><?php echo $admin['full_name']; ?></h5>
            <p class="text-muted small mb-3"><?php echo strtoupper($admin['role']); ?></p>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <input type="file" name="photo" class="form-control form-control-sm" accept="image/*" required>
                </div>
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4">Change Photo</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Update Profile Info</h5>
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo $admin['full_name']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?php echo $admin['username']; ?>" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $admin['email']; ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Security Settings</h5>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-dark rounded-pill px-4">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
