<?php
// admin/houses.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "House Management";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_house'])) {
        $house_number = sanitize($_POST['house_number']);
        $rent_amount = sanitize($_POST['rent_amount']);
        $description = sanitize($_POST['description']);
        $status = sanitize($_POST['status']);

        try {
            $stmt = $pdo->prepare("INSERT INTO houses (house_number, rent_amount, status, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$house_number, $rent_amount, $status, $description]);
            setFlash('success', 'House added successfully!');
        } catch (PDOException $e) {
            setFlash('danger', 'Error adding house: ' . $e->getMessage());
        }
    }

    if (isset($_POST['edit_house'])) {
        $id = $_POST['house_id'];
        $house_number = sanitize($_POST['house_number']);
        $rent_amount = sanitize($_POST['rent_amount']);
        $description = sanitize($_POST['description']);
        $status = sanitize($_POST['status']);

        try {
            $stmt = $pdo->prepare("UPDATE houses SET house_number = ?, rent_amount = ?, status = ?, description = ? WHERE id = ?");
            $stmt->execute([$house_number, $rent_amount, $status, $description, $id]);
            setFlash('success', 'House updated successfully!');
        } catch (PDOException $e) {
            setFlash('danger', 'Error updating house: ' . $e->getMessage());
        }
    }

    if (isset($_POST['delete_house'])) {
        $id = $_POST['house_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM houses WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'House deleted successfully!');
        } catch (PDOException $e) {
            setFlash('danger', 'Error deleting house: This house might have tenants assigned.');
        }
    }
    
    redirect('houses.php');
}

// Fetch all houses
$houses = $pdo->query("SELECT * FROM houses ORDER BY id DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Houses</h2>
    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addHouseModal">
        <i class="fas fa-plus me-2"></i> Add New House
    </button>
</div>

<div class="table-responsive table-glass">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>House #</th>
                <th>Rent Amount</th>
                <th>Status</th>
                <th>Description</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($houses)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No houses found. Add one to get started!</td>
                </tr>
            <?php else: ?>
                <?php foreach ($houses as $house): ?>
                    <tr>
                        <td class="fw-bold"><?php echo $house['house_number']; ?></td>
                        <td><?php echo formatCurrency($house['rent_amount']); ?> TSH</td>
                        <td>
                            <span class="badge rounded-pill <?php echo $house['status'] == 'vacant' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ucfirst($house['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $house['description'] ?: '-'; ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary edit-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editHouseModal"
                                    data-id="<?php echo $house['id']; ?>"
                                    data-number="<?php echo $house['house_number']; ?>"
                                    data-rent="<?php echo $house['rent_amount']; ?>"
                                    data-status="<?php echo $house['status']; ?>"
                                    data-description="<?php echo $house['description']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-btn" 
                                    data-id="<?php echo $house['id']; ?>"
                                    data-number="<?php echo $house['house_number']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add House Modal -->
<div class="modal fade" id="addHouseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Add New House</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label">House Number / Name</label>
                        <input type="text" name="house_number" class="form-control" placeholder="e.g., Block A-01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monthly Rent Amount (TSH)</label>
                        <input type="number" name="rent_amount" class="form-control" placeholder="e.g., 250000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="vacant">Vacant</option>
                            <option value="occupied">Occupied</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_house" class="btn btn-primary px-4">Save House</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit House Modal -->
<div class="modal fade" id="editHouseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit House</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="house_id" id="edit_house_id">
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label">House Number / Name</label>
                        <input type="text" name="house_number" id="edit_house_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monthly Rent Amount (TSH)</label>
                        <input type="number" name="rent_amount" id="edit_house_rent" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_house_status" class="form-select">
                            <option value="vacant">Vacant</option>
                            <option value="occupied">Occupied</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" id="edit_house_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_house" class="btn btn-primary px-4">Update House</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" action="" method="POST" style="display: none;">
    <input type="hidden" name="house_id" id="delete_house_id">
    <input type="hidden" name="delete_house" value="1">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Modal Data Population
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_house_id').value = this.dataset.id;
            document.getElementById('edit_house_number').value = this.dataset.number;
            document.getElementById('edit_house_rent').value = this.dataset.rent;
            document.getElementById('edit_house_status').value = this.dataset.status;
            document.getElementById('edit_house_description').value = this.dataset.description;
        });
    });

    // Delete Confirmation with SweetAlert2
    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const number = this.dataset.number;
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete house ${number}. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f72585',
                cancelButtonColor: '#4361ee',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_house_id').value = id;
                    document.getElementById('deleteForm').submit();
                }
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
