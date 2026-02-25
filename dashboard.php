<?php
// dashboard.php
require_once 'config/db.php';
require_once 'config/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Dashboard";

// Fetch Stats
$totalHouses = $pdo->query("SELECT COUNT(*) FROM houses")->fetchColumn();
$totalTenants = $pdo->query("SELECT COUNT(*) FROM tenants WHERE status = 'active'")->fetchColumn();
$occupiedHouses = $pdo->query("SELECT COUNT(*) FROM houses WHERE status = 'occupied'")->fetchColumn();
$vacantHouses = $pdo->query("SELECT COUNT(*) FROM houses WHERE status = 'vacant'")->fetchColumn();

// Financial Stats (Current Month)
$currentMonth = date('Y-m');
$monthlyIncome = $pdo->query("SELECT SUM(amount_paid) FROM payments WHERE payment_month = '$currentMonth'")->fetchColumn() ?: 0;
$totalExpenses = $pdo->query("SELECT SUM(amount) FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = '$currentMonth'")->fetchColumn() ?: 0;
$monthlyProfit = $monthlyIncome - $totalExpenses;

// Debt Stats
$totalDebt = $pdo->query("SELECT SUM(balance) FROM payments")->fetchColumn() ?: 0;
$debtTenants = $pdo->query("SELECT COUNT(DISTINCT tenant_id) FROM payments WHERE balance > 0")->fetchColumn() ?: 0;

// Overdue/Expired Tenants (End Date Passed)
$overdueTenants = $pdo->query("SELECT t.*, h.house_number FROM tenants t JOIN houses h ON t.house_id = h.id WHERE t.end_date <= CURRENT_DATE AND t.status = 'active' ORDER BY t.end_date ASC")->fetchAll();


include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <h3><?php echo $totalHouses; ?></h3>
            <p>Total Houses</p>
            <small class="text-success"><?php echo $occupiedHouses; ?> Occupied</small> | 
            <small class="text-warning"><?php echo $vacantHouses; ?> Vacant</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: var(--secondary-color);">
            <h3><?php echo $totalTenants; ?></h3>
            <p>Active Tenants</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <h3><?php echo formatCurrency($monthlyIncome); ?></h3>
            <p>Monthly Income (<?php echo date('M'); ?>)</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3><?php echo formatCurrency($totalDebt); ?></h3>
            <p>Total Outstanding Debt</p>
            <small class="text-danger"><?php echo count($overdueTenants); ?> Period Expired</small> | 
            <small class="text-danger"><?php echo $debtTenants; ?> with balance</small>
        </div>
    </div>
</div>

<?php if (!empty($overdueTenants)): ?>
<div class="row mt-4">
    <div class="col-md-12">
            <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-0">
                <h5 class="fw-bold text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Action Required: Rental Period Expired</h5>
                <p class="mb-3">The following tenants have reached their payment cycle end date and need to settle their next 3-month payment.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Tenant</th>
                                <th>House</th>
                                <th>Expiry Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdueTenants as $ot): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $ot['full_name']; ?></td>
                                    <td><?php echo $ot['house_number']; ?></td>
                                    <td class="text-danger fw-bold"><?php echo date('d M Y', strtotime($ot['end_date'])); ?></td>
                                    <td class="text-end">
                                        <a href="admin/tenant_profile.php?id=<?php echo $ot['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">View Profile</a>
                                        <a href="communication/center.php?tenant_id=<?php echo $ot['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">Notify</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <h5 class="card-title mb-4 font-weight-bold">Financial Summary (Current Month)</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light text-center">
                            <small class="text-muted d-block">Monthly Income</small>
                            <span class="h5 fw-bold text-success"><?php echo formatCurrency($monthlyIncome); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light text-center">
                            <small class="text-muted d-block">Monthly Expenses</small>
                            <span class="h5 fw-bold text-danger"><?php echo formatCurrency($totalExpenses); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light text-center">
                            <small class="text-muted d-block">Net Profit</small>
                            <span class="h5 fw-bold text-primary"><?php echo formatCurrency($monthlyProfit); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
