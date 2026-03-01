<?php
// admin/manage_admins.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Security: Only super_admin can access this page
if ($_SESSION['admin_role'] !== 'super_admin') {
    setFlash('danger', 'Unauthorized access!');
    redirect('profile.php');
}

$page_title = "Manage Admins";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        $full_name = sanitize($_POST['full_name']);
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];

        $stmt = $pdo->prepare("INSERT INTO admins (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $username, $email, $password, $role])) {
            logActivity($pdo, $_SESSION['admin_id'], 'Add Admin', "Added new admin: $username with role $role");
            setFlash('success', 'New admin added successfully!');
        }
    }

    if (isset($_POST['edit_admin'])) {
        $id = $_POST['admin_id'];
        $full_name = sanitize($_POST['full_name']);
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $role = $_POST['role'];
        
        $sql = "UPDATE admins SET full_name = ?, username = ?, email = ?, role = ? WHERE id = ?";
        $params = [$full_name, $username, $email, $role, $id];

        // Update password if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE admins SET full_name = ?, username = ?, email = ?, role = ?, password = ? WHERE id = ?";
            $params = [$full_name, $username, $email, $role, $password, $id];
        }

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            logActivity($pdo, $_SESSION['admin_id'], 'Edit Admin', "Updated details for admin: $username (Role: $role)");
            setFlash('success', 'Admin details updated successfully!');
        }
    }

    if (isset($_POST['delete_admin'])) {
        $id = $_POST['admin_id'];
        if ($id != $_SESSION['admin_id']) { // Cannot delete self
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            if ($stmt->execute([$id])) {
                logActivity($pdo, $_SESSION['admin_id'], 'Delete Admin', "Deleted admin ID: $id");
                setFlash('success', 'Admin removed successfully!');
            }
        } else {
            setFlash('danger', 'You cannot delete your own account!');
        }
    }

    if (isset($_POST['delete_log'])) {
        $id = $_POST['log_id'];
        $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE id = ?");
        if ($stmt->execute([$id])) {
            setFlash('success', 'Activity log deleted successfully!');
        }
    }

    if (isset($_POST['delete_all_logs'])) {
        $stmt = $pdo->prepare("DELETE FROM activity_logs");
        if ($stmt->execute()) {
            logActivity($pdo, $_SESSION['admin_id'], 'Clear Logs', "Cleared all system activity logs");
            setFlash('success', 'All activity logs have been cleared!');
        }
    }
    redirect('manage_admins.php');
}

// Fetch All Admins
$admins = $pdo->query("SELECT * FROM admins ORDER BY role ASC, full_name ASC")->fetchAll();

// Fetch Logs with Search
$search = isset($_GET['search_logs']) ? sanitize($_GET['search_logs']) : '';
$log_query = "SELECT l.*, a.full_name as admin_name 
              FROM activity_logs l 
              JOIN admins a ON l.admin_id = a.id";

if (!empty($search)) {
    $log_query .= " WHERE a.full_name LIKE :search 
                    OR l.action LIKE :search 
                    OR l.details LIKE :search";
}

$log_query .= " ORDER BY l.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($log_query);

if (!empty($search)) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$logs = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">System Administrators</h5>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="fas fa-plus me-2"></i> Add New Admin
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $a): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $a['photo'] ? '../uploads/admins/' . $a['photo'] : '../assets/img/default-admin.png'; ?>" 
                                                 class="rounded-circle me-2" width="35" height="35" style="object-fit: cover;">
                                            <span class="fw-bold"><?php echo $a['full_name']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $a['username']; ?></td>
                                    <td><?php echo $a['email']; ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $a['role'] == 'super_admin' ? 'bg-danger' : ($a['role'] == 'manager' ? 'bg-primary' : 'bg-info'); ?>">
                                            <?php echo strtoupper($a['role']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-sm btn-outline-primary edit-admin-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editAdminModal"
                                                    data-id="<?php echo $a['id']; ?>"
                                                    data-fullname="<?php echo $a['full_name']; ?>"
                                                    data-username="<?php echo $a['username']; ?>"
                                                    data-email="<?php echo $a['email']; ?>"
                                                    data-role="<?php echo $a['role']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($a['id'] != $_SESSION['admin_id']): ?>
                                                <form action="" method="POST" onsubmit="return confirm('Are you sure?');" style="display:inline;">
                                                    <input type="hidden" name="admin_id" value="<?php echo $a['id']; ?>">
                                                    <button type="submit" name="delete_admin" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <h5 class="fw-bold mb-0">System Activity logs</h5>
                    <div class="d-flex gap-2">
                        <form action="" method="GET" class="d-flex gap-2">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search_logs" class="form-control rounded-start-pill ps-3" 
                                       placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-primary rounded-end-pill px-3" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <?php if (!empty($search)): ?>
                                <a href="manage_admins.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center">Clear</a>
                            <?php endif; ?>
                        </form>
                        <form action="" method="POST" onsubmit="return confirm('WARNING: This will permanently delete ALL activity logs. Are you sure?');">
                            <button type="submit" name="delete_all_logs" class="btn btn-sm btn-danger rounded-pill px-3">
                                <i class="fas fa-trash-alt me-1"></i> Clear All
                            </button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="sticky-top bg-white border-bottom">
                            <tr>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Time</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No activity logs found.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?php echo $log['admin_name']; ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $log['action']; ?></span></td>
                                    <td class="small text-truncate" style="max-width: 300px;"><?php echo $log['details']; ?></td>
                                    <td><small class="text-muted"><?php echo date('d M, H:i:s', strtotime($log['created_at'])); ?></small></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <button class="btn btn-xs btn-outline-info view-log-btn" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewLogModal"
                                                    data-admin="<?php echo $log['admin_name']; ?>"
                                                    data-action="<?php echo $log['action']; ?>"
                                                    data-details="<?php echo htmlspecialchars($log['details']); ?>"
                                                    data-time="<?php echo date('d M Y, H:i:s', strtotime($log['created_at'])); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <form action="" method="POST" onsubmit="return confirm('Delete this log entry?');" style="display:inline;">
                                                <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                                <button type="submit" name="delete_log" class="btn btn-xs btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Add System Administrator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role / Permission</label>
                        <select name="role" class="form-select" required>
                            <option value="viewer">Viewer (Read Only)</option>
                            <option value="editor">Editor (Record Entry)</option>
                            <option value="manager">Manager (Manage Data)</option>
                            <option value="super_admin">Super Admin (Full Access)</option>
                        </select>
                        <div class="form-text">Choose the level of access for this user.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_admin" class="btn btn-primary rounded-pill px-4">Create Admin Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Log Modal -->
<div class="modal fade" id="viewLogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Activity Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small text-muted d-block">Admin</label>
                    <span id="view_log_admin" class="fw-bold"></span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">Action</label>
                    <span id="view_log_action" class="badge bg-light text-dark border"></span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">Time</label>
                    <span id="view_log_time" class="text-muted"></span>
                </div>
                <hr class="my-3 opacity-10">
                <div class="mb-0">
                    <label class="small text-muted d-block mb-1">Details</label>
                    <div id="view_log_details" class="p-3 bg-light rounded-3 small" style="white-space: pre-wrap;"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Administrator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="admin_id" id="edit_admin_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role / Permission</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            <option value="viewer">Viewer (Read Only)</option>
                            <option value="editor">Editor (Record Entry)</option>
                            <option value="manager">Manager (Manage Data)</option>
                            <option value="super_admin">Super Admin (Full Access)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Change Password (Leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control" placeholder="New password">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_admin" class="btn btn-primary rounded-pill px-4">Update Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-admin-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_admin_id').value = this.dataset.id;
        document.getElementById('edit_full_name').value = this.dataset.fullname;
        document.getElementById('edit_username').value = this.dataset.username;
        document.getElementById('edit_email').value = this.dataset.email;
        document.getElementById('edit_role').value = this.dataset.role;
    });
});

document.querySelectorAll('.view-log-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('view_log_admin').textContent = this.dataset.admin;
        document.getElementById('view_log_action').textContent = this.dataset.action;
        document.getElementById('view_log_time').textContent = this.dataset.time;
        document.getElementById('view_log_details').textContent = this.dataset.details;
    });
});
</script>

<?php include '../includes/footer.php'; ?>
