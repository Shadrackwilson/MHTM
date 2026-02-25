<?php
// admin/tenants.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "Tenant Management";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_tenant'])) {
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $whatsapp_number = sanitize($_POST['whatsapp_number']);
        $house_id = $_POST['house_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = sanitize($_POST['status']);

        // Handle Image Upload
        $photo = "";
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'cr2'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newName = time() . '_' . uniqid() . '.' . $ext;
                $uploadPath = '../uploads/tenants/' . $newName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    $photo = $newName;
                }
            }
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO tenants (full_name, phone, email, whatsapp_number, house_id, start_date, end_date, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $phone, $email, $whatsapp_number, $house_id, $start_date, $end_date, $photo, $status]);
            
            // Auto-update house status if assigned
            if ($house_id && $status === 'active') {
                $stmt = $pdo->prepare("UPDATE houses SET status = 'occupied' WHERE id = ?");
                $stmt->execute([$house_id]);
            }
            $pdo->commit();
            setFlash('success', 'Tenant added successfully!');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error adding tenant: ' . $e->getMessage());
        }
    }

    if (isset($_POST['edit_tenant'])) {
        $id = $_POST['tenant_id'];
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $whatsapp_number = sanitize($_POST['whatsapp_number']);
        $old_house_id = $_POST['old_house_id'];
        $house_id = $_POST['house_id'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $status = sanitize($_POST['status']);

        // Handle Image Upload
        $photoUpdate = "";
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'cr2'];
            $filename = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $newName = time() . '_' . uniqid() . '.' . $ext;
                $uploadPath = '../uploads/tenants/' . $newName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                   $photoUpdate = ", photo = '$newName'";
                }
            }
        }

        try {
            $pdo->beginTransaction();
            $sql = "UPDATE tenants SET full_name = ?, phone = ?, email = ?, whatsapp_number = ?, house_id = ?, start_date = ?, end_date = ?, status = ? $photoUpdate WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$full_name, $phone, $email, $whatsapp_number, $house_id, $start_date, $end_date, $status, $id]);

            // Update house statuses
            if ($old_house_id != $house_id) {
                if ($old_house_id) {
                    $pdo->prepare("UPDATE houses SET status = 'vacant' WHERE id = ?")->execute([$old_house_id]);
                }
                if ($house_id && $status === 'active') {
                    $pdo->prepare("UPDATE houses SET status = 'occupied' WHERE id = ?")->execute([$house_id]);
                }
            } elseif ($status === 'left' && $house_id) {
                $pdo->prepare("UPDATE houses SET status = 'vacant' WHERE id = ?")->execute([$house_id]);
            } elseif ($status === 'active' && $house_id) {
                $pdo->prepare("UPDATE houses SET status = 'occupied' WHERE id = ?")->execute([$house_id]);
            }

            $pdo->commit();
            setFlash('success', 'Tenant updated successfully!');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error updating tenant: ' . $e->getMessage());
        }
    }

    if (isset($_POST['delete_tenant'])) {
        $id = $_POST['tenant_id'];
        $house_id = $_POST['house_id'];
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM tenants WHERE id = ?");
            $stmt->execute([$id]);
            if ($house_id) {
                $pdo->prepare("UPDATE houses SET status = 'vacant' WHERE id = ?")->execute([$house_id]);
            }
            $pdo->commit();
            setFlash('success', 'Tenant deleted successfully!');
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlash('danger', 'Error deleting tenant: ' . $e->getMessage());
        }
    }

    redirect('tenants.php');
}

// Filter Data
$search_name = sanitize($_GET['search_name'] ?? '');
$filter_house = sanitize($_GET['filter_house'] ?? '');
$filter_status = sanitize($_GET['filter_status'] ?? '');

$query = "SELECT t.*, h.house_number FROM tenants t LEFT JOIN houses h ON t.house_id = h.id WHERE 1=1";
$params = [];

if ($search_name) {
    $query .= " AND t.full_name LIKE ?";
    $params[] = "%$search_name%";
}
if ($filter_house) {
    $query .= " AND t.house_id = ?";
    $params[] = $filter_house;
}
if ($filter_status) {
    $query .= " AND t.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY t.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tenants = $stmt->fetchAll();

// Fetch available houses for assignment
$vacantHouses = $pdo->query("SELECT id, house_number FROM houses WHERE status = 'vacant' ORDER BY house_number ASC")->fetchAll();
// All houses for edit (in case we need to reassign to current house)
$allHouses = $pdo->query("SELECT id, house_number FROM houses ORDER BY house_number ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Tenants</h2>
    <div class="d-flex gap-2">
         <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addTenantModal">
            <i class="fas fa-user-plus me-2"></i> Add New Tenant
        </button>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-body">
        <form action="" method="GET" class="row g-3">
             <div class="col-md-4">
                <input type="text" name="search_name" class="form-control" placeholder="Search tenant name..." value="<?php echo $search_name; ?>">
             </div>
             <div class="col-md-3">
                 <select name="filter_house" class="form-select">
                     <option value="">All Houses</option>
                     <?php foreach ($allHouses as $h): ?>
                         <option value="<?php echo $h['id']; ?>" <?php echo $filter_house == $h['id'] ? 'selected' : ''; ?>>
                            <?php echo $h['house_number']; ?>
                         </option>
                     <?php endforeach; ?>
                 </select>
             </div>
             <div class="col-md-3">
                 <select name="filter_status" class="form-select">
                     <option value="">All Status</option>
                     <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                     <option value="left" <?php echo $filter_status == 'left' ? 'selected' : ''; ?>>Left</option>
                 </select>
             </div>
             <div class="col-md-2 d-grid">
                 <button type="submit" class="btn btn-outline-primary rounded-pill">Filter</button>
             </div>
        </form>
    </div>
</div>

<div class="table-responsive table-glass">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Profile</th>
                <th>Full Name</th>
                <th>House</th>
                <th>End Date</th>
                <th>Phone</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tenants)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No tenants found. Add one to get started!</td>
                </tr>
            <?php else: ?>
                <?php foreach ($tenants as $tenant): ?>
                    <tr>
                        <td>
                            <img src="<?php echo $tenant['photo'] ? '../uploads/tenants/' . $tenant['photo'] : '../assets/img/default-profile.png'; ?>" 
                                 class="rounded-circle border" 
                                 width="40" height="40" 
                                 style="object-fit: cover;">
                        </td>
                        <td class="fw-bold"><?php echo $tenant['full_name']; ?></td>
                        <td><?php echo $tenant['house_number'] ?: '<span class="text-danger">None</span>'; ?></td>
                        <td>
                            <span class="<?php echo (strtotime($tenant['end_date']) < time() && $tenant['status'] == 'active') ? 'text-danger fw-bold' : ''; ?>">
                                <?php echo $tenant['end_date'] ? date('d M Y', strtotime($tenant['end_date'])) : '-'; ?>
                            </span>
                        </td>
                        <td><?php echo $tenant['phone']; ?></td>
                        <td>
                            <span class="badge rounded-pill <?php echo $tenant['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo ucfirst($tenant['status']); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="tenant_profile.php?id=<?php echo $tenant['id']; ?>" class="btn btn-sm btn-outline-info" title="View Profile">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="../reports/contract.php?id=<?php echo $tenant['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Download Contract" target="_blank">
                                    <i class="fas fa-file-contract"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-primary edit-tenant-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editTenantModal"
                                    data-id="<?php echo $tenant['id']; ?>"
                                    data-name="<?php echo $tenant['full_name']; ?>"
                                    data-phone="<?php echo $tenant['phone']; ?>"
                                    data-email="<?php echo $tenant['email']; ?>"
                                    data-whatsapp="<?php echo $tenant['whatsapp_number']; ?>"
                                    data-house_id="<?php echo $tenant['house_id']; ?>"
                                    data-start_date="<?php echo $tenant['start_date']; ?>"
                                    data-end_date="<?php echo $tenant['end_date']; ?>"
                                    data-status="<?php echo $tenant['status']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-tenant-btn" 
                                    data-id="<?php echo $tenant['id']; ?>"
                                    data-house_id="<?php echo $tenant['house_id']; ?>"
                                    data-name="<?php echo $tenant['full_name']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Tenant Modal -->
<div class="modal fade" id="addTenantModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Add New Tenant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body pt-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Assign House</label>
                            <select name="house_id" class="form-select">
                                <option value="">Select a vacant house</option>
                                <?php foreach ($vacantHouses as $house): ?>
                                    <option value="<?php echo $house['id']; ?>"><?php echo $house['house_number']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="add_start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required onchange="calculateEndDate('add_start_date', 'add_end_date')">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date (Next Payment)</label>
                            <input type="date" name="end_date" id="add_end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewImage(this, 'add-preview')">
                            <img id="add-preview" class="profile-img-preview mt-2" style="display:none;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="left">Left</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_tenant" class="btn btn-primary px-4">Save Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tenant Modal -->
<div class="modal fade" id="editTenantModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Tenant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tenant_id" id="edit_tenant_id">
                <input type="hidden" name="old_house_id" id="edit_old_house_id">
                <div class="modal-body pt-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" id="edit_whatsapp_number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">House</label>
                            <select name="house_id" id="edit_house_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($allHouses as $house): ?>
                                    <option value="<?php echo $house['id']; ?>"><?php echo $house['house_number']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required onchange="calculateEndDate('edit_start_date', 'edit_end_date')">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Profile Photo (Leave blank to keep current)</label>
                            <input type="file" name="photo" class="form-control" accept="image/*" onchange="previewImage(this, 'edit-preview')">
                            <img id="edit-preview" class="profile-img-preview mt-2" style="display:none;">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="left">Left</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_tenant" class="btn btn-primary px-4">Update Tenant</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteTenantForm" action="" method="POST" style="display: none;">
    <input type="hidden" name="tenant_id" id="delete_tenant_id">
    <input type="hidden" name="house_id" id="delete_tenant_house_id">
    <input type="hidden" name="delete_tenant" value="1">
</form>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-tenant-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_tenant_id').value = this.dataset.id;
            document.getElementById('edit_old_house_id').value = this.dataset.house_id;
            document.getElementById('edit_full_name').value = this.dataset.name;
            document.getElementById('edit_phone').value = this.dataset.phone;
            document.getElementById('edit_email').value = this.dataset.email;
            document.getElementById('edit_whatsapp_number').value = this.dataset.whatsapp;
            document.getElementById('edit_house_id').value = this.dataset.house_id;
            document.getElementById('edit_start_date').value = this.dataset.start_date;
            document.getElementById('edit_end_date').value = this.dataset.end_date;
            document.getElementById('edit_status').value = this.dataset.status;
        });
    });

    const deleteBtns = document.querySelectorAll('.delete-tenant-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const house_id = this.dataset.house_id;
            Swal.fire({
                title: 'Confirm Delete',
                text: `Are you sure you want to delete ${name}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f72585',
                confirmButtonText: 'Yes, delete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_tenant_id').value = id;
                    document.getElementById('delete_tenant_house_id').value = house_id;
                    document.getElementById('deleteTenantForm').submit();
                }
            });
        });
    });
});

function calculateEndDate(startId, endId) {
    const startVal = document.getElementById(startId).value;
    if (startVal) {
        const start = new Date(startVal);
        start.setMonth(start.getMonth() + 3);
        const y = start.getFullYear();
        const m = String(start.getMonth() + 1).padStart(2, '0');
        const d = String(start.getDate()).padStart(2, '0');
        document.getElementById(endId).value = `${y}-${m}-${d}`;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
