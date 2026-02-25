<?php
// admin/payments.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "Payment Management";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['record_payment'])) {
        $tenant_id = $_POST['tenant_id'];
        $amount_paid = sanitize($_POST['amount_paid']);
        $payment_month = $_POST['payment_month'];
        $payment_date = $_POST['payment_date'];
        
        // Fetch Tenant house rent
        $stmt = $pdo->prepare("SELECT h.rent_amount FROM tenants t JOIN houses h ON t.house_id = h.id WHERE t.id = ?");
        $stmt->execute([$tenant_id]);
        $rent = $stmt->fetchColumn() ?: 0;
        $total_due = $rent * 3; // 3 months cycle
        
        $balance = $total_due - $amount_paid;
        if ($balance < 0) $balance = 0;

        $receipt_number = generateReceiptNo();

        try {
            $stmt = $pdo->prepare("INSERT INTO payments (tenant_id, amount_paid, payment_month, payment_date, balance, receipt_number) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenant_id, $amount_paid, $payment_month, $payment_date, $balance, $receipt_number]);
            setFlash('success', 'Payment recorded successfully! Receipt: ' . $receipt_number);
        } catch (PDOException $e) {
            setFlash('danger', 'Error recording payment: ' . $e->getMessage());
        }
    }

    if (isset($_POST['delete_payment'])) {
        $id = $_POST['payment_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Payment record deleted successfully!');
        } catch (PDOException $e) {
            setFlash('danger', 'Error deleting payment: ' . $e->getMessage());
        }
    }
    
    redirect('payments.php');
}

// Fetch all payments with tenant info
$payments = $pdo->query("SELECT p.*, t.full_name, h.house_number FROM payments p JOIN tenants t ON p.tenant_id = t.id JOIN houses h ON t.house_id = h.id ORDER BY p.id DESC LIMIT 50")->fetchAll();
// Fetch active tenants for the form
$tenants = $pdo->query("SELECT t.id, t.full_name, h.house_number, h.rent_amount FROM tenants t JOIN houses h ON t.house_id = h.id WHERE t.status = 'active' ORDER BY t.full_name ASC")->fetchAll();

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Payments</h2>
    <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
        <i class="fas fa-plus me-2"></i> Record New Payment
    </button>
</div>

<div class="table-responsive table-glass">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Receipt #</th>
                <th>Tenant Name</th>
                <th>House</th>
                <th>Month</th>
                <th>Amount Paid</th>
                <th>Balance</th>
                <th>Date Paid</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No payments recorded yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td class="fw-bold text-muted"><?php echo $payment['receipt_number']; ?></td>
                        <td class="fw-bold"><?php echo $payment['full_name']; ?></td>
                        <td><?php echo $payment['house_number']; ?></td>
                        <td><?php echo date('F Y', strtotime($payment['payment_month'])); ?></td>
                        <td class="text-success fw-bold"><?php echo formatCurrency($payment['amount_paid']); ?></td>
                        <td class="<?php echo $payment['balance'] > 0 ? 'text-danger fw-bold' : 'text-success'; ?>">
                            <?php echo formatCurrency($payment['balance']); ?>
                        </td>
                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                        <td class="text-end">
                            <a href="../reports/receipt.php?id=<?php echo $payment['id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-print"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger delete-btn" 
                                    data-id="<?php echo $payment['id']; ?>"
                                    data-receipt="<?php echo $payment['receipt_number']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label">Select Tenant</label>
                        <select name="tenant_id" id="payment_tenant_id" class="form-select" required onchange="updateRentInfo(this)">
                            <option value="">Choose Tenant...</option>
                            <?php foreach ($tenants as $tenant): ?>
                                <option value="<?php echo $tenant['id']; ?>" data-rent="<?php echo $tenant['rent_amount']; ?>">
                                    <?php echo $tenant['full_name']; ?> (<?php echo $tenant['house_number']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Total Rent Due (3 Months): <strong id="rent_amount_info">0</strong> TSH</label>
                        <small class="d-block text-muted">Monthly Rent: <span id="monthly_rent_info">0</span> TSH</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount Paid (TSH)</label>
                        <input type="number" name="amount_paid" id="payment_amount_paid" class="form-control" placeholder="e.g., 200000" required oninput="calculateBalance()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Balance: <strong id="balance_info" class="text-danger">0</strong> TSH</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Month</label>
                        <input type="month" name="payment_month" class="form-control" value="<?php echo date('Y-m'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Paid</label>
                        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="record_payment" class="btn btn-success px-4">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deletePaymentForm" action="" method="POST" style="display: none;">
    <input type="hidden" name="payment_id" id="delete_payment_id">
    <input type="hidden" name="delete_payment" value="1">
</form>

<script>
let currentRent = 0;

function updateRentInfo(select) {
    const option = select.options[select.selectedIndex];
    const monthlyRent = option.dataset.rent || 0;
    currentRent = monthlyRent * 3; // Calculate 3 months total
    
    document.getElementById('monthly_rent_info').innerText = new Intl.NumberFormat().format(monthlyRent);
    document.getElementById('rent_amount_info').innerText = new Intl.NumberFormat().format(currentRent);
    calculateBalance();
}

function calculateBalance() {
    const paid = document.getElementById('payment_amount_paid').value || 0;
    const balance = currentRent - paid;
    document.getElementById('balance_info').innerText = new Intl.NumberFormat().format(balance > 0 ? balance : 0);
}

document.addEventListener('DOMContentLoaded', function() {
    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const receipt = this.dataset.receipt;
            Swal.fire({
                title: 'Delete Payment?',
                text: `You are deleting payment record ${receipt}. This will affect financial stats!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f72585',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_payment_id').value = id;
                    document.getElementById('deletePaymentForm').submit();
                }
            });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
