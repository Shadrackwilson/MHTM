<?php
// reports/index.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = "Reports & Analytics";

// Filters
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');

// Report A: Rent Collection
$stmt = $pdo->prepare("SELECT p.*, t.full_name, h.house_number 
                       FROM payments p 
                       JOIN tenants t ON p.tenant_id = t.id 
                       JOIN houses h ON t.house_id = h.id 
                       WHERE payment_date BETWEEN ? AND ? 
                       ORDER BY payment_date DESC");
$stmt->execute([$start_date, $end_date]);
$payments = $stmt->fetchAll();
$total_income = array_sum(array_column($payments, 'amount_paid'));

// Report B: Outstanding Balances
$debtors = $pdo->query("SELECT t.full_name, h.house_number, SUM(p.balance) as total_debt 
                        FROM tenants t 
                        JOIN houses h ON t.house_id = h.id 
                        JOIN payments p ON t.id = p.tenant_id 
                        WHERE p.balance > 0 
                        GROUP BY t.id 
                        HAVING total_debt > 0")->fetchAll();
$total_debt = array_sum(array_column($debtors, 'total_debt'));

// Report C: Expenses
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_date BETWEEN ? AND ? ORDER BY expense_date DESC");
$stmt->execute([$start_date, $end_date]);
$expenses = $stmt->fetchAll();
$total_expenses = array_sum(array_column($expenses, 'amount'));

// Report D: Profit
$profit = $total_income - $total_expenses;

include '../includes/header.php';
?>

<div class="card shadow-sm border-0 rounded-4 mb-4 no-print">
    <div class="card-body">
        <form action="" method="GET" class="row align-items-end">
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1 rounded-pill">Filter Report</button>
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary rounded-pill">Print</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <h3><?php echo formatCurrency($total_income); ?></h3>
            <p>Total Collection</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3><?php echo formatCurrency($total_expenses); ?></h3>
            <p>Total Expenses</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: var(--primary-color);">
            <h3><?php echo formatCurrency($profit); ?></h3>
            <p>Net Profit</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: var(--warning-color);">
            <h3><?php echo formatCurrency($total_debt); ?></h3>
            <p>Total Oustanding Debt</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-pills mb-3 no-print" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-4" id="pills-income-tab" data-bs-toggle="pill" data-bs-target="#pills-income" type="button" role="tab">Income</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="pills-expenses-tab" data-bs-toggle="pill" data-bs-target="#pills-expenses" type="button" role="tab">Expenses</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="pills-debt-tab" data-bs-toggle="pill" data-bs-target="#pills-debt" type="button" role="tab">Debt</button>
            </li>
        </ul>
        
        <div class="tab-content" id="pills-tabContent">
            <!-- Income Tab -->
            <div class="tab-pane fade show active" id="pills-income" role="tabpanel">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">Rent Collection History</h5>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Tenant</th>
                                        <th>House #</th>
                                        <th>Month</th>
                                        <th>Amount</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($p['payment_date'])); ?></td>
                                            <td><?php echo $p['full_name']; ?></td>
                                            <td><?php echo $p['house_number']; ?></td>
                                            <td><?php echo date('F Y', strtotime($p['payment_month'])); ?></td>
                                            <td class="fw-bold"><?php echo formatCurrency($p['amount_paid']); ?></td>
                                            <td><small><?php echo $p['receipt_number']; ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenses Tab -->
            <div class="tab-pane fade" id="pills-expenses" role="tabpanel">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">
                         <h5 class="fw-bold mb-4">Expense Records</h5>
                         <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses as $e): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($e['expense_date'])); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?php echo $e['category']; ?></span></td>
                                            <td><?php echo $e['description']; ?></td>
                                            <td class="text-end fw-bold text-danger"><?php echo formatCurrency($e['amount']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debt Tab -->
            <div class="tab-pane fade" id="pills-debt" role="tabpanel">
                 <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">Tenants with Outstanding Balance</h5>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Tenant Name</th>
                                        <th>House #</th>
                                        <th class="text-end">Total Debt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($debtors as $d): ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo $d['full_name']; ?></td>
                                            <td><?php echo $d['house_number']; ?></td>
                                            <td class="text-end fw-bold text-danger"><?php echo formatCurrency($d['total_debt']); ?> TSH</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .stat-card { border: 1px solid #ddd !important; }
    .tab-content > .tab-pane { display: block !important; opacity: 1 !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
