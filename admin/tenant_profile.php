<?php
// admin/tenant_profile.php
require_once '../config/db.php';
require_once '../config/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$id = $_GET['id'] ?? null;
if (!$id) {
    redirect('tenants.php');
}

// Fetch Tenant Details
$stmt = $pdo->prepare("SELECT t.*, h.house_number, h.rent_amount FROM tenants t LEFT JOIN houses h ON t.house_id = h.id WHERE t.id = ?");
$stmt->execute([$id]);
$tenant = $stmt->fetch();

if (!$tenant) {
    redirect('tenants.php');
}

$page_title = "Tenant Profile: " . $tenant['full_name'];

// Fetch Payment History
$stmt = $pdo->prepare("SELECT * FROM payments WHERE tenant_id = ? ORDER BY payment_month DESC");
$stmt->execute([$id]);
$payments = $stmt->fetchAll();

// Calculate Totals based on exact months stayed
$startDate = new DateTime($tenant['start_date']);
$today = new DateTime();
$diff = $startDate->diff($today);
$monthsStayed = ($diff->y * 12) + $diff->m;
// If they have stayed even 1 day of a new month, count it (optional, but standard for rent)
if ($diff->d > 0) $monthsStayed += 1;

$expectedRent = $monthsStayed * ($tenant['rent_amount']);

$totalPaid = 0;
foreach ($payments as $p) {
    $totalPaid += $p['amount_paid'];
}
$currentDebt = $expectedRent - $totalPaid;
if ($currentDebt < 0) $currentDebt = 0;

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body text-center">
                <img src="<?php echo $tenant['photo'] ? '../uploads/tenants/' . $tenant['photo'] : '../assets/img/default-profile.png'; ?>" 
                     class="profile-img-preview mb-3" style="width: 150px; height: 150px;">
                <h4 class="fw-bold"><?php echo $tenant['full_name']; ?></h4>
                <p class="text-muted"><i class="fas fa-home me-2"></i> <?php echo $tenant['house_number'] ?: 'No house assigned'; ?></p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="https://wa.me/<?php echo $tenant['whatsapp_number']; ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-circle">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:<?php echo $tenant['email']; ?>" class="btn btn-outline-primary btn-sm rounded-circle">
                        <i class="fas fa-envelope"></i>
                    </a>
                    <a href="tel:<?php echo $tenant['phone']; ?>" class="btn btn-outline-info btn-sm rounded-circle">
                        <i class="fas fa-phone"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Information Details</h5>
                <div class="mb-2">
                    <small class="text-muted d-block">Phone Number</small>
                    <span class="fw-bold"><?php echo $tenant['phone']; ?></span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Email Address</small>
                    <span class="fw-bold"><?php echo $tenant['email'] ?: '-'; ?></span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Start Date</small>
                    <span class="fw-bold"><?php echo date('M d, Y', strtotime($tenant['start_date'])); ?></span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Monthly Rent</small>
                    <span class="fw-bold text-primary"><?php echo formatCurrency($tenant['rent_amount'] ?? 0); ?> TSH</span>
                </div>
                <div class="mb-0">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge <?php echo $tenant['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo ucfirst($tenant['status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6">
                <div class="stat-card" style="border-left-color: var(--success-color);">
                    <h3><?php echo formatCurrency($totalPaid); ?></h3>
                    <p>Total Paid</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card" style="border-left-color: var(--danger-color);">
                    <h3><?php echo formatCurrency($currentDebt); ?></h3>
                    <p>Current Debt</p>
                    <small class="text-muted">Stayed: <?php echo $monthsStayed; ?> Months | Expected: <?php echo formatCurrency($expectedRent); ?></small>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mt-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Payment History</h5>
                    <a href="payments.php?tenant_id=<?php echo $id; ?>" class="btn btn-primary btn-sm">Record Payment</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Date Paid</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th>Receipt #</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No payments recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($payment['payment_month'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                        <td class="fw-bold"><?php echo formatCurrency($payment['amount_paid']); ?></td>
                                        <td class="text-danger fw-bold"><?php echo formatCurrency($payment['balance']); ?></td>
                                        <td><small class="text-muted"><?php echo $payment['receipt_number']; ?></small></td>
                                        <td>
                                            <a href="../reports/receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Communication Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Send Communication</h5>
                        <div class="d-flex gap-2">
                             <a href="../communication/center.php?action=sms&tenant_id=<?php echo $id; ?>" class="btn btn-outline-primary flex-grow-1">
                                <i class="fas fa-sms me-2"></i> SMS
                             </a>
                             <a href="../communication/center.php?action=email&tenant_id=<?php echo $id; ?>" class="btn btn-outline-info flex-grow-1">
                                <i class="fas fa-envelope me-2"></i> Email
                             </a>
                             <a href="../communication/center.php?action=whatsapp&tenant_id=<?php echo $id; ?>" class="btn btn-outline-success flex-grow-1">
                                <i class="fab fa-whatsapp me-2"></i> WhatsApp
                             </a>
                             <a href="../reports/contract.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary flex-grow-1" target="_blank">
                                <i class="fas fa-file-contract me-2"></i> Contract
                             </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
