<?php
// admin/expenses.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "Expense Management";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_expense'])) {
        if (!canManage()) {
            setFlash('danger', 'Unauthorized action!');
            redirect('expenses.php');
        }
        $category = sanitize($_POST['category']);
        $house_id = $_POST['house_id'] ?: null;
        $amount = sanitize($_POST['amount']);
        $expense_date = $_POST['expense_date'];
        $description = sanitize($_POST['description']);

        try {
            $stmt = $pdo->prepare("INSERT INTO expenses (category, house_id, amount, expense_date, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category, $house_id, $amount, $expense_date, $description]);
            logActivity($pdo, $_SESSION['admin_id'], 'Add Expense', "Recorded expense: $category of " . number_format($amount) . " TSH");
            setFlash('success', 'Expense recorded successfully!');
        } catch (PDOException $e) {
            setFlash('danger', 'Error adding expense: ' . $e->getMessage());
        }
    }

    if (isset($_POST['edit_expense'])) {
        if (!canEdit()) {
            setFlash('danger', 'Unauthorized action!');
            redirect('expenses.php');
        }
        $id = $_POST['expense_id'];
        $category = sanitize($_POST['category']);
        $house_id = $_POST['house_id'] ?: null;
        $amount = sanitize($_POST['amount']);
        $expense_date = $_POST['expense_date'];
        $description = sanitize($_POST['description']);

        try {
            $stmt = $pdo->prepare("UPDATE expenses SET category = ?, house_id = ?, amount = ?, expense_date = ?, description = ? WHERE id = ?");
            $stmt->execute([$category, $house_id, $amount, $expense_date, $description, $id]);
            logActivity($pdo, $_SESSION['admin_id'], 'Edit Expense', "Updated expense ID: $id ($category)");
            setFlash('success', 'Expense updated successfully!');
        } catch (PDOException $e) {
            setFlash('danger', 'Error updating expense: ' . $e->getMessage());
        }
    }

    if (isset($_POST['delete_expense'])) {
        if (!canManage()) {
            setFlash('danger', 'Unauthorized action!');
            redirect('expenses.php');
        }
        $id = $_POST['expense_id'];
        try {
            $expInfo = $pdo->query("SELECT category, amount FROM expenses WHERE id = $id")->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($expInfo) {
                logActivity($pdo, $_SESSION['admin_id'], 'Delete Expense', "Deleted expense: " . $expInfo['category'] . " of " . number_format($expInfo['amount']) . " TSH");
            }
            setFlash('success', 'Expense record deleted successfully!');
        } catch (PDOException $e) {
            setFlash('danger', 'Error deleting expense: ' . $e->getMessage());
        }
    }
    
    redirect('expenses.php');
}

// Fetch all expenses with house info
$expenses = $pdo->query("SELECT e.*, h.house_number FROM expenses e LEFT JOIN houses h ON e.house_id = h.id ORDER BY e.expense_date DESC")->fetchAll();
// Fetch all houses for selection
$houses = $pdo->query("SELECT id, house_number FROM houses ORDER BY house_number ASC")->fetchAll();

$categories = ['Repairs & Maintenance', 'Cleaning', 'Electricity', 'Water', 'Security', 'Taxes', 'Agent Fee', 'Landlord Management', 'Other'];

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Expenses</h2>
    <?php if (canManage()): ?>
    <button class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
        <i class="fas fa-plus me-2"></i> Add New Expense
    </button>
    <?php endif; ?>
</div>

<div class="table-responsive table-glass">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>House</th>
                <th>Amount</th>
                <th>Description</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($expenses)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No expenses recorded yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td class="fw-bold"><?php echo date('d M Y', strtotime($expense['expense_date'])); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo $expense['category']; ?></span></td>
                        <td><?php echo $expense['house_number'] ?: '<span class="text-muted">General</span>'; ?></td>
                        <td class="text-danger fw-bold"><?php echo formatCurrency($expense['amount']); ?> TSH</td>
                        <td><?php echo $expense['description'] ?: '-'; ?></td>
                        <td class="text-end">
                            <?php if (canEdit()): ?>
                            <button class="btn btn-sm btn-outline-primary edit-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editExpenseModal"
                                    data-id="<?php echo $expense['id']; ?>"
                                    data-category="<?php echo $expense['category']; ?>"
                                    data-house_id="<?php echo $expense['house_id']; ?>"
                                    data-amount="<?php echo $expense['amount']; ?>"
                                    data-date="<?php echo $expense['expense_date']; ?>"
                                    data-description="<?php echo $expense['description']; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (canManage()): ?>
                            <button class="btn btn-sm btn-outline-danger delete-btn" 
                                    data-id="<?php echo $expense['id']; ?>"
                                    data-category="<?php echo $expense['category']; ?>"
                                    data-amount="<?php echo $expense['amount']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Add New Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">House (Optional)</label>
                        <select name="house_id" class="form-select">
                            <option value="">General / None</option>
                            <?php foreach ($houses as $h): ?>
                                <option value="<?php echo $h['id']; ?>"><?php echo $h['house_number']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (TSH)</label>
                        <input type="number" name="amount" class="form-control" placeholder="e.g., 50000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_expense" class="btn btn-danger px-4">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="expense_id" id="edit_expense_id">
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" id="edit_category" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">House (Optional)</label>
                        <select name="house_id" id="edit_house_id" class="form-select">
                            <option value="">General / None</option>
                            <?php foreach ($houses as $h): ?>
                                <option value="<?php echo $h['id']; ?>"><?php echo $h['house_number']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (TSH)</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="expense_date" id="edit_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_expense" class="btn btn-primary px-4">Update Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteExpenseForm" action="" method="POST" style="display: none;">
    <input type="hidden" name="expense_id" id="delete_expense_id">
    <input type="hidden" name="delete_expense" value="1">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_expense_id').value = this.dataset.id;
            document.getElementById('edit_category').value = this.dataset.category;
            document.getElementById('edit_house_id').value = this.dataset.house_id;
            document.getElementById('edit_amount').value = this.dataset.amount;
            document.getElementById('edit_date').value = this.dataset.date;
            document.getElementById('edit_description').value = this.dataset.description;
        });
    });

    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const category = this.dataset.category;
            Swal.fire({
                title: 'Delete Expense?',
                text: `Deleting expense for ${category}. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f72585',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_expense_id').value = id;
                    document.getElementById('deleteExpenseForm').submit();
                }
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
